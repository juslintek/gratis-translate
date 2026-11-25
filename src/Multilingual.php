<?php declare(strict_types=1);

namespace Gratis\Translate;

defined('ABSPATH') || exit;

/**
 * Language taxonomy, translation linking, switcher shortcode and hreflang tags.
 */
final class Multilingual {

    public const TAXONOMY = 'language';

    /** Default languages seeded on first run: slug => label. */
    private const DEFAULT_LANGS = [
        'en' => 'English',
        'lt' => 'Lithuanian',
        'es' => 'Spanish',
        'fr' => 'French',
        'de' => 'German',
    ];

    /** Register the language taxonomy, meta, shortcode and hreflang output. */
    public static function register(): void {
        register_taxonomy(self::TAXONOMY, ['post', 'page'], [
            'labels'       => [
                'name'          => __('Languages', 'gratis-translate'),
                'singular_name' => __('Language', 'gratis-translate'),
            ],
            'public'       => false,
            'show_ui'      => true,
            'show_in_rest' => true,
            'hierarchical' => false,
        ]);

        foreach (['post', 'page'] as $type) {
            register_post_meta($type, '_translation_of', [
                'type'              => 'integer',
                'single'            => true,
                'show_in_rest'      => true,
                'sanitize_callback' => 'absint',
                'auth_callback'     => static fn() => current_user_can('edit_posts'),
            ]);
        }

        add_shortcode('gratis-language-switcher', [__CLASS__, 'switcher']);
        add_action('wp_head', [__CLASS__, 'hreflang']);

        self::seedDefaults();
    }

    /** Seed the default language terms once. */
    private static function seedDefaults(): void {
        foreach (self::DEFAULT_LANGS as $slug => $label) {
            if (!term_exists($slug, self::TAXONOMY)) {
                wp_insert_term($label, self::TAXONOMY, ['slug' => $slug]);
            }
        }
    }

    /** `[gratis-language-switcher]` renders links to a post's translations. */
    public static function switcher(): string {
        if (!is_singular()) {
            return '';
        }
        $translations = self::getTranslations((int) get_the_ID());
        if (empty($translations)) {
            return '';
        }

        $current = get_the_ID();
        $html    = '<nav class="gratis-lang-switcher" aria-label="' . esc_attr__('Language', 'gratis-translate') . '">';
        foreach ($translations as $lang => $trans_id) {
            $url    = get_permalink($trans_id);
            $label  = esc_html(strtoupper($lang));
            $active = (int) $trans_id === (int) $current;
            if (!$url) {
                continue;
            }
            $html .= '<a href="' . esc_url($url) . '" hreflang="' . esc_attr($lang) . '"'
                . ($active ? ' aria-current="page"' : '') . '>' . $label . '</a> ';
        }
        $html .= '</nav>';
        return $html;
    }

    /** Emit `<link rel="alternate" hreflang>` tags for singular content. */
    public static function hreflang(): void {
        if (!is_singular() || !Settings::hreflangEnabled()) {
            return;
        }
        $translations = self::getTranslations((int) get_the_ID());
        foreach ($translations as $lang => $trans_id) {
            $url = get_permalink($trans_id);
            if (!$url) {
                continue;
            }
            echo '<link rel="alternate" hreflang="' . esc_attr($lang) . '" href="' . esc_url($url) . '">' . "\n";
        }
    }

    /**
     * Resolve the set of translations for a post, keyed by language slug.
     *
     * @return array<string,int>
     */
    public static function getTranslations(int $postId): array {
        $original = (int) get_post_meta($postId, '_translation_of', true);
        $original = $original > 0 ? $original : $postId;

        // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- translation grouping is the intended query.
        $args = [
            'post_type'      => get_post_type($postId) ?: 'post',
            'posts_per_page' => 50, // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page -- bounded set of translations.
            'post_status'    => 'publish',
            // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- bounded translation-group lookup.
            'meta_query'     => [['key' => '_translation_of', 'value' => $original]],
        ];
        $all = get_posts($args);

        $translations = [];
        foreach ($all as $p) {
            $langs = wp_get_object_terms($p->ID, self::TAXONOMY, ['fields' => 'slugs']);
            if (!is_wp_error($langs) && !empty($langs)) {
                $translations[$langs[0]] = (int) $p->ID;
            }
        }

        $origLangs = wp_get_object_terms($original, self::TAXONOMY, ['fields' => 'slugs']);
        if (!is_wp_error($origLangs) && !empty($origLangs)) {
            $translations[$origLangs[0]] = $original;
        }

        return $translations;
    }

    /**
     * All registered languages, keyed by slug.
     *
     * @return array<string,string>
     */
    public static function languages(): array {
        $terms = get_terms(['taxonomy' => self::TAXONOMY, 'hide_empty' => false]);
        if (is_wp_error($terms) || empty($terms)) {
            return [];
        }
        $out = [];
        foreach ($terms as $term) {
            $out[$term->slug] = $term->name;
        }
        return $out;
    }
}
