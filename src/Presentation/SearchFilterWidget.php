<?php
declare(strict_types=1);

namespace Campsflow\Presentation;

use Elementor\Controls_Manager;
use Elementor\Widget_Base;

final class SearchFilterWidget extends Widget_Base {
	use FilterRenderMethods;

	public function get_name(): string {
		return 'campsflow_search_filter';
	}

	public function get_title(): string {
		return __( 'CampsFlow — Filtry wyszukiwania', 'campsflow' );
	}

	public function get_icon(): string {
		return 'eicon-search';
	}

	public function get_categories(): array {
		return array( 'campsflow' );
	}

	protected function register_controls(): void {
		$this->registerStyleLayoutSection();
		$this->start_controls_section(
			'section_filters',
			array(
				'label' => __( 'Filtry', 'campsflow' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);
		$this->add_control(
			'cf_filter_tip',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => __( 'Jeden widget z wszystkimi filtrami w jednym formularzu — wygodny do testowania. Jeśli chcesz rozmieszczać filtry osobno, użyj widgetu <strong>Pole filtru</strong> wielokrotnie.', 'campsflow' ),
				'content_classes' => 'elementor-descriptor',
			)
		);

		$this->addFilterControl( 'category', __( 'Profil', 'campsflow' ), __( 'Wszystkie profile', 'campsflow' ) );
		$this->addFilterControl( 'age', __( 'Grupa wiekowa', 'campsflow' ), __( 'Wszystkie grupy wiekowe', 'campsflow' ) );
		$this->addFilterControl( 'child_age', __( 'Wiek', 'campsflow' ), __( 'Wiek', 'campsflow' ) );
		$this->addFilterControl( 'destination', __( 'Kierunek', 'campsflow' ), __( 'Wszystkie kierunki', 'campsflow' ) );
		$this->addFilterControl( 'transport', __( 'Transport', 'campsflow' ), __( 'Transport', 'campsflow' ) );
		$this->addFilterControl( 'dates', __( 'Daty', 'campsflow' ), '' );
		$this->end_controls_section();
	}

	private function registerStyleLayoutSection(): void {
		$this->start_controls_section(
			'section_style_layout',
			array(
				'label' => __( 'Układ formularza', 'campsflow' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);
		$this->add_control(
			'form_direction',
			array(
				'label'     => __( 'Kierunek', 'campsflow' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'column',
				'options'   => array(
					'column' => __( 'Pionowy (filtry pod sobą)', 'campsflow' ),
					'row'    => __( 'Poziomy (filtry obok siebie)', 'campsflow' ),
				),
				'selectors' => array(
					'{{WRAPPER}} .cf-filters' => 'display:flex; flex-direction:{{VALUE}}; flex-wrap:wrap; align-items:flex-start;',
				),
			)
		);
		$this->add_control(
			'form_gap',
			array(
				'label'     => __( 'Odstęp między polami', 'campsflow' ),
				'type'      => Controls_Manager::SLIDER,
				'default'   => array( 'size' => 8 ),
				'range'     => array(
					'px' => array(
						'min'  => 0,
						'max'  => 40,
						'step' => 1,
					),
				),
				'selectors' => array(
					'{{WRAPPER}} .cf-filters' => 'gap: {{SIZE}}px;',
				),
			)
		);
		$this->end_controls_section();
	}

	private function addFilterControl( string $key, string $label, string $defaultPlaceholder ): void {
		$this->add_control(
			'show_' . $key,
			array(
				'label'     => $label,
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'label_on'  => __( 'Tak', 'campsflow' ),
				'label_off' => __( 'Nie', 'campsflow' ),
			)
		);
		if ( $defaultPlaceholder !== '' ) {
			$this->add_control(
				'label_' . $key,
				array(
					'label'     => __( 'Label (opcja pusta)', 'campsflow' ),
					'type'      => Controls_Manager::TEXT,
					'default'   => $defaultPlaceholder,
					'condition' => array( 'show_' . $key => 'yes' ),
				)
			);
		}
	}

	protected function render(): void {
		$s        = $this->get_settings_for_display();
		$endpoint = rest_url( 'campsflow/v1/events' );

		echo '<form class="cf-search-form cf-filters" method="get" action="" data-endpoint="' . esc_url( $endpoint ) . '">';

		if ( ( $s['show_category'] ?? '' ) === 'yes' ) {
			$this->renderTaxFilterSelect( 'cf_event_category', 'category', (string) ( $s['label_category'] ?? '' ) );
		}
		if ( ( $s['show_age'] ?? '' ) === 'yes' ) {
			$this->renderTaxFilterSelect( 'cf_age_group', 'age', (string) ( $s['label_age'] ?? '' ) );
		}
		if ( ( $s['show_child_age'] ?? '' ) === 'yes' ) {
			$this->renderChildAgeFilterSelect( (string) ( $s['label_child_age'] ?? '' ) );
		}
		if ( ( $s['show_destination'] ?? '' ) === 'yes' ) {
			$this->renderDestinationFilterSelect( (string) ( $s['label_destination'] ?? '' ) );
		}
		if ( ( $s['show_transport'] ?? '' ) === 'yes' ) {
			$this->renderTaxFilterSelect( 'cf_transport_type', 'transport', (string) ( $s['label_transport'] ?? '' ) );
		}
		if ( ( $s['show_dates'] ?? '' ) === 'yes' ) {
			$currentFrom = sanitize_text_field( $_GET['dateFrom'] ?? '' );
			$currentTo   = sanitize_text_field( $_GET['dateTo'] ?? '' );
			echo '<input class="cf-filter" type="date" name="dateFrom" value="' . esc_attr( $currentFrom ) . '">';
			echo '<input class="cf-filter" type="date" name="dateTo" value="' . esc_attr( $currentTo ) . '">';
		}

		echo '</form>';
	}
}
