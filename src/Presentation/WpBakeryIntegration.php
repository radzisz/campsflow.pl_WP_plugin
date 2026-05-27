<?php
declare(strict_types=1);

namespace Campsflow\Presentation;

/**
 * WPBakery Page Builder integration.
 * Maps our shortcodes to drag-and-drop elements in the WPBakery editor.
 * Only activates when WPBakery is loaded.
 */
final class WpBakeryIntegration {

	public function register(): void {
		add_action( 'vc_before_init', array( $this, 'mapElements' ) );
	}

	public function mapElements(): void {
		$this->mapListing();
		$this->mapEventMeta();
		$this->mapEventSessions();
		$this->mapEventTags();
		$this->mapEventAgeGroups();
	}

	private function mapListing(): void {
		vc_map(
			array(
				'name'        => __( 'CampsFlow — Lista obozów', 'campsflow' ),
				'base'        => 'campsflow_listing',
				'category'    => 'CampsFlow',
				'icon'        => 'dashicons-flag',
				'description' => __( 'Wyświetla listę imprez lub turnusów z filtrami', 'campsflow' ),
				'params'      => array(
					array(
						'type'        => 'dropdown',
						'heading'     => __( 'Widok', 'campsflow' ),
						'param_name'  => 'view',
						'value'       => array(
							__( 'Lista imprez', 'campsflow' ) => 'events',
							__( 'Lista turnusów (płaska)', 'campsflow' ) => 'sessions',
						),
						'description' => __( 'Imprezowy — z wyborem turnusu. Płaski — bezpośrednie zapisy.', 'campsflow' ),
					),
					array(
						'type'       => 'dropdown',
						'heading'    => __( 'Kolumny', 'campsflow' ),
						'param_name' => 'columns',
						'value'      => array(
							'1' => '1',
							'2' => '2',
							'3' => '3',
							'4' => '4',
						),
						'std'        => '3',
					),
				),
			)
		);
	}

	private function mapEventMeta(): void {
		vc_map(
			array(
				'name'        => __( 'CampsFlow — Szczegóły imprezy', 'campsflow' ),
				'base'        => 'campsflow_event_meta',
				'category'    => 'CampsFlow',
				'icon'        => 'dashicons-info-outline',
				'description' => __( 'Lokalizacja, tagi i opis aktualnej imprezy', 'campsflow' ),
				'params'      => array(
					array(
						'type'       => 'checkbox',
						'heading'    => __( 'Widoczność', 'campsflow' ),
						'param_name' => 'show',
						'value'      => array(
							__( 'Lokalizacja', 'campsflow' ) => 'location',
							__( 'Tagi', 'campsflow' )      => 'tags',
							__( 'Opis', 'campsflow' )      => 'description',
							__( 'Zdjęcia', 'campsflow' )   => 'photos',
							__( 'Program', 'campsflow' )   => 'program',
							__( 'Co zawiera cena', 'campsflow' ) => 'price_include',
							__( 'Dokumenty', 'campsflow' ) => 'documents',
							__( 'Warunki ogólne', 'campsflow' ) => 'terms',
							__( 'Inf. praktyczne', 'campsflow' ) => 'instructions',
							__( 'Kontakt', 'campsflow' )   => 'contact',
							__( 'Pola własne', 'campsflow' ) => 'custom_fields',
						),
						'std'        => 'location,tags,description',
					),
				),
			)
		);
	}

	private function mapEventTags(): void {
		vc_map(
			array(
				'name'        => __( 'CampsFlow — Tagi', 'campsflow' ),
				'base'        => 'campsflow_event_tags',
				'category'    => 'CampsFlow',
				'icon'        => 'dashicons-tag',
				'description' => __( 'Tagi taksonomiczne imprezy jako pillsy', 'campsflow' ),
				'params'      => array(
					array(
						'type'       => 'dropdown',
						'heading'    => __( 'Sortowanie', 'campsflow' ),
						'param_name' => 'sort',
						'value'      => array(
							__( 'Nazwa A→Z', 'campsflow' ) => 'name_asc',
							__( 'Nazwa Z→A', 'campsflow' ) => 'name_desc',
							__( 'Kolejność domyślna', 'campsflow' ) => 'default',
						),
						'std'        => 'name_asc',
					),
					array(
						'type'        => 'textfield',
						'heading'     => __( 'Maksymalna liczba tagów', 'campsflow' ),
						'param_name'  => 'max',
						'value'       => '0',
						'description' => __( '0 = pokaż wszystkie', 'campsflow' ),
					),
					array(
						'type'       => 'textfield',
						'heading'    => __( 'Odstęp między tagami (px)', 'campsflow' ),
						'param_name' => 'gap',
						'value'      => '6',
					),
				),
			)
		);
	}

	private function mapEventAgeGroups(): void {
		vc_map(
			array(
				'name'        => __( 'CampsFlow — Grupy wiekowe', 'campsflow' ),
				'base'        => 'campsflow_event_age_groups',
				'category'    => 'CampsFlow',
				'icon'        => 'dashicons-groups',
				'description' => __( 'Grupy wiekowe imprezy jako pillsy', 'campsflow' ),
				'params'      => array(
					array(
						'type'       => 'dropdown',
						'heading'    => __( 'Sortowanie', 'campsflow' ),
						'param_name' => 'sort',
						'value'      => array(
							__( 'Nazwa A→Z', 'campsflow' ) => 'name_asc',
							__( 'Nazwa Z→A', 'campsflow' ) => 'name_desc',
							__( 'Kolejność domyślna', 'campsflow' ) => 'default',
						),
						'std'        => 'name_asc',
					),
					array(
						'type'        => 'textfield',
						'heading'     => __( 'Maksymalna liczba grup', 'campsflow' ),
						'param_name'  => 'max',
						'value'       => '0',
						'description' => __( '0 = pokaż wszystkie', 'campsflow' ),
					),
					array(
						'type'       => 'textfield',
						'heading'    => __( 'Odstęp między pillsami (px)', 'campsflow' ),
						'param_name' => 'gap',
						'value'      => '6',
					),
				),
			)
		);
	}

	private function mapEventSessions(): void {
		vc_map(
			array(
				'name'        => __( 'CampsFlow — Turnusy', 'campsflow' ),
				'base'        => 'campsflow_event_sessions',
				'category'    => 'CampsFlow',
				'icon'        => 'dashicons-calendar-alt',
				'description' => __( 'Lista terminów z cenami i przyciskami zapisu', 'campsflow' ),
				'params'      => array(
					array(
						'type'       => 'textfield',
						'heading'    => __( 'Nagłówek sekcji', 'campsflow' ),
						'param_name' => 'title',
						'value'      => __( 'Dostępne terminy', 'campsflow' ),
					),
					array(
						'type'       => 'textfield',
						'heading'    => __( 'Tekst przycisku', 'campsflow' ),
						'param_name' => 'button_label',
						'value'      => __( 'Zapisz się', 'campsflow' ),
					),
					array(
						'type'       => 'checkbox',
						'heading'    => __( 'Opcje', 'campsflow' ),
						'param_name' => 'show_meeting_points',
						'value'      => array( __( 'Pokaż punkty zbiórki', 'campsflow' ) => '1' ),
					),
					array(
						'type'       => 'checkbox',
						'heading'    => '',
						'param_name' => 'show_custom_fields',
						'value'      => array( __( 'Pokaż pola własne', 'campsflow' ) => '1' ),
					),
				),
			)
		);
	}
}
