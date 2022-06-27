<?php

use \Firebase\JWT;
use \Monolog\Logger;
use \Monolog\Handler\StreamHandler;

class WP_Lightning
{
	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      WP_Lightning_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * The lightning client.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      WP_Lightning_Client_Interface   $lightningClient    The lightning client.
	 */
	protected $lightningClient;

	/**
	 * The lightning client type.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $lightningClient    The lightning client type.
	 */
	protected $lightningClientType;

	/**
	 * The database handler.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      LNP_DatabaseHandler    $database_handler    The database handler.
	 */
	protected $database_handler;

	/**
	 * The connection options.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      array    $connection_options    The connection options.
	 */
	protected $connection_options;

	/**
	 * The paywall options.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      array    $paywall_options    The paywall options.
	 */
	protected $paywall_options;

	/**
	 * The donation options.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      array    $donation_options    The donation options.
	 */
	protected $donation_options;

	/**
	 * The Monolog Logger
	 * @var Logger $logger
	 */
	protected $logger;

	/**
	 * The Plugin Admin Class
	 */
	protected $plugin_admin;

	/**
	 * The Plugin Public Class
	 */
	protected $plugin_public;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct()
	{
		if (defined('WP_LIGHTNING_VERSION')) {
			$this->version = WP_LIGHTNING_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'wp-lightning';
		$this->load_dependencies();
		$this->initialize_loader();
		$this->initialize_logger();
		$this->set_locale();
		$this->read_database_options();
		$this->setup_client();
		$this->define_admin_hooks();
		$this->define_public_hooks();
		$this->define_shortcodes();
		$this->initialize_rest_api();
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - WP_Lightning_Loader. Orchestrates the hooks of the plugin.
	 * - WP_Lightning_i18n. Defines internationalization functionality.
	 * - WP_Lightning_Admin. Defines all hooks for the admin area.
	 * - WP_Lightning_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies()
	{

		// Composer dependencies
		require_once plugin_dir_path(dirname(__FILE__)) . 'vendor/autoload.php';

		// Custom Tables
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/db/database-handler.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/db/transactions.php';

		// REST API Server
		// Server class includes controllers
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/rest-api/class-rest-server.php';

		// Settings
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/settings/class-abstract-settings.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/settings/class-dashboard.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/settings/class-balance.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/settings/class-donation.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/settings/class-paywall.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/settings/class-connections.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/settings/class-help.php';

		// Admin stuff
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/widgets/twentyuno-widget.php';

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wp-lightning-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wp-lightning-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-wp-lightning-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-wp-lightning-public.php';

		/**
		 * The lightning client classes.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/clients/lightning-address.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/clients/interface-wp-lightning-client.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/clients/abstract-class-wp-lightning-client.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/clients/class-wp-lightning-btcpay-client.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/clients/class-wp-lightning-lnaddress-client.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/clients/class-wp-lightning-lnbits-client.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/clients/class-wp-lightning-lnd-client.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/clients/class-wp-lightning-lndhub-client.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-wp-lightning-paywall.php';

		/**
		 * The class responsible for donation widget
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-wp-lightning-donations-widget.php';

		/**
		 * The class responsible for REST API.
		 */
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/rest-api/class-rest-server.php';

		$this->plugin_admin = new WP_Lightning_Admin($this);
		$this->plugin_public = new WP_Lightning_Public($this);
	}

	/**
	 * Initialize the loader which registers all actions and filters for the plugin
	 */
	private function initialize_loader() {
		$this->loader = new WP_Lightning_Loader();
	}

	/**
	 * Initialize the logger instance
	 */
	private function initialize_logger() {
		$this->logger = new Logger('WP_LIGHTNING_LOGGER');
		$date = date('Y-m-d');
		$this->logger->pushHandler(new StreamHandler(trailingslashit(wp_upload_dir()['basedir']). "wp-lightning-logs/{$date}.log", Logger::INFO));
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the WP_Lightning_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale()
	{

		$plugin_i18n = new WP_Lightning_i18n();

		$this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');
	}

	/**
	 * Read the options from the database.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function read_database_options()
	{

		$this->database_handler = new LNP_DatabaseHandler();

		$dashboard_page = new LNP_Dashboard($this, 'lnp_settings');
		$balance_page = new LNP_BalancePage($this, 'lnp_settings', $this->database_handler);
		$paywall_page    = new LNP_PaywallPage($this, 'lnp_settings');
		$connection_page = new LNP_ConnectionPage($this, 'lnp_settings');
		$donation_page   = new LNP_DonationPage($this, 'lnp_settings');
		$help_page = new LNP_HelpPage($this, 'lnp_settings');

		// get page options
		$this->connection_options = $connection_page->options;
		$this->paywall_options    = $paywall_page->options;
		// Set the amount key as well to paywall_amount to override from shortcode
		$this->paywall_options['amount'] = $this->paywall_options['paywall_amount'];
		$this->donation_options   = $donation_page->options;
	}

	/**
	 * Setup the lightning client
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function setup_client()
	{

		$this->lightningClient = null;
		$this->lightningClientType = null;

		if (!$this->lightningClient) {
			if (!empty($this->connection_options['lnd_address'])) {
				$this->lightningClientType = 'lnd';
				$this->lightningClient = new WP_Lightning_LND_Client($this->connection_options);
			} elseif (!empty($this->connection_options['lnbits_apikey'])) {
				$this->lightningClientType = 'lnbits';
				$this->lightningClient = new WP_Lightning_LNBits_Client($this->connection_options);
			} elseif (!empty($this->connection_options['lnaddress_address']) || !empty($this->connection_options['lnaddress_lnurl'])) {
				$this->lightningClientType = 'lnaddress';
				$this->lightningClient = new WP_Lightning_LNAddress_Client($this->connection_options);
			} elseif (!empty($this->connection_options['btcpay_host'])) {
				$this->lightningClientType = 'btcpay';
				$this->lightningClient = new WP_Lightning_BTCPay_Client($this->connection_options);
			} elseif (!empty($this->connection_options['lndhub_url']) && !empty($this->connection_options['lndhub_login']) && !empty($this->connection_options['lndhub_password'])) {
				$this->lightningClientType = 'lndhub';
				$this->lightningClient = new WP_Lightning_LNDHub_Client($this->connection_options);
			}
		}
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks()
	{
		// Load the css styles for admin section
		$this->loader->add_action('admin_enqueue_scripts', $this->plugin_admin, 'enqueue_styles');
		// Load the js scripts for admin section
		$this->loader->add_action('admin_enqueue_scripts', $this->plugin_admin, 'enqueue_scripts');
		// Add the WP Lightning menu to the Wordpress Dashboard
		$this->loader->add_action('admin_menu', $this->plugin_admin, 'lightning_menu');
		// Register the donation block
		$this->loader->add_action('init', $this->plugin_admin, 'init_donation_block');
		// Register the subscription widget
		$this->loader->add_action('widgets_init', $this->plugin_admin, 'widget_init');
		// Register the paywall block
		$this->loader->add_action('init', $this->plugin_admin, 'init_paywall_block');
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks()
	{
		$donation_widget = new LNP_DonationsWidget($this);

		// Load the css styles for frotnend
		$this->loader->add_action('wp_enqueue_scripts', $this->plugin_public, 'enqueue_styles');
		// Load the js scripts for frotnend
		$this->loader->add_action('wp_enqueue_scripts', $this->plugin_public, 'enqueue_scripts');
		// Add the lightning meta tag to the head of the page
		$this->loader->add_action('wp_head', $this->plugin_public, 'hook_meta_tags');

		// For RSS Feed
		if (!empty($this->paywall_options['lnurl_rss'])) {
			// Add URL as RSS Item
			$this->loader->add_action('rss2_item', $this->plugin_public, 'add_lnurl_to_rss_item_filter');
		}

		// Apply Paywall to the content
		$this->loader->add_filter('the_content', $this->plugin_public, 'ln_paywall_filter');
		// Add donation widget to the content
		$this->loader->add_filter('the_content', $donation_widget, 'set_donation_box');

		remove_filter('the_content', 'wptexturize');
	}

	/**
	 * Register all of the shortcodes.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_shortcodes()
	{
		// Register shortcode for donation block
		$this->loader->add_shortcode('alby_donation_block', $this->plugin_public, 'sc_alby_donation_block');
	}

	/**
	 * Initialize REST API.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function initialize_rest_api()
	{
		$server = LNP_RESTServer::instance();
		$server->set_plugin_instance($this);
		$server->init();
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run()
	{
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name()
	{
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    WP_Lightning_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader()
	{
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version()
	{
		return $this->version;
	}

	/**
	 * Get the lightning client
	 */
	public function getLightningClient()
	{
		return $this->lightningClient;
	}

	/**
	 * Get the lightning client type
	 */
	public function getLightningClientType()
	{
		return $this->lightningClientType;
	}

	/**
	 * Get the database handler
	 */
	public function getDatabaseHandler()
	{
		return $this->database_handler;
	}

	/**
	 * Get the connection options
	 */
	public function getConnectionOptions()
	{
		return $this->connection_options;
	}

	/**
	 * Get the paywall options
	 */
	public function getPaywallOptions()
	{
		return $this->paywall_options;
	}

	/**
	 * Get the donation options
	 */
	public function getDonationOptions()
	{
		return $this->donation_options;
	}

	/**
	 * Retrieve the Monolog logger
	 * @return Logger
	 */
	public function get_logger()
	{
		return $this->logger;
	}

    public static function get_paid_post_ids()
    {
        $wplnp = null;
        if (isset($_COOKIE['wplnp'])) {
            $wplnp = $_COOKIE['wplnp'];
        } elseif (isset($_GET['wplnp'])) {
            $wplnp = $_GET['wplnp'];
        }
        if (empty($wplnp)) return [];
        try {
            $jwt = JWT\JWT::decode($wplnp, new JWT\Key(WP_LN_PAYWALL_JWT_KEY, WP_LN_PAYWALL_JWT_ALGORITHM));
            $paid_post_ids = $jwt->{'post_ids'};
            if (!is_array($paid_post_ids)) return [];

            return $paid_post_ids;
        } catch (Exception $e) {
            //setcookie("wplnp", "", time() - 3600, '/'); // delete invalid JWT cookie
            return [];
        }
    }

    public static function has_paid_for_post($post_id)
    {
        $paid_post_ids = self::get_paid_post_ids();
        return in_array($post_id, $paid_post_ids);
    }

    /**
     * Store the post_id in an cookie to remember the payment
     * and increment the paid amount on the post
     * must only be called once (can be exploited currently)
     */
    public static function save_as_paid($post_id, $amount_paid = 0)
    {
        $paid_post_ids = self::get_paid_post_ids();
        if (!in_array($post_id, $paid_post_ids)) {
            $amount_received = get_post_meta($post_id, '_lnp_amount_received', true);
            if (is_numeric($amount_received)) {
                $amount = $amount_received + $amount_paid;
            } else {
                $amount = $amount_paid;
            }
            update_post_meta($post_id, '_lnp_amount_received', $amount);

            array_push($paid_post_ids, $post_id);
        }
        $jwt = JWT\JWT::encode(array('post_ids' => $paid_post_ids), WP_LN_PAYWALL_JWT_KEY, WP_LN_PAYWALL_JWT_ALGORITHM);
        setcookie('wplnp', $jwt, time() + time() + 60 * 60 * 24 * 180, '/');
    }

}
