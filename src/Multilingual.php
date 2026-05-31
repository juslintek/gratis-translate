<?php declare(strict_types=1);
namespace Gratis\Translate;

final class Multilingual
{
    public static function register(): void
    {
        // Register language taxonomy for posts and pages
        register_taxonomy('language', ['post', 'page'], [
            'labels' => ['name' => 'Languages'], 'public' => false,
            'show_ui' => true, 'show_in_rest' => true, 'hierarchical' => false,
        ]);

        // Register translation links meta
        register_post_meta('post', '_translation_of', ['type' => 'integer', 'single' => true, 'show_in_rest' => true]);
        register_post_meta('page', '_translation_of', ['type' => 'integer', 'single' => true, 'show_in_rest' => true]);

        // Language switcher shortcode
        add_shortcode('gratis-language-switcher', [__CLASS__, 'switcher']);

        // Add hreflang tags
        add_action('wp_head', [__CLASS__, 'hreflang']);

        // Ensure default languages exist
        if (!term_exists('en', 'language')) {
            wp_insert_term('English', 'language', ['slug' => 'en']);
            wp_insert_term('Lithuanian', 'language', ['slug' => 'lt']);
            wp_insert_term('Spanish', 'language', ['slug' => 'es']);
            wp_insert_term('French', 'language', ['slug' => 'fr']);
            wp_insert_term('German', 'language', ['slug' => 'de']);
        }
    }

    public static function switcher(): string
    {
        if (!is_singular()) return '';
        $post_id = get_the_ID();
        $translations = self::getTranslations($post_id);
        if (empty($translations)) return '';

        $html = '<div class="gratis-lang-switcher">';
        foreach ($translations as $lang => $trans_id) {
            $html .= '<a href="' . get_permalink($trans_id) . '" hreflang="' . esc_attr($lang) . '">' . strtoupper($lang) . '</a> ';
        }
        $html .= '</div>';
        return $html;
    }

    public static function hreflang(): void
    {
        if (!is_singular()) return;
        $translations = self::getTranslations(get_the_ID());
        foreach ($translations as $lang => $trans_id) {
            echo '<link rel="alternate" hreflang="' . esc_attr($lang) . '" href="' . esc_url(get_permalink($trans_id)) . '">' . "\n";
        }
    }

    public static function getTranslations(int $postId): array
    {
        $original = (int) get_post_meta($postId, '_translation_of', true) ?: $postId;
        $all = get_posts(['post_type' => get_post_type($postId), 'numberposts' => -1,
            'meta_query' => [['key' => '_translation_of', 'value' => $original]]]);

        $translations = [];
        foreach ($all as $p) {
            $langs = wp_get_object_terms($p->ID, 'language', ['fields' => 'slugs']);
            if (!empty($langs)) $translations[$langs[0]] = $p->ID;
        }
        // Add original
        $origLangs = wp_get_object_terms($original, 'language', ['fields' => 'slugs']);
        if (!empty($origLangs)) $translations[$origLangs[0]] = $original;

        return $translations;
    }
}
