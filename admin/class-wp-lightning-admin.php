<?php

 use \tkijewski\lnurl;

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    WP_Lightning
 * @subpackage WP_Lightning/admin
 */
class WP_Lightning_Admin {

	/**
     * Main Plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      WP_Lightning    $plugin    The main plugin object.
     */
    private $plugin;

	/**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param    WP_Lightning    $plugin       The main plugin object.
     */
    public function __construct($plugin)
    {
        $this->plugin = $plugin;
    }

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		wp_enqueue_style( $this->plugin->get_plugin_name(), plugin_dir_url( __FILE__ ) . 'css/wp-lightning-admin.css', array(), $this->plugin->get_version(), 'all' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		wp_enqueue_script(  $this->plugin->get_plugin_name(), plugin_dir_url( __FILE__ ) . 'js/wp-lightning-admin.js', array( 'jquery' ), $this->plugin->get_version(), true );
	}

	/**
	 * Admin Page
	 */
	public function lightning_menu()
	{
		add_menu_page(
			'Lightning Paywall',
			'Lightning Paywall',
			'manage_options',
			'lnp_settings',
			null,
			'dashicons-superhero'
		);
	}

	/**
     * Register the donation gutenberg block
     */
    public function init_donation_block() {

        // Gutenberg is not active.
        if ( ! function_exists( 'register_block_type' ) ) {
            return;
        }

        // Path to Js that handles block functionality
        wp_register_script(
            'alby/donate-js',
            sprintf(
                '%s/assets/js/blocks/donation/donation.js',
                untrailingslashit(WP_LN_ROOT_URI)
            )
        );

        wp_register_style(
            'alby/donate-css',
            sprintf(
                '%s/assets/css/blocks/donation.css',
                untrailingslashit(WP_LN_ROOT_URI)
            )
        );


        register_block_type( 'alby/donate', array(
            'api_version'     => 2,
            'title'           => 'Alby: Bitcoin Donation',
            'category'        => 'common',
            'description'     => 'Learning in progress',
            'icon'            => 'icon-alby',
            'editor_script'   => 'alby/donate-js',
            'editor_style'    => 'alby/donate-css',
            'render_callback' => (array($this, 'render_gutenberg')),
        ));
    }

	public function render_gutenberg( $atts )
    {
        $atts = shortcode_atts(array(
            'pay_block'     => 'true',
            'btc_format'    => '',
            'currency'      => '',
            'price'         => '',
            'duration_type' => '',
            'duration'      => '',
        ), $atts);

        return do_shortcode("[alby_donation_block]");
    }

    /**
     * Register the paywall gutenberg block
     */
    public function init_paywall_block() {

        // Gutenberg is not active.
        if ( ! function_exists( 'register_block_type' ) ) {
            return;
        }

        // Path to Js that handles block functionality
        wp_register_script(
            'alby/paywall-js',
            sprintf(
                '%s/assets/js/blocks/paywall/paywall.js',
                untrailingslashit(WP_LN_ROOT_URI)
            )
        );

        wp_register_style(
            'alby/paywall-css',
            sprintf(
                '%s/assets/css/blocks/paywall.css',
                untrailingslashit(WP_LN_ROOT_URI)
            )
        );


        register_block_type( 'alby/paywall', array(
            'api_version'     => 2,
            'title'           => 'Alby: Paywall Payment',
            'category'        => 'common',
            'description'     => 'Used to separate public and private content to initialize paywall',
            'icon'            => 'icon-alby',
            'editor_script'   => 'alby/paywall-js',
            'editor_style'    => 'alby/paywall-css',
            'render_callback' => (array($this, 'render_paywall_block')),
        ));
    }

    public function render_paywall_block( $atts )
    {
        return do_shortcode("[lnd-amount]");
    }

	function widget_init()
	{
        $lnurl = lnurl\encodeUrl(get_rest_url(null, '/lnp-alby/v1/lnurlp'));
        $widget = new TwentyunoWidget(["lnurl" => $lnurl]);
		register_widget($widget);
	}
}
