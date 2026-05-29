<?php
/**
 * Uninstall cleanup.
 *
 * @package AEO_Kit
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Remove plugin options.
delete_option( 'aeo_kit_settings' );
delete_option( 'aeo_kit_flush_needed' );

// Remove cached llms.txt.
delete_transient( 'aeo_kit_llms_txt' );

// Note: per-post meta (_aeo_summary, _aeo_faq) is intentionally left in place
// so content is preserved if the plugin is reinstalled. Uncomment to purge:
//
// global $wpdb;
// $wpdb->query( "DELETE FROM {$wpdb->postmeta} WHERE meta_key IN ('_aeo_summary','_aeo_faq')" );
