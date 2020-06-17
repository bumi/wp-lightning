<?php
/*
    Plugin Name: Lightning Publisher
    Version:     0.1.8
    Plugin URI:  https://github.com/ElementsProject/wordpress-lightning-publisher
    Description: Lightning Publisher for WordPress
    Author:      Blockstream
    Author URI:  https://blockstream.com
*/

if (!defined('ABSPATH')) exit;

require_once 'vendor/autoload.php';
define('LIGHTNING_PUBLISHER_KEY', hash_hmac('sha256', 'lightning-publisher-token', AUTH_KEY));

class Lightning_Publisher {
  public function __construct() {
    $this->options = get_option('ln_publisher');
    $this->charge = new LightningChargeClient($this->options['server_url'], $this->options['api_token']);

    // frontend
    add_action('wp_enqueue_scripts', array($this, 'enqueue_script'));
    add_filter('the_content',        array($this, 'ifpaid_filter'));

    // ajax
    add_action('wp_ajax_lnd_invoice',        array($this, 'ajax_make_invoice'));
    add_action('wp_ajax_nopriv_lnd_invoice', array($this, 'ajax_make_invoice'));
    add_action('wp_ajax_lnd_token',          array($this, 'ajax_make_token'));
    add_action('wp_ajax_nopriv_lnd_token',   array($this, 'ajax_make_token'));

    // admin
    add_action('admin_init', array($this, 'admin_init'));
    add_action('admin_menu', array($this, 'admin_menu'));
  }

  /**
   * Process [ifpaid] tags in post content
   */
  public function ifpaid_filter($content) {
    $ifpaid = self::extract_ifpaid_tag($content);
    if (!$ifpaid) return $content;

    $post_id = get_the_ID();
    list($public, $protected) = preg_split('/(<p>)?' . preg_quote($ifpaid->tag, '/') . '(<\/p>)?/', $content, 2);

    return self::check_payment($post_id) ? self::format_paid($post_id, $ifpaid, $public, $protected)
                                         : self::format_unpaid($post_id, $ifpaid, $public);
  }

  /**
   * Register scripts and styles
   */
  public function enqueue_script() {
    wp_enqueue_script('ln-publisher', plugins_url('js/publisher.js', __FILE__), array('jquery'));
    wp_enqueue_style('ln-publisher', plugins_url('css/publisher.css', __FILE__));
    wp_localize_script('ln-publisher', 'LN_publisher', array(
      'ajax_url'   => admin_url('admin-ajax.php'),
      'charge_url' => !empty($this->options['public_url']) ? $this->options['public_url'] : $this->options['server_url']
    ));
  }

  /**
   * AJAX endpoint to create new invoices
   */
  public function ajax_make_invoice() {
    $post_id = (int)$_POST['post_id'];
    $ifpaid = self::extract_ifpaid_tag(get_post_field('post_content', $post_id));
    if (!$ifpaid) return status_header(404);

    $invoice = $this->charge->invoice([
      'currency'    => $ifpaid->currency,
      'amount'      => $ifpaid->amount,
      'description' => get_bloginfo('name') . ': pay to continue reading ' . get_the_title($post_id),
      'metadata'    => [ 'source' => 'wordpress-lightning-publisher', 'post_id' => $post_id, 'url' => get_permalink($post_id) ]
    ]);

    wp_send_json($invoice->id, 201);
  }

  /**
   * AJAX endpoint to exchange invoices for HMAC access tokens
   * @TODO persist to cookie?
   */
  public function ajax_make_token() {
    $invoice = $this->charge->fetch($_POST['invoice_id']);

    if (!$invoice)                    return status_header(404);
    if ($invoice->status !== 'paid')  return status_header(402);
    if (!$invoice->metadata->post_id) return status_header(500); // should never actually happen

    $post_id = $invoice->metadata->post_id;
    $token   = self::make_token($post_id);
    $url     = add_query_arg('publisher_access', $token, get_permalink($post_id));

    wp_send_json([ 'post_id' => $post_id, 'token' => $token, 'url' => $url ]);
  }

  /**
   * Create HMAC tokens granting access to $post_id
   * @param int $post_id
   * @return str base36 token
   * @TODO expiry time, link token to invoice
   */
  protected static function make_token($post_id) {
    return base_convert(hash_hmac('sha256', $post_id, LIGHTNING_PUBLISHER_KEY), 16, 36);
  }


  /**
   * Check whether the current visitor has access to $post_id
   * @param int $post_id
   * @return bool
   */
  protected static function check_payment($post_id) {
    return isset($_GET['publisher_access']) && self::make_token($post_id) === $_GET['publisher_access'];
  }

  /**
   * Parse [ifpaid] tags and return as structured data
   * Expected format: [ifpaid AMOUNT CURRENCY KEY=VAL]
   * @param string $content
   * @return array
   */
  protected static function extract_ifpaid_tag($content) {
    if (!preg_match('/\[ifpaid [\d.]+ [a-z]+.*?\]/i', $content, $m)) return;
    $tag = html_entity_decode(str_replace(array('&#8220;', '&#8221;'), '"', $m[0]));
    if (substr($tag, -2, 1) !== ' ') $tag = substr($tag, 0, -1) . ' ]';
    $attrs = shortcode_parse_atts($tag);
    return (object)[ 'tag' => $m[0], 'amount' => $attrs[1], 'currency' => $attrs[2], 'attrs' => $attrs ];
  }

  /**
   * Format display for paid post
   */
  protected static function format_paid($post_id, $ifpaid, $public, $protected) {
    $text = isset($ifpaid->attrs['thanks']) ? $ifpaid->attrs['thanks']
      : "<p>Thank you for paying! The rest of the post is available below.</p><p>To return to this content later, please add this page to your bookmarks (Ctrl-d).</p>";

    return sprintf('%s<div class="ln-publisher-paid" id="paid">%s</div>%s', $public, $text, $protected);
  }

  /**
   * Format display for unpaid post
   */
  protected static function format_unpaid($post_id, $ifpaid, $public) {
    $attrs  = $ifpaid->attrs;
    $text   = '<p>' . sprintf(!isset($attrs['text']) ? 'To continue reading the rest of this post, please pay <em>%s</em>.' : $attrs['text'], $ifpaid->amount . ' ' . $ifpaid->currency).'</p>';
    $button = sprintf('<a class="ln-publisher-btn" href="#" data-publisher-postid="%d">%s</a>', $post_id, !isset($attrs['button']) ? 'Pay to continue reading' : $attrs['button']);

    return sprintf('%s<div class="ln-publisher-pay">%s%s</div>', $public, $text, $button);
  }

  /**
   * Admin settings page
   */

  public function admin_menu() {
    add_options_page('Lightning Publisher Settings', 'Lightning Publisher',
                     'manage_options', 'lnd_', array($this, 'admin_page'));
  }
  public function admin_init() {
    register_setting('lnd_', 'lnd_');
    add_settings_section('lnd_server', 'Lightning Charge Server', null, 'lnd_');

    add_settings_field('lnd_address', 'Address', array($this, 'field_address'), 'lnd_', 'lnd_server');
    add_settings_field('lnd_macaroon', 'Macaroon', array($this, 'field_macaroon'), 'lnd_', 'lnd_server');
    add_settings_field('lnd_tls_cert', 'TLS cert', array($this, 'field_tls_cert'), 'lnd_', 'lnd_server');
  }
  public function admin_page() {
    ?>
    <div class="wrap">
        <h1>Lightning Publisher Settings</h1>
        <form method="post" action="options.php">
        <?php
            settings_fields('lnd_');
            do_settings_sections('lnd_');
            submit_button();
        ?>
        </form>
    </div>
    <?php
  }
  /* Function to validate options */
  
public function validate_options($lnd_address, $lnd_macaroon, $lnd_tls_cert)
{
    if( preg_match('^[\s]*[\s]*$', $lnd_address))
    {
        return false;
    }	
    if (preg_match('^[\s]*[\s]*$', $lnd_macaroon)){

    return false;
  }
  if (preg_match('^[\s]*[\s]*$', $lnd_tls_cert)){
 
    return false;
}
    return true;
}


  public function field_address(){
    printf('<input type="text" name="lnd_[address]" value="%s" />', esc_attr($this->options['address']));
  }
  public function field_macaroon(){
    printf('<input type="text" name="lnd_[macaroon]" value="%s" /><br><label>%s</label>', esc_attr($this->options['macaroon']),
           'URL where Lightning Charge is publicily accessible to users. Optional, defaults to Server URL.');
  }
  public function field_tls_cert(){
    printf('<input type="text" name="lnd_[tls_cert]" value="%s" />', esc_attr($this->options['tls_cert']));
  }
}

new Lightning_Publisher();
