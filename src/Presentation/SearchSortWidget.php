<?php
declare(strict_types=1);

namespace Campsflow\Presentation;

use Elementor\Controls_Manager;
use Elementor\Widget_Base;

final class SearchSortWidget extends Widget_Base {

	/** @var array<string, array{asc: string, desc: string, default_label: string}> */
	private const SORT_GROUPS = array(
		'title' => array(
			'asc'           => 'title_asc',
			'desc'          => 'title_desc',
			'default_label' => 'Nazwa',
		),
		'date'  => array(
			'asc'           => 'date_asc',
			'desc'          => 'date_desc',
			'default_label' => 'Termin',
		),
		'price' => array(
			'asc'           => 'price_asc',
			'desc'          => 'price_desc',
			'default_label' => 'Cena',
		),
	);

	public function get_name(): string {
		return 'campsflow_search_sort';
	}

	public function get_title(): string {
		return __( 'CampsFlow — Sortowanie', 'campsflow' );
	}

	public function get_icon(): string {
		return 'eicon-sort-amount-desc';
	}

	public function get_categories(): array {
		return array( 'campsflow' );
	}

	protected function register_controls(): void {
		$this->registerContentSection();
		$this->registerStyleLayoutSection();
		$this->registerStyleLabelSection();
		$this->registerStyleButtonSection();
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
				'raw'             => __( 'Lista klikalnych etykiet — kliknięcie pokazuje strzałkę kierunku, ponowne kliknięcie odwraca kierunek. Reaguje na reset filtrów automatycznie.', 'campsflow' ),
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
			'separator',
			array(
				'label'   => __( 'Separator', 'campsflow' ),
				'type'    => Controls_Manager::TEXT,
				'default' => '/',
			)
		);

		$this->add_control(
			'cf_sort_divider',
			array(
				'type' => Controls_Manager::DIVIDER,
			)
		);

		foreach ( self::SORT_GROUPS as $key => $group ) {
			$this->add_control(
				'show_' . $key,
				array(
					'label'     => $group['default_label'],
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
					'default'   => $group['default_label'],
					'condition' => array( 'show_' . $key => 'yes' ),
				)
			);
		}

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
				'label'     => __( 'Kierunek', 'campsflow' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'column',
				'options'   => array(
					'column' => __( 'Pionowy (nagłówek nad paskiem)', 'campsflow' ),
					'row'    => __( 'Poziomy (nagłówek obok paska)', 'campsflow' ),
				),
				'selectors' => array(
					'{{WRAPPER}} .cf-sort-wrap' => 'display:flex; flex-direction:{{VALUE}}; align-items:center; flex-wrap:wrap;',
				),
			)
		);

		$this->add_control(
			'layout_gap',
			array(
				'label'     => __( 'Odstęp między nagłówkiem a paskiem', 'campsflow' ),
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
					'{{WRAPPER}} .cf-sort-wrap' => 'gap: {{SIZE}}px;',
				),
				'condition' => array( 'layout' => 'row' ),
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

		$this->end_controls_section();
	}

	private function registerStyleButtonSection(): void {
		$this->start_controls_section(
			'section_style_btn',
			array(
				'label' => __( 'Przyciski sortowania', 'campsflow' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'btn_color',
			array(
				'label'     => __( 'Kolor tekstu', 'campsflow' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .cf-sort-btn' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'btn_active_color',
			array(
				'label'     => __( 'Kolor aktywnego', 'campsflow' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .cf-sort-btn.is-active' => 'color: {{VALUE}};',
				),
			)
		);

		$this->add_control(
			'btn_font_size',
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
					'{{WRAPPER}} .cf-sort-btn' => 'font-size: {{SIZE}}{{UNIT}};',
				),
			)
		);

		$this->add_control(
			'sep_color',
			array(
				'label'     => __( 'Kolor separatora', 'campsflow' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .cf-sort-bar__sep' => 'color: {{VALUE}};',
				),
			)
		);

		$this->end_controls_section();
	}

	protected function render(): void {
		$s           = $this->get_settings_for_display();
		$header      = (string) ( $s['header'] ?? '' );
		$separator   = (string) ( $s['separator'] ?? '/' );
		$currentSort = sanitize_text_field( $_GET['sort'] ?? '' );

		$buttons = array();
		foreach ( self::SORT_GROUPS as $key => $group ) {
			if ( ( $s[ 'show_' . $key ] ?? 'yes' ) !== 'yes' ) {
				continue;
			}
			$custom    = (string) ( $s[ 'label_' . $key ] ?? '' );
			$buttons[] = array(
				'asc'   => $group['asc'],
				'desc'  => $group['desc'],
				'label' => $custom !== '' ? $custom : $group['default_label'],
			);
		}

		if ( empty( $buttons ) ) {
			return;
		}

		echo '<div class="cf-sort-wrap">';

		if ( $header !== '' ) {
			echo '<label class="cf-filter-label">' . esc_html( $header ) . '</label>';
		}

		echo '<div class="cf-sort-bar">';

		$last = count( $buttons ) - 1;
		foreach ( $buttons as $i => $btn ) {
			$isAsc    = $currentSort === $btn['asc'];
			$isDesc   = $currentSort === $btn['desc'];
			$classes  = 'cf-sort-btn';
			$classes .= $isAsc ? ' is-active is-asc' : ( $isDesc ? ' is-active is-desc' : '' );
			$arrow    = $isAsc ? '▲' : ( $isDesc ? '▼' : '' );

			echo '<button type="button" class="' . esc_attr( $classes ) . '"'
				. ' data-asc="' . esc_attr( $btn['asc'] ) . '"'
				. ' data-desc="' . esc_attr( $btn['desc'] ) . '">'
				. esc_html( $btn['label'] )
				. ' <span class="cf-sort-btn__arrow" aria-hidden="true">' . esc_html( $arrow ) . '</span>'
				. '</button>';

			if ( $i < $last && $separator !== '' ) {
				echo '<span class="cf-sort-bar__sep" aria-hidden="true">' . esc_html( $separator ) . '</span>';
			}
		}

		echo '<input type="hidden" class="cf-filter" name="sort" value="' . esc_attr( $currentSort ) . '">';
		echo '</div>';

		echo '</div>';
	}
}
