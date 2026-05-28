<?php
declare(strict_types=1);

namespace Campsflow\Presentation;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;

final class EventContactWidget extends Widget_Base {

	public function get_name(): string {
		return 'campsflow_event_contact';
	}

	public function get_title(): string {
		return __( 'CampsFlow — Kontakt', 'campsflow' );
	}

	public function get_icon(): string {
		return 'eicon-person';
	}

	public function get_categories(): array {
		return array( 'campsflow_event' );
	}

	protected function register_controls(): void {
		$this->registerContentSection();
		$this->registerStyleBoxSection();
		$this->registerStyleLabelSection();
		$this->registerStyleContentSection();
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
			'show_label',
			array(
				'label'     => __( 'Pokaż nagłówek', 'campsflow' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => '',
				'label_on'  => __( 'Tak', 'campsflow' ),
				'label_off' => __( 'Nie', 'campsflow' ),
			)
		);
		$this->add_control(
			'label_text',
			array(
				'label'     => __( 'Tekst nagłówka', 'campsflow' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => __( 'Kontakt', 'campsflow' ),
				'condition' => array( 'show_label' => 'yes' ),
			)
		);
		$this->add_control(
			'editor_placeholder',
			array(
				'label'       => __( 'Placeholder (tryb edycji)', 'campsflow' ),
				'type'        => Controls_Manager::TEXT,
				'description' => __( 'Widoczny tylko w edytorze gdy brak danych kontaktowych.', 'campsflow' ),
			)
		);
		$this->end_controls_section();
	}

	private function registerStyleBoxSection(): void {
		$this->start_controls_section(
			'section_style_box',
			array(
				'label' => __( 'Kontener', 'campsflow' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);
		$this->add_control(
			'box_bg',
			array(
				'label'     => __( 'Tło', 'campsflow' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .cf-contact-box' => 'background: {{VALUE}}',
				),
			)
		);
		$this->add_control(
			'box_padding',
			array(
				'label'      => __( 'Padding', 'campsflow' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', 'em', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .cf-contact-box' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}',
				),
			)
		);
		$this->add_control(
			'box_border_radius',
			array(
				'label'      => __( 'Zaokrąglenie rogów', 'campsflow' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .cf-contact-box' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}',
				),
			)
		);
		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'box_border',
				'selector' => '{{WRAPPER}} .cf-contact-box',
			)
		);
		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'box_shadow',
				'selector' => '{{WRAPPER}} .cf-contact-box',
			)
		);
		$this->end_controls_section();
	}

	private function registerStyleLabelSection(): void {
		$this->start_controls_section(
			'section_style_label',
			array(
				'label'     => __( 'Nagłówek', 'campsflow' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'show_label' => 'yes' ),
			)
		);
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'label_typography',
				'selector' => '{{WRAPPER}} .cf-contact-box__label',
			)
		);
		$this->add_control(
			'label_color',
			array(
				'label'     => __( 'Kolor nagłówka', 'campsflow' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .cf-contact-box__label' => 'color: {{VALUE}}',
				),
			)
		);
		$this->end_controls_section();
	}

	private function registerStyleContentSection(): void {
		$this->start_controls_section(
			'section_style_content',
			array(
				'label' => __( 'Dane kontaktowe', 'campsflow' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'contact_typography',
				'selector' => '{{WRAPPER}} .cf-contact-box',
			)
		);
		$this->add_control(
			'contact_color',
			array(
				'label'     => __( 'Kolor tekstu', 'campsflow' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .cf-contact-box' => 'color: {{VALUE}}',
				),
			)
		);
		$this->end_controls_section();
	}

	protected function render(): void {
		$s           = $this->get_settings_for_display();
		$postId      = (int) get_the_ID();
		$showLabel   = ( $s['show_label'] ?? '' ) === 'yes';
		$labelText   = sanitize_text_field( (string) ( $s['label_text'] ?? '' ) );
		$placeholder = sanitize_text_field( (string) ( $s['editor_placeholder'] ?? '' ) );

		$contact = array();
		if ( $postId ) {
			$decoded = json_decode( (string) get_post_meta( $postId, 'cf_contact', true ), true );
			$contact = is_array( $decoded ) ? $decoded : array();
		}

		if ( empty( $contact ) ) {
			if ( '' !== $placeholder && \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<div class="cf-contact-box">';
				if ( $showLabel && '' !== $labelText ) {
					echo '<div class="cf-contact-box__label">' . esc_html( $labelText ) . '</div>';
				}
				echo '<p class="cf-contact-box--placeholder">' . esc_html( $placeholder ) . '</p>';
				echo '</div>';
			}
			return;
		}

		$name  = trim( sanitize_text_field( (string) ( $contact['firstname'] ?? '' ) ) . ' ' . sanitize_text_field( (string) ( $contact['lastname'] ?? '' ) ) );
		$email = sanitize_email( (string) ( $contact['email'] ?? '' ) );
		$phone = sanitize_text_field( (string) ( $contact['phone'] ?? '' ) );

		echo '<div class="cf-contact-box">';
		if ( $showLabel && '' !== $labelText ) {
			echo '<div class="cf-contact-box__label">' . esc_html( $labelText ) . '</div>';
		}
		if ( '' !== $name ) {
			echo '<div class="cf-contact-box__name">' . esc_html( $name ) . '</div>';
		}
		echo '<div class="cf-contact-box__links">';
		if ( '' !== $email ) {
			echo '<a class="cf-contact-link" href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a>';
		}
		if ( '' !== $phone ) {
			$tel = preg_replace( '/[^+\d]/', '', $phone ) ?? '';
			echo '<a class="cf-contact-link" href="tel:' . esc_attr( $tel ) . '">' . esc_html( $phone ) . '</a>';
		}
		echo '</div>';
		echo '</div>';
	}
}
