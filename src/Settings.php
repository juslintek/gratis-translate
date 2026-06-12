<?php declare(strict_types=1);

namespace Gratis\Translate;

defined('ABSPATH') || exit;

/**
 * Global settings for Gratis Translate.
 */
final class Settings {

    private const GROUP = 'gratis_translate_settings_group';
    private const PAGE  = 'gratis-translate-settings';

    private const OPT_DEFAULT = 'gratis_translate_default_lang';
    private const OPT_HREFLANG = 'gratis_translate_hreflang';

    /** Register the settings submenu under the Languages taxonomy. */
    public static function register_menu(): void {
        add_submenu_page(
            'edit-tags.php?taxonomy=' . Multilingual::TAXONOMY,
            __('Translate Settings', 'gratis-translate'),
            __('Settings', 'gratis-translate'),
            'manage_options',
            self::PAGE,
            [__CLASS__, 'render_page']
        );
        // Also surface under Settings for discoverability.
        add_options_page(
            __('Translate Settings', 'gratis-translate'),
            __('Translate', 'gratis-translate'),
            'manage_options',
            self::PAGE,
            [__CLASS__, 'render_page']
        );
    }

    /** Register options. */
    public static function register_settings(): void {
        register_setting(self::GROUP, self::OPT_DEFAULT, [
            'type'              => 'string',
            'sanitize_callback' => 'sanitize_key',
            'default'           => 'en',
        ]);
        register_setting(self::GROUP, self::OPT_HREFLANG, [
            'type'              => 'boolean',
            'sanitize_callback' => static fn($v) => (bool) $v,
            'default'           => true,
        ]);
    }

    /** Default site language slug. */
    public static function defaultLang(): string {
        return (string) get_option(self::OPT_DEFAULT, 'en');
    }

    /** Whether hreflang tags are emitted. */
    public static function hreflangEnabled(): bool {
        return (bool) get_option(self::OPT_HREFLANG, true);
    }

    /** Render the settings screen. */
    public static function render_page(): void {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have permission to access this page.', 'gratis-translate'));
        }

        $languages = Multilingual::languages();

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Translate Settings', 'gratis-translate') . '</h1>';
        echo '<form method="post" action="options.php">';
        settings_fields(self::GROUP);
        echo '<table class="form-table" role="presentation"><tbody>';

        echo '<tr><th scope="row"><label for="gt_default">' . esc_html__('Default language', 'gratis-translate') . '</label></th><td>';
        echo '<select id="gt_default" name="' . esc_attr(self::OPT_DEFAULT) . '">';
        $current = self::defaultLang();
        foreach ($languages as $slug => $label) {
            echo '<option value="' . esc_attr($slug) . '"' . selected($current, $slug, false) . '>'
                . esc_html($label) . ' (' . esc_html($slug) . ')</option>';
        }
        echo '</select></td></tr>';

        echo '<tr><th scope="row">' . esc_html__('hreflang tags', 'gratis-translate') . '</th><td>';
        echo '<label><input type="checkbox" name="' . esc_attr(self::OPT_HREFLANG) . '" value="1"' . checked(self::hreflangEnabled(), true, false) . '> ';
        echo esc_html__('Output alternate-language link tags in the document head.', 'gratis-translate') . '</label></td></tr>';

        echo '</tbody></table>';
        submit_button(__('Save settings', 'gratis-translate'));
        echo '</form>';

        echo '<hr><h2>' . esc_html__('Usage', 'gratis-translate') . '</h2>';
        echo '<p>' . esc_html__('Assign a language to each post/page and link translations via the Translation meta box. Add the switcher anywhere with:', 'gratis-translate') . '</p>';
        echo '<p><code>[gratis-language-switcher]</code></p>';
        echo '</div>';
    }
}
