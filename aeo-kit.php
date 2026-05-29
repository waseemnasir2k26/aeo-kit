<?php
/**
 * Plugin Name:       AEO Kit — Answer Engine Optimization
 * Plugin URI:        https://github.com/waseemnasir2k26/aeo-kit
 * Description:       Make your WordPress site quotable by AI answer engines (ChatGPT, Claude, Perplexity, Gemini). Generates an on-site /llms.txt, rich JSON-LD schema (Organization, Article, Breadcrumb, FAQ, HowTo), and citation-ready summaries. 100% local — no external API, no cloud, no per-use cost.
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            Waseem Nasir (SkynetLabs)
 * Author URI:        https://www.skynetjoe.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       aeo-kit
 * Domain Path:       /languages
 *
 * @package AEO_Kit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // No direct access.
}

define( 'AEO_KIT_VERSION', '1.0.0' );
define( 'AEO_KIT_FILE', __FILE__ );
define( 'AEO_KIT_DIR', plugin_dir_path( __FILE__ ) );
define( 'AEO_KIT_URL', plugin_dir_url( __FILE__ ) );
define( 'AEO_KIT_BASENAME', plugin_basename( __FILE__ ) );

require_once AEO_KIT_DIR . 'includes/class-aeo-kit.php';

/**
 * Boot the plugin.
 *
 * @return AEO_Kit
 */
function aeo_kit() {
	return AEO_Kit::instance();
}

aeo_kit();

/**
 * Activation: register rewrite rule for /llms.txt then flush.
 */
register_activation_hook(
	__FILE__,
	function () {
		require_once AEO_KIT_DIR . 'includes/class-aeo-llms-txt.php';
		AEO_Kit_LLMS_Txt::add_rewrite_rules();
		flush_rewrite_rules();
		// Belt-and-braces: also schedule an init flush for multisite subsites
		// and git-drop installs where this hook may not cover every context.
		update_option( 'aeo_kit_flush_needed', 1, false );
	}
);

/**
 * Deactivation: flush rewrite rules so /llms.txt stops resolving.
 */
register_deactivation_hook(
	__FILE__,
	function () {
		flush_rewrite_rules();
	}
);
