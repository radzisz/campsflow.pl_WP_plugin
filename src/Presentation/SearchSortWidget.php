<?php
declare(strict_types=1);

namespace Campsflow\Presentation;

use Elementor\Controls_Manager;
use Elementor\Widget_Base;

final class SearchSortWidget extends Widget_Base {

	/** @var array<string, string> */
	private const SORT_OPTIONS = array(
		'title_asc'  => 'Nazwa A-Z',
		'title_desc' => 'Nazwa Z-A',
		'date_asc'   => 'Termin: najwcześniejszy',
		'date_desc'  => 'Termin: najpóźniejszy',
		'price_asc'  => 'Cena: od najtańszej',
		'price_desc' => 'Cena: od najdroższej',
	);

	public function get_name(): string {
		return 'campsflow_search_sort';
	}

	public function get_title(): string {
		return __( 'CampsFlow — Sortowanie', 'campsflow' );
	}

	public function get_icon(): string {
		return 'eicon-sort-amount-asc';
	}

	public function get_categories(): array {
		return array( 'campsflow' );
	}

	protected function register_controls(): void {
		$this->registerContentSection();
		$this->registerStyleLabelSection();
		$this->registerStyleInputSection();
	}

	private function registerContentSection(): void {
		$this->start_controls_section(
			'section_sort',
			array(
				'label' => __( 'Sortowanie', 'campsflow' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'cf_sort_tip',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => __( 'Umieść ten widget obok pól filtru — działa tak samo jak każde inne pole: zmienia URL, a widget <strong>Wyniki wyszukiwania</strong> reaguje automatycznie.', 'campsflow' ),
				'content_classes' => 'elementor-descriptor',
			)
		);

		$this->add_control(
			'header',
			array(
				'label'       => __( 'Nagłówek', 'campsflow' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => __( 'np. Sortuj według', 'campsflow' ),
			)
		);

		$this->add_control(
			'placeholder',
			array(
				'label'   => __( 'Label (opcja pusta)', 'campsflow' ),
				'type'    => Controls_Manager::TEXT,
				'default' => __( 'Sortuj według', 'campsflow' ),
			)
		);

		$this->add_control(
			'default_sort',
			array(
				'label'   => __( 'Domyślne sortowanie', 'campsflow' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '',
				'options' => array(
					''           => __( '— brak (pierwsza widoczna opcja) —', 'campsflow' ),
					'title_asc'  => __( 'Nazwa A-Z', 'campsflow' ),
					'title_desc' => __( 'Nazwa Z-A', 'campsflow' ),
					'date_asc'   => __( 'Termin: najwcześniejszy', 'campsflow' ),
					'date_desc'  => __( 'Termin: najpóźniejszy', 'campsflow' ),
					'price_asc'  => __( 'Cena: od najtańszej', 'campsflow' ),
					'price_desc' => __( 'Cena: od najdroższej', 'campsflow' ),
				),
			)
		);

		$this->add_control(
			'cf_sort_divider',
			array(
				'type' => Controls_Manager::DIVIDER,
			)
		);

		$this->addSortOptionControls( 'title_asc', __( 'Nazwa A-Z', 'campsflow' ) );
		$this->addSortOptionControls( 'title_desc', __( 'Nazwa Z-A', 'campsflow' ) );
		$this->addSortOptionControls( 'date_asc', __( 'Termin: najwcześniejszy', 'campsflow' ) );
		$this->addSortOptionControls( 'date_desc', __( 'Termin: najpóźniejszy', 'campsflow' ) );
		$this->addSortOptionControls( 'price_asc', __( 'Cena: od najtańszej', 'campsflow' ) );
		$this->addSortOptionControls( 'price_desc', __( 'Cena: od najdroższej', 'campsflow' ) );

		$this->end_controls_section();
	}

	private function addSortOptionControls( string $key, string $defaultLabel ): void {
		$this->add_control(
			'show_' . $key,
			array(
				'label'     => $defaultLabel,
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
				'default'   => $defaultLabel,
				'condition' => array( 'show_' . $key => 'yes' ),
			)
		);
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

	private function registerStyleInputSection(): void {
		$this->start_controls_section(
			'section_style_input',
			array(
				'label' => __( 'Pole wyboru', 'campsflow' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'input_width',
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
				'default'    => array(
					'unit' => '%',
					'size' => 100,
				),
				'selectors'  => array(
					'{{WRAPPER}} .cf-filter' => 'width: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'input_bg',
			array(
				'label'     => __( 'Tło', 'campsflow' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .cf-filter' => 'background-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'input_color',
			array(
				'label'     => __( 'Kolor tekstu', 'campsflow' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .cf-filter' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'input_border_color',
			array(
				'label'     => __( 'Kolor obramowania', 'campsflow' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .cf-filter' => 'border-color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'input_border_radius',
			array(
				'label'      => __( 'Zaokrąglenie', 'campsflow' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 50,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .cf-filter' => 'border-radius: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'input_font_size',
			array(
				'label'      => __( 'Rozmiar czcionki', 'campsflow' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', 'em', 'rem' ),
				'range'      => array(
					'px' => array(
						'min' => 10,
						'max' => 28,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .cf-filter' => 'font-size: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->end_controls_section();
	}

	protected function render(): void {
		$s           = $this->get_settings_for_display();
		$header      = (string) ( $s['header'] ?? '' );
		$placeholder = (string) ( $s['placeholder'] ?? '' );
		$defaultSort = (string) ( $s['default_sort'] ?? '' );
		$currentSort = sanitize_text_field( $_GET['sort'] ?? $defaultSort );

		$options = array();
		foreach ( self::SORT_OPTIONS as $value => $fallbackLabel ) {
			if ( ( $s[ 'show_' . $value ] ?? 'yes' ) !== 'yes' ) {
				continue;
			}
			$custom            = (string) ( $s[ 'label_' . $value ] ?? '' );
			$options[ $value ] = $custom !== '' ? $custom : $fallbackLabel;
		}

		if ( empty( $options ) ) {
			return;
		}

		if ( $header !== '' ) {
			echo '<label class="cf-filter-label">' . esc_html( $header ) . '</label>';
		}

		$emptyLabel = $placeholder !== '' ? $placeholder : __( 'Sortuj według', 'campsflow' );
		echo '<select class="cf-filter" name="sort">';
		echo '<option value="">' . esc_html( $emptyLabel ) . '</option>';
		foreach ( $options as $value => $label ) {
			$selected = selected( $currentSort, $value, false );
			echo '<option value="' . esc_attr( $value ) . '"' . $selected . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select>';
	}
}
