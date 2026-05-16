<?php
declare(strict_types=1);

namespace Campsflow\PostType;

final class SessionPostType
{
    public const SLUG = 'cf_session';

    public function register(): void
    {
        add_action('init', [$this, 'registerPostType']);
        add_action('current_screen', [$this, 'blockNewPostScreen']);
    }

    public function registerPostType(): void
    {
        register_post_type(self::SLUG, [
            'labels' => [
                'name'          => __('Turnusy', 'campsflow'),
                'singular_name' => __('Turnus', 'campsflow'),
                'all_items'     => __('Wszystkie turnusy', 'campsflow'),
                'search_items'  => __('Szukaj turnusów', 'campsflow'),
                'not_found'     => __('Nie znaleziono turnusów.', 'campsflow'),
            ],
            'public'            => true,
            'show_ui'           => true,
            'show_in_menu'      => 'edit.php?post_type=' . EventPostType::SLUG,
            'show_in_nav_menus' => true,
            'show_in_rest'      => true,
            'supports'          => ['title'],
            'has_archive'       => false,
            'rewrite'           => ['slug' => 'turnus', 'with_front' => false],
            'capability_type'   => 'post',
            'capabilities'      => [
                'create_posts' => 'do_not_allow',
            ],
            'map_meta_cap'  => true,
            'hierarchical'  => false,
        ]);
    }

    public function blockNewPostScreen(\WP_Screen $screen): void
    {
        if ($screen->post_type === self::SLUG && $screen->action === 'add') {
            wp_redirect(admin_url('edit.php?post_type=' . EventPostType::SLUG));
            exit;
        }
    }
}
