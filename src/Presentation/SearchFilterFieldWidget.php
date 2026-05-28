<?php
declare(strict_types=1);

namespace Campsflow\Presentation;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;

final class SearchFilterFieldWidget extends Widget_Base {
	use FilterRenderMethods;

	public function get_name(): string {
		return 'campsflow_search_filter_field';
	}

	public function get_title(): string {
		return __( 'CampsFlow — Pole filtru', 'campsflow' );
	}

	public function get_icon(): string {
		return 'eicon-filter';
	}

	public function get_categories(): array {
		return array( 'campsflow_search' );
	}

	protected function register_controls(): void {
		$this->registerContentSection();
		$this->registerStyleLayoutSection();
		$this->registerStyleLabelSection();
		$this->registerStyleMultiSection();
		$this->registerStyleDropdownFooterSection();
	}

	private function registerContentSection(): void {
		$this->start_controls_section(
			'section_field',
			array(
				'label' => __( 'Pole', 'campsflow' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'cf_field_tip',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => __( 'Umieść ten widget wielokrotnie na stronie — każde pole zmienia URL, a widget <strong>Wyniki wyszukiwania</strong> reaguje automatycznie. Nie potrzebujesz wspólnego kontenera ani formularza.', 'campsflow' ),
				'content_classes' => 'elementor-descriptor',
			)
		);

		$this->add_control(
			'field_type',
			array(
				'label'   => __( 'Typ pola', 'campsflow' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'category',
				'options' => array(
					'category'    => __( 'Profil', 'campsflow' ),
					'age'         => __( 'Grupa wiekowa', 'campsflow' ),
					'child_age'   => __( 'Wiek', 'campsflow' ),
					'destination' => __( 'Kierunek', 'campsflow' ),
					'transport'   => __( 'Transport', 'campsflow' ),
					'season'      => __( 'Sezon', 'campsflow' ),
					'dates'       => __( 'Termin (zakres dat)', 'campsflow' ),
				),
			)
		);

		$this->add_control(
			'header',
			array(
				'label'       => __( 'Nagłówek', 'campsflow' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => __( 'np. Wybierz profil', 'campsflow' ),
			)
		);

		$this->add_control(
			'placeholder',
			array(
				'label'     => __( 'Label (opcja pusta)', 'campsflow' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => '',
				'condition' => array(
					'field_type!' => array( 'dates' ),
				),
			)
		);
		$this->add_control(
			'show_transport_icons',
			array(
				'label'     => __( 'Pokaż ikonki', 'campsflow' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'label_on'  => __( 'Tak', 'campsflow' ),
				'label_off' => __( 'Nie', 'campsflow' ),
				'condition' => array( 'field_type' => 'transport' ),
			)
		);

		$this->end_controls_section();
	}

	private function registerStyleLayoutSection(): void {
		$this->start_controls_section(
			'section_style_layout',
			array(
				'label' => __( 'Układ', 'campsflow' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);
		$this->add_control(
			'layout',
			array(
				'label'                => __( 'Kierunek', 'campsflow' ),
				'type'                 => Controls_Manager::SELECT,
				'default'              => 'column',
				'options'              => array(
					'column' => __( 'Pionowy (nagłówek nad polem)', 'campsflow' ),
					'row'    => __( 'Poziomy (nagłówek obok pola)', 'campsflow' ),
				),
				'selectors_dictionary' => array(
					'column' => 'flex-direction:column; align-items:flex-start;',
					'row'    => 'flex-direction:row; align-items:center;',
				),
				'selectors'            => array(
					'{{WRAPPER}} .cf-filter-wrap' => '{{VALUE}}',
				),
			)
		);
		$this->add_control(
			'layout_gap',
			array(
				'label'     => __( 'Odstęp między nagłówkiem a polem', 'campsflow' ),
				'type'      => Controls_Manager::SLIDER,
				'default'   => array( 'size' => 8 ),
				'range'     => array(
					'px' => array(
						'min'  => 0,
						'max'  => 60,
						'step' => 1,
					),
				),
				'selectors' => array(
					'{{WRAPPER}} .cf-filter-wrap' => 'gap: {{SIZE}}px;',
				),
			)
		);
		$this->end_controls_section();
	}

	private function registerStyleLabelSection(): void {
		$this->start_controls_section(
			'section_style_label',
			array(
				'label' => __( 'Nagłówek', 'campsflow' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'label_color',
			array(
				'label'     => __( 'Kolor', 'campsflow' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .cf-filter-label' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'label_font_size',
			array(
				'label'      => __( 'Rozmiar czcionki', 'campsflow' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'rem' ),
				'range'      => array(
					'px' => array(
						'min' => 10,
						'max' => 48,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .cf-filter-label' => 'font-size: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'label_font_weight',
			array(
				'label'     => __( 'Grubość czcionki', 'campsflow' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => '',
				'options'   => array(
					''    => __( 'Domyślna', 'campsflow' ),
					'400' => __( 'Normalna (400)', 'campsflow' ),
					'600' => __( 'Półgruba (600)', 'campsflow' ),
					'700' => __( 'Gruba (700)', 'campsflow' ),
				),
				'selectors' => array(
					'{{WRAPPER}} .cf-filter-label' => 'font-weight: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'label_margin_bottom',
			array(
				'label'      => __( 'Odstęp pod nagłówkiem', 'campsflow' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 40,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .cf-filter-label' => 'margin-bottom: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	private function registerStyleMultiSection(): void {
		$this->start_controls_section(
			'section_style_multi',
			array(
				'label' => __( 'Multi-select (lista rozwijana)', 'campsflow' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);
		$this->add_control(
			'multi_width',
			array(
				'label'      => __( 'Szerokość', 'campsflow' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%' ),
				'range'      => array(
					'%'  => array(
						'min' => 10,
						'max' => 100,
					),
					'px' => array(
						'min' => 50,
						'max' => 600,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .cf-multi, {{WRAPPER}} .cf-daterange' => 'width: {{SIZE}}{{UNIT}};',
				),
			)
		);
		$this->add_control(
			'multi_bg',
			array(
				'label'     => __( 'Tło przycisku', 'campsflow' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .cf-multi__toggle, {{WRAPPER}} .cf-daterange__toggle' => 'background-color: {{VALUE}}',
				),
			)
		);
		$this->add_control(
			'multi_color',
			array(
				'label'     => __( 'Kolor tekstu', 'campsflow' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .cf-multi__toggle, {{WRAPPER}} .cf-daterange__toggle' => 'color: {{VALUE}}',
				),
			)
		);
		$this->add_control(
			'multi_border_radius',
			array(
				'label'      => __( 'Zaokrąglenie rogów', 'campsflow' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 50,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .cf-multi__toggle, {{WRAPPER}} .cf-daterange__toggle' => 'border-radius: {{SIZE}}{{UNIT}}',
				),
			)
		);
		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'multi_border',
				'selector' => '{{WRAPPER}} .cf-multi__toggle, {{WRAPPER}} .cf-daterange__toggle',
			)
		);
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'multi_typography',
				'selector' => '{{WRAPPER}} .cf-multi__toggle, {{WRAPPER}} .cf-daterange__toggle',
			)
		);
		$this->add_control(
			'multi_accent',
			array(
				'label'     => __( 'Kolor akcentu (zaznaczone opcje)', 'campsflow' ),
				'type'      => Controls_Manager::COLOR,
				'separator' => 'before',
				'selectors' => array(
					'{{WRAPPER}} .cf-multi__count' => 'background-color: {{VALUE}}',
					'{{WRAPPER}} .cf-multi__option input:checked' => 'accent-color: {{VALUE}}',
				),
			)
		);
		$this->add_control(
			'multi_hover_bg',
			array(
				'label'     => __( 'Tło przycisku (hover)', 'campsflow' ),
				'type'      => Controls_Manager::COLOR,
				'separator' => 'before',
				'selectors' => array(
					'{{WRAPPER}} .cf-multi__toggle:hover, {{WRAPPER}} .cf-daterange__toggle:hover' => 'background-color: {{VALUE}}',
				),
			)
		);
		$this->add_control(
			'multi_hover_color',
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

	protected function render(): void {
		$s           = $this->get_settings_for_display();
		$field_type  = (string) ( $s['field_type'] ?? 'category' );
		$placeholder = (string) ( $s['placeholder'] ?? '' );
		$header      = (string) ( $s['header'] ?? '' );

		echo '<div class="cf-filter-wrap">';

		if ( $header !== '' ) {
			echo '<label class="cf-filter-label">' . esc_html( $header ) . '</label>';
		}

		switch ( $field_type ) {
			case 'category':
				$label = $placeholder !== '' ? $placeholder : __( 'Wszystkie profile', 'campsflow' );
				$this->renderTaxFilterSelect( 'cf_event_category', 'category', $label );
				break;

			case 'age':
				$label = $placeholder !== '' ? $placeholder : __( 'Wszystkie grupy wiekowe', 'campsflow' );
				$this->renderTaxFilterSelect( 'cf_age_group', 'age', $label );
				break;

			case 'child_age':
				$label = $placeholder !== '' ? $placeholder : __( 'Wiek', 'campsflow' );
				$this->renderChildAgeFilterSelect( $label );
				break;

			case 'destination':
				$label = $placeholder !== '' ? $placeholder : __( 'Wszystkie kierunki', 'campsflow' );
				$this->renderDestinationFilterSelect( $label );
				break;

			case 'transport':
				$label     = $placeholder !== '' ? $placeholder : __( 'Transport', 'campsflow' );
				$showIcons = ( $s['show_transport_icons'] ?? 'yes' ) === 'yes';
				$this->renderTransportFilterSelect( $label, $showIcons );
				break;

			case 'season':
				$label = $placeholder !== '' ? $placeholder : __( 'Sezon', 'campsflow' );
				$this->renderSeasonFilterSelect( $label );
				break;

			case 'dates':
				$this->renderDateRangePicker( __( 'Termin', 'campsflow' ) );
				break;
		}

		echo '</div>';
	}
}
