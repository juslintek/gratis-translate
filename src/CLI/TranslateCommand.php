<?php declare(strict_types=1);

namespace Gratis\Translate\CLI;

use Gratis\Translate\Multilingual;

defined('ABSPATH') || exit;

/**
 * WP-CLI helpers for Gratis Translate.
 */
final class TranslateCommand {

    /**
     * List available languages.
     *
     * @when after_wp_load
     *
     * @param array<int,string>    $args       Positional args (unused).
     * @param array<string,string> $assoc_args Associative args (unused).
     */
    public function languages($args, $assoc_args): void {
        unset($args, $assoc_args);
        $terms = get_terms(['taxonomy' => Multilingual::TAXONOMY, 'hide_empty' => false]);
        if (is_wp_error($terms) || empty($terms)) {
            \WP_CLI::log('No languages.');
            return;
        }
        $rows = array_map(static fn($t) => [
            'Code'  => $t->slug,
            'Name'  => $t->name,
            'Posts' => $t->count,
        ], $terms);
        \WP_CLI\Utils\format_items('table', $rows, ['Code', 'Name', 'Posts']);
    }

    /**
     * Link a translation to an original post.
     *
     * ## OPTIONS
     *
     * <post_id>
     * : Translation post ID.
     *
     * <original_id>
     * : Original post ID.
     *
     * <lang>
     * : Language code.
     *
     * @when after_wp_load
     *
     * @param array<int,string>    $args       Positional args.
     * @param array<string,string> $assoc_args Associative args (unused).
     */
    public function link($args, $assoc_args): void {
        unset($assoc_args);
        $post_id     = (int) ($args[0] ?? 0);
        $original_id = (int) ($args[1] ?? 0);
        $lang        = sanitize_key((string) ($args[2] ?? ''));

        if ($post_id <= 0 || $original_id <= 0 || '' === $lang) {
            \WP_CLI::error('Usage: wp gratis-translate link <post_id> <original_id> <lang>');
        }
        if (!term_exists($lang, Multilingual::TAXONOMY)) {
            \WP_CLI::error("Unknown language code: {$lang}");
        }

        update_post_meta($post_id, '_translation_of', $original_id);
        wp_set_object_terms($post_id, [$lang], Multilingual::TAXONOMY);
        \WP_CLI::success("Post #{$post_id} linked as {$lang} translation of #{$original_id}");
    }
}
