<?php
declare(strict_types=1);

namespace Campsflow\Presentation;

/**
 * Registers Elementor widget and category.
 * Only activates when Elementor is loaded — safe to include unconditionally.
 */
final class ElementorIntegration
{
    public function register(): void
    {
        add_action('elementor/widgets/register', [$this, 'registerWidget']);
        add_action('elementor/elements/categories_registered', [$this, 'registerCategory']);
    }

    public function registerWidget(\Elementor\Widgets_Manager $manager): void
    {
        $manager->register(new ElementorWidget());
        $manager->register(new EventMetaWidget());
        $manager->register(new EventSessionsWidget());
    }

    public function registerCategory(\Elementor\Elements_Manager $manager): void
    {
        $manager->add_category('campsflow', [
            'title' => __('CampsFlow', 'campsflow'),
            'icon'  => 'fa fa-flag',
        ]);
    }
}
