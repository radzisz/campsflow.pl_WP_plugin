<?php
declare(strict_types=1);

namespace Campsflow\Presentation;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Typography;
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
		return array( 'campsflow_search' );
	}

	protected function register_controls(): void {
		$this->registerStyleLayoutSection();
		$this->registerStyleFiltersSection();
		$this->registerStyleDropdownFooterSection();
		$this->registerStyleResetSection();
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
		$this->add_control(
			'show_transport_icons',
			array(
				'label'     => __( 'Pokaż ikonki', 'campsflow' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'label_on'  => __( 'Tak', 'campsflow' ),
				'label_off' => __( 'Nie', 'campsflow' ),
				'condition' => array( 'show_transport' => 'yes' ),
			)
		);
		$this->addFilterControl( 'dates', __( 'Termin', 'campsflow' ), __( 'Termin', 'campsflow' ) );

		$this->add_control(
			'show_reset',
			array(
				'label'     => __( 'Przycisk reset', 'campsflow' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'label_on'  => __( 'Tak', 'campsflow' ),
				'label_off' => __( 'Nie', 'campsflow' ),
				'separator' => 'before',
			)
		);
		$this->add_control(
			'reset_label',
			array(
				'label'     => __( 'Tekst przycisku reset', 'campsflow' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => __( 'Wyczyść filtry', 'campsflow' ),
				'condition' => array( 'show_reset' => 'yes' ),
			)
		);

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

	private function registerStyleFiltersSection(): void {
		$this->start_controls_section(
			'section_style_filters',
			array(
				'label' => __( 'Pola filtru', 'campsflow' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);
		$this->add_control(
			'filter_bg',
			array(
				'label'     => __( 'Tło pola', 'campsflow' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .cf-multi__toggle, {{WRAPPER}} .cf-daterange__toggle' => 'background-color: {{VALUE}}',
				),
			)
		);
		$this->add_control(
			'filter_color',
			array(
				'label'     => __( 'Kolor tekstu', 'campsflow' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .cf-multi__toggle, {{WRAPPER}} .cf-daterange__toggle' => 'color: {{VALUE}}',
				),
			)
		);
		$this->add_control(
			'filter_border_radius',
			array(
				'label'      => __( 'Zaokrąglenie rogów', 'campsflow' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .cf-multi__toggle, {{WRAPPER}} .cf-daterange__toggle' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}',
				),
			)
		);
		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'filter_border',
				'selector' => '{{WRAPPER}} .cf-multi__toggle, {{WRAPPER}} .cf-daterange__toggle',
			)
		);
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'filter_typography',
				'selector' => '{{WRAPPER}} .cf-multi__toggle, {{WRAPPER}} .cf-daterange__toggle',
			)
		);
		$this->add_control(
			'filter_accent',
			array(
				'label'     => __( 'Kolor akcentu (zaznaczone)', 'campsflow' ),
				'type'      => Controls_Manager::COLOR,
				'separator' => 'before',
				'selectors' => array(
					'{{WRAPPER}} .cf-multi__count' => 'background-color: {{VALUE}}',
					'{{WRAPPER}} .cf-multi__option input:checked' => 'accent-color: {{VALUE}}',
				),
			)
		);
		$this->add_control(
			'filter_hover_bg',
			array(
				'label'     => __( 'Tło (hover)', 'campsflow' ),
				'type'      => Controls_Manager::COLOR,
				'separator' => 'before',
				'selectors' => array(
					'{{WRAPPER}} .cf-multi__toggle:hover, {{WRAPPER}} .cf-daterange__toggle:hover' => 'background-color: {{VALUE}}',
				),
			)
		);
		$this->add_control(
			'filter_hover_color',
			array(
				'label'     => __( 'Kolor tekstu (hover)', 'campsflow' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .cf-multi__toggle:hover, {{WRAPPER}} .cf-daterange__toggle:hover' => 'color: {{VALUE}}',
				),
			)
		);
		$this->end_controls_section();
	}

	private function registerStyleDropdownFooterSection(): void {
		$this->start_controls_section(
			'section_style_dropdown_footer',
			array(
				'label' => __( 'Stopka dropdownu', 'campsflow' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);
		$this->add_control(
			'footer_icon_size',
			array(
				'label'      => __( 'Rozmiar ikon', 'campsflow' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'rem' ),
				'range'      => array(
					'px'  => array(
						'min' => 8,
						'max' => 24,
					),
					'rem' => array(
						'min'  => .5,
						'max'  => 2,
						'step' => .05,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .cf-multi__footer-clear, {{WRAPPER}} .cf-multi__footer-confirm' => 'font-size: {{SIZE}}{{UNIT}}; width: calc({{SIZE}}{{UNIT}} * 2.2); height: calc({{SIZE}}{{UNIT}} * 2.2);',
				),
			)
		);
		$this->add_control(
			'footer_clear_color',
			array(
				'label'     => __( 'Wyczyść — kolor', 'campsflow' ),
				'type'      => Controls_Manager::COLOR,
				'separator' => 'before',
				'selectors' => array(
					'{{WRAPPER}} .cf-multi__footer-clear' => 'color: {{VALUE}}',
				),
			)
		);
		$this->add_control(
			'footer_clear_hover_color',
			array(
				'label'     => __( 'Wyczyść — kolor (hover)', 'campsflow' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .cf-multi__footer-clear:hover' => 'color: {{VALUE}}',
				),
			)
		);
		$this->add_control(
			'footer_clear_hover_bg',
			array(
				'label'     => __( 'Wyczyść — tło (hover)', 'campsflow' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .cf-multi__footer-clear:hover' => 'background-color: {{VALUE}}',
				),
			)
		);
		$this->add_control(
			'footer_confirm_color',
			array(
				'label'     => __( 'Gotowe — kolor', 'campsflow' ),
				'type'      => Controls_Manager::COLOR,
				'separator' => 'before',
				'selectors' => array(
					'{{WRAPPER}} .cf-multi__footer-confirm' => 'color: {{VALUE}}',
				),
			)
		);
		$this->add_control(
			'footer_confirm_hover_bg',
			array(
				'label'     => __( 'Gotowe — tło (hover)', 'campsflow' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .cf-multi__footer-confirm:hover' => 'background-color: {{VALUE}}',
				),
			)
		);
		$this->add_control(
			'footer_confirm_hover_color',
			array(
				'label'     => __( 'Gotowe — kolor tekstu (hover)', 'campsflow' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .cf-multi__footer-confirm:hover' => 'color: {{VALUE}}',
				),
			)
		);
		$this->end_controls_section();
	}

	private function registerStyleResetSection(): void {
		$this->start_controls_section(
			'section_style_reset',
			array(
				'label' => __( 'Przycisk reset', 'campsflow' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);
		$this->add_control(
			'reset_bg',
			array(
				'label'     => __( 'Tło', 'campsflow' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .cf-reset' => 'background-color: {{VALUE}}',
				),
			)
		);
		$this->add_control(
			'reset_color',
			array(
				'label'     => __( 'Kolor tekstu', 'campsflow' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .cf-reset' => 'color: {{VALUE}}',
				),
			)
		);
		$this->add_control(
			'reset_border_radius',
			array(
				'label'      => __( 'Zaokrąglenie rogów', 'campsflow' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .cf-reset' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}',
				),
			)
		);
		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'reset_border',
				'selector' => '{{WRAPPER}} .cf-reset',
			)
		);
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'reset_typography',
				'selector' => '{{WRAPPER}} .cf-reset',
			)
		);
		$this->end_controls_section();
	}

	private function addFilterControl( string $key, string $label, string $defaultPlaceholder ): void {
		$this->add_control(
			'heading_' . $key,
			array(
				'label'     => $label,
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);
		$this->add_control(
			'show_' . $key,
			array(
				'label'     => __( 'Pokaż', 'campsflow' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'label_on'  => __( 'Tak', 'campsflow' ),
				'label_off' => __( 'Nie', 'campsflow' ),
			)
		);
		$this->add_control(
			'label_' . $key,
			array(
				'label'     => __( 'Etykieta', 'campsflow' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => $defaultPlaceholder,
				'condition' => array( 'show_' . $key => 'yes' ),
			)
		);
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
			$showIcons = ( $s['show_transport_icons'] ?? 'yes' ) === 'yes';
			$this->renderTransportFilterSelect( (string) ( $s['label_transport'] ?? '' ), $showIcons );
		}
		if ( ( $s['show_dates'] ?? '' ) === 'yes' ) {
			$this->renderDateRangePicker( (string) ( $s['label_dates'] ?? __( 'Termin', 'campsflow' ) ) );
		}

		if ( ( $s['show_reset'] ?? '' ) === 'yes' ) {
			$resetLabel = (string) ( $s['reset_label'] ?? __( 'Wyczyść filtry', 'campsflow' ) );
			echo '<button type="button" class="cf-reset">' . esc_html( $resetLabel ) . '</button>';
		}

		echo '</form>';
	}
}
