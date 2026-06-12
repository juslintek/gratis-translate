<?php declare(strict_types=1);

namespace Gratis\Translate;

defined('ABSPATH') || exit;

/**
 * Translation meta box: assign a language and link to an original post.
 */
final class MetaBox {

    private const NONCE_ACTION = 'gratis_translate_save_meta';
    private const NONCE_FIELD  = 'gratis_translate_meta_nonce';

    /** Register the meta box on translatable post types. */
    public static function register(): void {
        foreach (['post', 'page'] as $type) {
            add_meta_box(
                'gratis-translate-link',
                __('Translation', 'gratis-translate'),
                [__CLASS__, 'render'],
                $type,
                'side',
                'default'
            );
        }
    }

    /** Render the meta box. */
    public static function render(\WP_Post $post): void {
        wp_nonce_field(self::NONCE_ACTION, self::NONCE_FIELD);

        $current_lang = '';
        $terms        = wp_get_object_terms($post->ID, Multilingual::TAXONOMY, ['fields' => 'slugs']);
        if (!is_wp_error($terms) && !empty($terms)) {
            $current_lang = (string) $terms[0];
        }
        $original = (int) get_post_meta($post->ID, '_translation_of', true);

        echo '<p><label for="gt_lang"><strong>' . esc_html__('Language', 'gratis-translate') . '</strong></label><br>';
        echo '<select id="gt_lang" name="gt_lang" class="widefat">';
        echo '<option value="">' . esc_html__('— none —', 'gratis-translate') . '</option>';
        foreach (Multilingual::languages() as $slug => $label) {
            echo '<option value="' . esc_attr($slug) . '"' . selected($current_lang, $slug, false) . '>'
                . esc_html($label) . ' (' . esc_html($slug) . ')</option>';
        }
        echo '</select></p>';

        echo '<p><label for="gt_original"><strong>' . esc_html__('Translation of (post ID)', 'gratis-translate') . '</strong></label><br>';
        echo '<input type="number" min="0" id="gt_original" name="gt_original" class="widefat" value="' . esc_attr((string) $original) . '">';
        echo '<span class="description">' . esc_html__('Leave 0 if this is the original.', 'gratis-translate') . '</span></p>';
    }

    /** Persist the meta box values. */
    public static function save(int $post_id, \WP_Post $post): void {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!in_array($post->post_type, ['post', 'page'], true)) {
            return;
        }
        $nonce = isset($_POST[self::NONCE_FIELD])
            ? sanitize_text_field(wp_unslash($_POST[self::NONCE_FIELD]))
            : '';
        if ('' === $nonce || !wp_verify_nonce($nonce, self::NONCE_ACTION)) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // phpcs:disable WordPress.Security.NonceVerification.Missing -- nonce verified above.
        $lang     = isset($_POST['gt_lang']) ? sanitize_key(wp_unslash($_POST['gt_lang'])) : '';
        $original = isset($_POST['gt_original']) ? absint(wp_unslash($_POST['gt_original'])) : 0;
        // phpcs:enable WordPress.Security.NonceVerification.Missing

        if ('' !== $lang && term_exists($lang, Multilingual::TAXONOMY)) {
            wp_set_object_terms($post_id, [$lang], Multilingual::TAXONOMY);
        } else {
            wp_set_object_terms($post_id, [], Multilingual::TAXONOMY);
        }

        if ($original > 0 && $original !== $post_id) {
            update_post_meta($post_id, '_translation_of', $original);
        } else {
            delete_post_meta($post_id, '_translation_of');
        }
    }
}
