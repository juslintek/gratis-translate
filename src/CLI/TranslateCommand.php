<?php declare(strict_types=1);
namespace Gratis\Translate\CLI;

final class TranslateCommand
{
    /**
     * List available languages.
     * @when after_wp_load
     */
    public function languages($args, $assoc_args): void
    {
        $terms = get_terms(['taxonomy' => 'language', 'hide_empty' => false]);
        $rows = array_map(fn($t) => ['Code' => $t->slug, 'Name' => $t->name, 'Posts' => $t->count], $terms);
        \WP_CLI\Utils\format_items('table', $rows, ['Code', 'Name', 'Posts']);
    }

    /**
     * Link a translation to an original post.
     * <post_id> : Translation post ID.
     * <original_id> : Original post ID.
     * <lang> : Language code.
     * @when after_wp_load
     */
    public function link($args, $assoc_args): void
    {
        $post_id = (int) $args[0];
        $original_id = (int) $args[1];
        $lang = $args[2];
        update_post_meta($post_id, '_translation_of', $original_id);
        wp_set_object_terms($post_id, [$lang], 'language');
        \WP_CLI::success("Post #{$post_id} linked as {$lang} translation of #{$original_id}");
    }
}
