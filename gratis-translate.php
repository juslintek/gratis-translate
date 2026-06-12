<?php
/**
 * Plugin Name: Gratis Translate
 * Plugin URI: https://github.com/juslintek/gratis-translate
 * Description: Multilingual content management with language taxonomy, switcher and hreflang. Replaces WPML ($39/yr), Polylang Pro ($99/yr).
 * Version: 1.0.0
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * Author: Linas Jusys
 * Author URI: https://github.com/juslintek
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: gratis-translate
 *
 * @package Gratis\Translate
 */

defined('ABSPATH') || exit;

define('GRATIS_TRANSLATE_VERSION', '1.0.0');
define('GRATIS_TRANSLATE_FILE', __FILE__);
define('GRATIS_TRANSLATE_DIR', plugin_dir_path(__FILE__));
define('GRATIS_TRANSLATE_URL', plugin_dir_url(__FILE__));

require_once __DIR__ . '/autoload.php';

add_action('init', [\Gratis\Translate\Multilingual::class, 'register']);

add_action('add_meta_boxes', [\Gratis\Translate\MetaBox::class, 'register']);
add_action('save_post', [\Gratis\Translate\MetaBox::class, 'save'], 10, 2);

add_action('admin_menu', [\Gratis\Translate\Settings::class, 'register_menu']);
add_action('admin_init', [\Gratis\Translate\Settings::class, 'register_settings']);

add_action('plugins_loaded', function () {
    if (defined('WP_CLI') && WP_CLI) {
        \WP_CLI::add_command('gratis-translate', \Gratis\Translate\CLI\TranslateCommand::class);
    }
});
