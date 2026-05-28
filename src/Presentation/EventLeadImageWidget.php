<?php
declare(strict_types=1);

namespace Campsflow\Presentation;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Widget_Base;

final class EventLeadImageWidget extends Widget_Base {

	public function get_name(): string {
		return 'campsflow_event_lead_image';
	}

	public function get_title(): string {
		return __( 'CampsFlow — Zdjęcie główne', 'campsflow' );
	}

	public function get_icon(): string {
		return 'eicon-image';
	}

	public function get_categories(): array {
		return array( 'campsflow_event' );
	}

	protected function register_controls(): void {
		$this->registerContentSection();
		$this->registerStyleSection();
	}

	private function registerContentSection(): void {
		$this->start_controls_section(
			'section_content',
			array(
				'label' => __( 'Zawartość', 'campsflow' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);
		$this->add_control(
			'alt_text',
			array(
				'label'       => __( 'Tekst alternatywny (alt)', 'campsflow' ),
				'type'        => Controls_Manager::TEXT,
				'description' => __( 'Jeśli puste, użyty zostanie tytuł wydarzenia.', 'campsflow' ),
			)
		);
		$this->add_control(
			'editor_placeholder',
			array(
				'label'       => __( 'Placeholder (tryb edycji)', 'campsflow' ),
				'type'        => Controls_Manager::TEXT,
				'description' => __( 'Widoczny tylko w edytorze gdy brak zdjęcia.', 'campsflow' ),
			)
		);
		$this->end_controls_section();
	}

	private function registerStyleSection(): void {
		$this->start_controls_section(
			'section_style',
			array(
				'label' => __( 'Obraz', 'campsflow' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);
		$this->add_control(
			'img_width',
			array(
				'label'      => __( 'Szerokość', 'campsflow' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%' ),
				'range'      => array(
					'px' => array(
						'min' => 50,
						'max' => 1600,
					),
					'%'  => array(
						'min' => 10,
						'max' => 100,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .cf-lead-image' => 'width: {{SIZE}}{{UNIT}}',
				),
			)
		);
		$this->add_control(
			'img_object_fit',
			array(
				'label'     => __( 'Object-fit', 'campsflow' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'cover',
				'options'   => array(
					'cover'   => 'Cover',
					'contain' => 'Contain',
					'fill'    => 'Fill',
					'none'    => 'None',
				),
				'selectors' => array(
					'{{WRAPPER}} .cf-lead-image' => 'object-fit: {{VALUE}}',
				),
			)
		);
		$this->add_control(
			'img_border_radius',
			array(
				'label'      => __( 'Zaokrąglenie rogów', 'campsflow' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 200,
					),
					'%'  => array(
						'min' => 0,
						'max' => 50,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .cf-lead-image' => 'border-radius: {{SIZE}}{{UNIT}}',
				),
			)
		);
		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'img_border',
				'selector' => '{{WRAPPER}} .cf-lead-image',
			)
		);
		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'img_shadow',
				'selector' => '{{WRAPPER}} .cf-lead-image',
			)
		);
		$this->end_controls_section();
	}

	protected function render(): void {
		$s           = $this->get_settings_for_display();
		$postId      = (int) get_the_ID();
		$altOverride = sanitize_text_field( (string) ( $s['alt_text'] ?? '' ) );
		$placeholder = sanitize_text_field( (string) ( $s['editor_placeholder'] ?? '' ) );

		$url = $postId ? (string) get_post_meta( $postId, 'cf_lead_image_url', true ) : '';

		if ( '' === $url ) {
			if ( '' !== $placeholder && \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<p class="cf-lead-image--placeholder">' . esc_html( $placeholder ) . '</p>';
			}
			return;
		}

		$alt = '' !== $altOverride ? $altOverride : get_the_title( $postId );

		echo '<img class="cf-lead-image" src="' . esc_url( $url ) . '" alt="' . esc_attr( $alt ) . '" loading="lazy">';
	}
}
