<?php
declare(strict_types=1);

namespace Campsflow\Presentation;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;

final class EventAgeGroupWidget extends Widget_Base {

	public function get_name(): string {
		return 'campsflow_event_age_groups';
	}

	public function get_title(): string {
		return __( 'CampsFlow — Grupy wiekowe', 'campsflow' );
	}

	public function get_icon(): string {
		return 'eicon-person';
	}

	public function get_categories(): array {
		return array( 'campsflow' );
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
				'default'   => __( 'Grupy wiekowe', 'campsflow' ),
				'condition' => array( 'show_label' => 'yes' ),
			)
		);
		$this->add_control(
			'sort_order',
			array(
				'label'   => __( 'Sortowanie', 'campsflow' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'name_asc',
				'options' => array(
					'name_asc'  => __( 'Nazwa A→Z', 'campsflow' ),
					'name_desc' => __( 'Nazwa Z→A', 'campsflow' ),
					'default'   => __( 'Kolejność domyślna', 'campsflow' ),
				),
			)
		);
		$this->add_control(
			'max_terms',
			array(
				'label'       => __( 'Maksymalna liczba grup', 'campsflow' ),
				'type'        => Controls_Manager::NUMBER,
				'default'     => 0,
				'min'         => 0,
				'max'         => 50,
				'step'        => 1,
				'description' => __( '0 = pokaż wszystkie', 'campsflow' ),
			)
		);
		$this->add_control(
			'editor_placeholder',
			array(
				'label'       => __( 'Placeholder (tryb edycji)', 'campsflow' ),
				'type'        => Controls_Manager::TEXT,
				'description' => __( 'Widoczny tylko w edytorze gdy brak grup wiekowych.', 'campsflow' ),
			)
		);
		$this->end_controls_section();
	}

	private function registerStyleSection(): void {
		$this->start_controls_section(
			'section_style',
			array(
				'label' => __( 'Pillsy', 'campsflow' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);
		$this->add_control(
			'pill_gap',
			array(
				'label'     => __( 'Odstęp między pillsami (px)', 'campsflow' ),
				'type'      => Controls_Manager::SLIDER,
				'default'   => array( 'size' => 6 ),
				'range'     => array(
					'px' => array(
						'min'  => 0,
						'max'  => 40,
						'step' => 1,
					),
				),
				'selectors' => array(
					'{{WRAPPER}} .cf-tags' => 'gap: {{SIZE}}px',
				),
			)
		);
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'pill_typography',
				'selector' => '{{WRAPPER}} .cf-tag--age',
			)
		);
		$this->add_control(
			'pill_color',
			array(
				'label'     => __( 'Kolor tekstu', 'campsflow' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .cf-tag--age' => 'color: {{VALUE}}',
				),
			)
		);
		$this->add_control(
			'pill_bg',
			array(
				'label'     => __( 'Kolor tła', 'campsflow' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .cf-tag--age' => 'background: {{VALUE}}',
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
		$sortOrder   = sanitize_key( (string) ( $s['sort_order'] ?? 'name_asc' ) );
		$maxTerms    = max( 0, (int) ( $s['max_terms'] ?? 0 ) );
		$gap         = max( 0, (int) ( $s['pill_gap']['size'] ?? 6 ) );

		$terms = $postId ? get_the_terms( $postId, 'cf_age_group' ) : false;
		$terms = is_array( $terms ) ? $terms : array();

		if ( 'name_asc' === $sortOrder ) {
			usort( $terms, fn( $a, $b ) => strcmp( $a->name, $b->name ) );
		} elseif ( 'name_desc' === $sortOrder ) {
			usort( $terms, fn( $a, $b ) => strcmp( $b->name, $a->name ) );
		}

		if ( $maxTerms > 0 ) {
			$terms = array_slice( $terms, 0, $maxTerms );
		}

		$wrapStyle = 'display:flex;flex-wrap:wrap;gap:' . $gap . 'px';

		if ( empty( $terms ) ) {
			if ( '' !== $placeholder && \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<div class="cf-tags" style="' . esc_attr( $wrapStyle ) . '">';
				if ( $showLabel && '' !== $labelText ) {
					echo '<div class="cf-tags__label">' . esc_html( $labelText ) . '</div>';
				}
				echo '<p class="cf-tags--placeholder">' . esc_html( $placeholder ) . '</p>';
				echo '</div>';
			}
			return;
		}

		echo '<div class="cf-tags" style="' . esc_attr( $wrapStyle ) . '">';
		if ( $showLabel && '' !== $labelText ) {
			echo '<div class="cf-tags__label">' . esc_html( $labelText ) . '</div>';
		}
		foreach ( $terms as $term ) {
			echo '<span class="cf-tag cf-tag--age">' . esc_html( $term->name ) . '</span>';
		}
		echo '</div>';
	}
}
