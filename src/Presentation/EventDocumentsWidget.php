<?php
declare(strict_types=1);

namespace Campsflow\Presentation;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;

final class EventDocumentsWidget extends Widget_Base {

	public function get_name(): string {
		return 'campsflow_event_documents';
	}

	public function get_title(): string {
		return __( 'CampsFlow — Dokumenty', 'campsflow' );
	}

	public function get_icon(): string {
		return 'eicon-document-file';
	}

	public function get_categories(): array {
		return array( 'campsflow' );
	}

	protected function register_controls(): void {
		$this->registerContentSection();
		$this->registerStyleBoxSection();
		$this->registerStyleLabelSection();
		$this->registerStyleLinksSection();
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
				'default'   => __( 'Dokumenty', 'campsflow' ),
				'condition' => array( 'show_label' => 'yes' ),
			)
		);
		$this->add_control(
			'open_new_tab',
			array(
				'label'     => __( 'Otwieraj w nowej karcie', 'campsflow' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'label_on'  => __( 'Tak', 'campsflow' ),
				'label_off' => __( 'Nie', 'campsflow' ),
			)
		);
		$this->add_control(
			'editor_placeholder',
			array(
				'label'       => __( 'Placeholder (tryb edycji)', 'campsflow' ),
				'type'        => Controls_Manager::TEXT,
				'description' => __( 'Widoczny tylko w edytorze gdy brak dokumentów.', 'campsflow' ),
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
					'{{WRAPPER}} .cf-docs-list' => 'background: {{VALUE}}',
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
					'{{WRAPPER}} .cf-docs-list' => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}',
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
					'{{WRAPPER}} .cf-docs-list' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}',
				),
			)
		);
		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'box_border',
				'selector' => '{{WRAPPER}} .cf-docs-list',
			)
		);
		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'     => 'box_shadow',
				'selector' => '{{WRAPPER}} .cf-docs-list',
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
				'selector' => '{{WRAPPER}} .cf-docs-list__label',
			)
		);
		$this->add_control(
			'label_color',
			array(
				'label'     => __( 'Kolor nagłówka', 'campsflow' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .cf-docs-list__label' => 'color: {{VALUE}}',
				),
			)
		);
		$this->end_controls_section();
	}

	private function registerStyleLinksSection(): void {
		$this->start_controls_section(
			'section_style_links',
			array(
				'label' => __( 'Linki do dokumentów', 'campsflow' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'links_typography',
				'selector' => '{{WRAPPER}} .cf-doc-link',
			)
		);
		$this->add_control(
			'links_color',
			array(
				'label'     => __( 'Kolor linków', 'campsflow' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .cf-doc-link' => 'color: {{VALUE}}',
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
		$openNewTab  = ( $s['open_new_tab'] ?? 'yes' ) === 'yes';
		$placeholder = sanitize_text_field( (string) ( $s['editor_placeholder'] ?? '' ) );

		$documents = array();
		if ( $postId ) {
			$decoded   = json_decode( (string) get_post_meta( $postId, 'cf_documents', true ), true );
			$documents = is_array( $decoded ) ? $decoded : array();
		}

		if ( empty( $documents ) ) {
			if ( '' !== $placeholder && \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<div class="cf-docs-list">';
				if ( $showLabel && '' !== $labelText ) {
					echo '<div class="cf-docs-list__label">' . esc_html( $labelText ) . '</div>';
				}
				echo '<p class="cf-docs-list--placeholder">' . esc_html( $placeholder ) . '</p>';
				echo '</div>';
			}
			return;
		}

		$target = $openNewTab ? ' target="_blank" rel="noopener"' : '';

		echo '<div class="cf-docs-list">';
		if ( $showLabel && '' !== $labelText ) {
			echo '<div class="cf-docs-list__label">' . esc_html( $labelText ) . '</div>';
		}
		echo '<ul>';
		foreach ( $documents as $doc ) {
			if ( ! is_array( $doc ) ) {
				continue;
			}
			$url  = esc_url( (string) ( $doc['url'] ?? '' ) );
			$name = esc_html( sanitize_text_field( (string) ( $doc['name'] ?? $doc['url'] ?? '' ) ) );
			if ( '' === $url ) {
				continue;
			}
			echo '<li><a class="cf-doc-link" href="' . $url . '"' . $target . '>' . $name . '</a></li>';
		}
		echo '</ul>';
		echo '</div>';
	}
}
