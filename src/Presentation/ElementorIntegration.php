<?php
declare(strict_types=1);

namespace Campsflow\Presentation;

/**
 * Registers Elementor widget and category.
 * Only activates when Elementor is loaded — safe to include unconditionally.
 */
final class ElementorIntegration {

	public function register(): void {
		add_action( 'elementor/widgets/register', array( $this, 'registerWidget' ) );
		add_action( 'elementor/elements/categories_registered', array( $this, 'registerCategory' ) );
	}

	public function registerWidget( \Elementor\Widgets_Manager $manager ): void {
		$manager->register( new EventSessionsWidget() );
		$manager->register( new EventBreadcrumbWidget() );
		$manager->register( new EventFieldWidget() );
		$manager->register( new EventContactWidget() );
		$manager->register( new EventDocumentsWidget() );
		$manager->register( new EventLeadImageWidget() );
		$manager->register( new EventLeadVideoWidget() );
		$manager->register( new EventGalleryWidget() );
		$manager->register( new EventTagsWidget() );
		$manager->register( new SearchFilterWidget() );
		$manager->register( new SearchFilterFieldWidget() );
		$manager->register( new SearchSortWidget() );
		$manager->register( new SearchResultsWidget() );
		$manager->register( new EventMapWidget() );
	}

	public function registerCategory( \Elementor\Elements_Manager $manager ): void {
		$manager->add_category(
			'campsflow_search',
			array(
				'title' => __( 'CampsFlow — Wyszukiwanie', 'campsflow' ),
				'icon'  => 'fa fa-search',
			)
		);
		$manager->add_category(
			'campsflow_event',
			array(
				'title' => __( 'CampsFlow — Wydarzenie', 'campsflow' ),
				'icon'  => 'fa fa-flag',
			)
		);
	}
}
