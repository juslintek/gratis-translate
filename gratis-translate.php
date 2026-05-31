<?php
/**
 * Plugin Name: Gratis Translate
 * Description: Multilingual content management. Replaces WPML ($39/yr), Polylang Pro ($99/yr).
 * Version: 1.0.0
 * Requires PHP: 8.1
 * Author: Linas Jusys
 * License: GPL-2.0-or-later
 */
defined('ABSPATH') || exit;
require_once __DIR__ . '/autoload.php';
add_action('init', [\Gratis\Translate\Multilingual::class, 'register']);
add_action('plugins_loaded', function () {
    if (defined('WP_CLI') && WP_CLI) \WP_CLI::add_command('gratis-translate', \Gratis\Translate\CLI\TranslateCommand::class);
});
