<?php
declare(strict_types=1);

namespace Campsflow\Presentation;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Widget_Base;

final class EventMapWidget extends Widget_Base {
	use EventMapRenderMethods;

	public function get_name(): string {
		return 'campsflow_event_map';
	}

	public function get_title(): string {
		return __( 'CampsFlow — Mapa', 'campsflow' );
	}

	public function get_icon(): string {
		return 'eicon-map-pin';
	}

	public function get_categories(): array {
		return array( 'campsflow_event' );
	}

	protected function register_controls(): void {
		$this->start_controls_section(
			'section_map',
			array(
				'label' => __( 'Mapa', 'campsflow' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);

		$this->add_control(
			'provider',
			array(
				'label'   => __( 'Dostawca', 'campsflow' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'openstreetmap',
				'options' => array(
					'openstreetmap' => __( 'OpenStreetMap (darmowy)', 'campsflow' ),
					'google'        => __( 'Google Maps (wymaga klucza API)', 'campsflow' ),
				),
			)
		);

		$this->add_control(
			'height',
			array(
				'label'     => __( 'Wysokość (px)', 'campsflow' ),
				'type'      => Controls_Manager::SLIDER,
				'default'   => array( 'size' => 400 ),
				'range'     => array(
					'px' => array(
						'min' => 150,
						'max' => 800,
					),
				),
				'selectors' => array(
					'{{WRAPPER}} .cf-event-map' => 'height: {{SIZE}}px;',
				),
			)
		);

		$this->add_control(
			'zoom',
			array(
				'label'   => __( 'Poziom przybliżenia', 'campsflow' ),
				'type'    => Controls_Manager::SLIDER,
				'default' => array( 'size' => 14 ),
				'range'   => array(
					'px' => array(
						'min' => 4,
						'max' => 20,
					),
				),
			)
		);

		$this->add_control(
			'map_type',
			array(
				'label'     => __( 'Typ mapy', 'campsflow' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'roadmap',
				'options'   => array(
					'roadmap'   => __( 'Mapa drogowa', 'campsflow' ),
					'satellite' => __( 'Satelita', 'campsflow' ),
					'hybrid'    => __( 'Hybrid', 'campsflow' ),
					'terrain'   => __( 'Teren', 'campsflow' ),
				),
				'condition' => array( 'provider' => 'google' ),
			)
		);

		$this->end_controls_section();
		$this->registerStyleSection();
	}

	private function registerStyleSection(): void {
		$this->start_controls_section(
			'section_style_map',
			array(
				'label' => __( 'Wygląd', 'campsflow' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);
		$this->add_control(
			'map_border_radius',
			array(
				'label'      => __( 'Zaokrąglenie rogów', 'campsflow' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .cf-event-map' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}; overflow: hidden',
				),
			)
		);
		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'map_border',
				'selector' => '{{WRAPPER}} .cf-event-map',
			)
		);
		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'map_shadow',
				'selector' => '{{WRAPPER}} .cf-event-map',
			)
		);
		$this->end_controls_section();
	}

	protected function render(): void {
		$s        = $this->get_settings_for_display();
		$provider = (string) ( $s['provider'] ?? 'openstreetmap' );
		$zoom     = (int) ( $s['zoom']['size'] ?? 14 );
		$mapType  = (string) ( $s['map_type'] ?? 'roadmap' );
		$height   = (int) ( $s['height']['size'] ?? 400 );
		$postId   = (int) get_the_ID();

		$this->echoMapDiv( $postId, $provider, $zoom, $mapType, $height );
	}
}
