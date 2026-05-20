<?php
declare(strict_types=1);

namespace Campsflow\Presentation;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Widget_Base;

final class ElementorWidget extends Widget_Base {

	public function get_name(): string {
		return 'campsflow_listing';
	}

	public function get_title(): string {
		return __( 'CampsFlow — Lista obozów', 'campsflow' );
	}

	public function get_icon(): string {
		return 'eicon-posts-grid';
	}

	public function get_categories(): array {
		return array( 'campsflow' );
	}

	public function get_keywords(): array {
		return array( 'obozy', 'turnusy', 'campsflow', 'lista' );
	}

	protected function register_controls(): void {
		// ── Content ────────────────────────────────────────────────

		$this->start_controls_section(
			'section_content',
			array(
				'label' => __( 'Zawartość', 'campsflow' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'view',
			array(
				'label'   => __( 'Widok', 'campsflow' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'events',
				'options' => array(
					'events'   => __( 'Lista imprez', 'campsflow' ),
					'sessions' => __( 'Lista turnusów (płaska)', 'campsflow' ),
				),
			)
		);

		$this->add_responsive_control(
			'columns',
			array(
				'label'          => __( 'Kolumny', 'campsflow' ),
				'type'           => Controls_Manager::NUMBER,
				'default'        => 3,
				'tablet_default' => 2,
				'mobile_default' => 1,
				'min'            => 1,
				'max'            => 4,
				'selectors'      => array(
					'{{WRAPPER}} .cf-listing' => '--cf-columns: {{VALUE}}',
				),
			)
		);

		$this->end_controls_section();

		// ── Style: Ogólne ──────────────────────────────────────────

		$this->start_controls_section(
			'section_style_general',
			array(
				'label' => __( 'Kolory i akcent', 'campsflow' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'accent_color',
			array(
				'label'     => __( 'Kolor akcentu (przyciski, linie)', 'campsflow' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#2563eb',
				'selectors' => array(
					'{{WRAPPER}} .cf-listing' => '--cf-accent: {{VALUE}}',
				),
			)
		);

		$this->add_control(
			'accent_hover_color',
			array(
				'label'     => __( 'Kolor akcentu — hover', 'campsflow' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#1d4ed8',
				'selectors' => array(
					'{{WRAPPER}} .cf-listing' => '--cf-accent-hover: {{VALUE}}',
				),
			)
		);

		$this->add_control(
			'gap',
			array(
				'label'     => __( 'Odstęp między kartami', 'campsflow' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array(
					'px' => array(
						'min' => 8,
						'max' => 64,
					),
				),
				'default'   => array(
					'size' => 24,
					'unit' => 'px',
				),
				'selectors' => array(
					'{{WRAPPER}} .cf-listing' => '--cf-gap: {{SIZE}}{{UNIT}}',
				),
			)
		);

		$this->end_controls_section();

		// ── Style: Karta ───────────────────────────────────────────

		$this->start_controls_section(
			'section_style_card',
			array(
				'label' => __( 'Karta imprezy', 'campsflow' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_control(
			'card_bg',
			array(
				'label'     => __( 'Tło karty', 'campsflow' ),
				'type'      => Controls_Manager::COLOR,
				'default'   => '#ffffff',
				'selectors' => array(
					'{{WRAPPER}} .cf-listing' => '--cf-card-bg: {{VALUE}}',
				),
			)
		);

		$this->add_control(
			'card_radius',
			array(
				'label'     => __( 'Zaokrąglenie rogów', 'campsflow' ),
				'type'      => Controls_Manager::SLIDER,
				'range'     => array(
					'px' => array(
						'min' => 0,
						'max' => 32,
					),
				),
				'default'   => array(
					'size' => 10,
					'unit' => 'px',
				),
				'selectors' => array(
					'{{WRAPPER}} .cf-listing' => '--cf-card-radius: {{SIZE}}{{UNIT}}',
				),
			)
		);

		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'card_shadow',
				'selector' => '{{WRAPPER}} .cf-card',
			)
		);

		$this->end_controls_section();

		// ── Style: Typografia ──────────────────────────────────────

		$this->start_controls_section(
			'section_style_typography',
			array(
				'label' => __( 'Typografia', 'campsflow' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'title_typography',
				'label'    => __( 'Tytuł imprezy', 'campsflow' ),
				'selector' => '{{WRAPPER}} .cf-card__title, {{WRAPPER}} .cf-sessions-flat__event',
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'session_typography',
				'label'    => __( 'Daty turnusu', 'campsflow' ),
				'selector' => '{{WRAPPER}} .cf-session__dates, {{WRAPPER}} .cf-sessions-box__dates',
			)
		);

		$this->end_controls_section();
	}

	protected function render(): void {
		$settings = $this->get_settings_for_display();
		$view     = in_array( $settings['view'] ?? '', array( 'events', 'sessions' ), true )
			? $settings['view']
			: 'events';

		$shortcode = new ListingShortcode();
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $shortcode->render(
			array(
				'view'    => $view,
				'columns' => '3',
			)
		);
	}
}
