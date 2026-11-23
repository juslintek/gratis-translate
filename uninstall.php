<?php
/**
 * Uninstall gratis-translate.
 *
 * Removes all plugin data: options, custom tables, and scheduled events.
 */

defined('WP_UNINSTALL_PLUGIN') || exit;

global $wpdb;

// Remove plugin options.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", 'gratis_translate_%'));

// Remove language taxonomy terms.
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$wpdb->query("DELETE tt, t FROM {$wpdb->term_taxonomy} tt JOIN {$wpdb->terms} t ON t.term_id = tt.term_id WHERE tt.taxonomy = 'gratis_language'");
