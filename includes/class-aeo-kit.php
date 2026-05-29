<?php
/**
 * Core loader.
 *
 * @package AEO_Kit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin class. Singleton.
 */
final class AEO_Kit {

	/**
	 * Single instance.
	 *
	 * @var AEO_Kit|null
	 */
	private static $instance = null;

	/**
	 * Default settings.
	 *
	 * @var array
	 */
	const DEFAULTS = array(
		'org_name'        => '',
		'org_logo'        => '',
		'org_type'        => 'Organization',
		'social_profiles' => '',
		'enable_org'      => 1,
		'enable_website'  => 1,
		'enable_article'  => 1,
		'enable_breadcrumb' => 1,
		'enable_faq'      => 1,
		'enable_llms'     => 1,
		'llms_post_types' => array( 'page', 'post' ),
	);

	/**
	 * Get the singleton.
	 *
	 * @return AEO_Kit
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor wires everything.
	 */
	private function __construct() {
		$this->includes();
		$this->hooks();
	}

	/**
	 * Load component classes.
	 */
	private function includes() {
		require_once AEO_KIT_DIR . 'includes/class-aeo-settings.php';
		require_once AEO_KIT_DIR . 'includes/class-aeo-schema.php';
		require_once AEO_KIT_DIR . 'includes/class-aeo-llms-txt.php';
		require_once AEO_KIT_DIR . 'includes/class-aeo-faq.php';
		require_once AEO_KIT_DIR . 'includes/class-aeo-meta-box.php';
	}

	/**
	 * Register hooks and instantiate components.
	 */
	private function hooks() {
		add_action( 'init', array( $this, 'load_textdomain' ) );
		add_filter( 'plugin_action_links_' . AEO_KIT_BASENAME, array( $this, 'action_links' ) );

		new AEO_Kit_Settings();
		new AEO_Kit_Schema();
		new AEO_Kit_LLMS_Txt();
		new AEO_Kit_FAQ();
		new AEO_Kit_Meta_Box();
	}

	/**
	 * Load translations.
	 */
	public function load_textdomain() {
		load_plugin_textdomain( 'aeo-kit', false, dirname( AEO_KIT_BASENAME ) . '/languages' );
	}

	/**
	 * Add a "Settings" link on the plugins screen.
	 *
	 * @param array $links Existing links.
	 * @return array
	 */
	public function action_links( $links ) {
		$url  = admin_url( 'options-general.php?page=aeo-kit' );
		$link = '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'aeo-kit' ) . '</a>';
		array_unshift( $links, $link );
		return $links;
	}

	/**
	 * Read a setting with default fallback.
	 *
	 * @param string $key     Setting key.
	 * @param mixed  $default Optional override default.
	 * @return mixed
	 */
	public static function get_setting( $key, $default = null ) {
		$settings = get_option( 'aeo_kit_settings', array() );
		if ( isset( $settings[ $key ] ) ) {
			return $settings[ $key ];
		}
		if ( null !== $default ) {
			return $default;
		}
		return isset( self::DEFAULTS[ $key ] ) ? self::DEFAULTS[ $key ] : '';
	}
}
