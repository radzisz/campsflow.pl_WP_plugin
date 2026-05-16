<?php
declare(strict_types=1);

namespace Campsflow\Taxonomy;

use Campsflow\PostType\EventPostType;

final class AgeGroupTaxonomy
{
    public const SLUG = 'cf_age_group';

    public function register(): void
    {
        add_action('init', [$this, 'registerTaxonomy']);
    }

    public function registerTaxonomy(): void
    {
        register_taxonomy(self::SLUG, [EventPostType::SLUG], [
            'labels' => [
                'name'          => __('Grupy wiekowe', 'campsflow'),
                'singular_name' => __('Grupa wiekowa', 'campsflow'),
                'all_items'     => __('Wszystkie grupy', 'campsflow'),
                'add_new_item'  => __('Dodaj grupę', 'campsflow'),
            ],
            'public'            => true,
            'show_ui'           => true,
            'show_in_rest'      => true,
            'hierarchical'      => true,
            'rewrite'           => ['slug' => 'wiek'],
            'show_admin_column' => true,
            'capabilities'      => [
                'manage_terms' => 'do_not_allow',
                'edit_terms'   => 'do_not_allow',
                'delete_terms' => 'do_not_allow',
                'assign_terms' => 'edit_posts',
            ],
        ]);
    }
}
