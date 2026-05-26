<?php
declare(strict_types=1);

namespace Campsflow\Presentation;

use Elementor\Controls_Manager;
use Elementor\Widget_Base;

final class EventLeadVideoWidget extends Widget_Base {

	public function get_name(): string {
		return 'campsflow_event_lead_video';
	}

	public function get_title(): string {
		return __( 'CampsFlow — Wideo główne', 'campsflow' );
	}

	public function get_icon(): string {
		return 'eicon-youtube';
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
			'aspect_ratio',
			array(
				'label'   => __( 'Proporcje (aspekt)', 'campsflow' ),
				'type'    => Controls_Manager::SELECT,
				'default' => '16-9',
				'options' => array(
					'16-9' => '16:9',
					'4-3'  => '4:3',
					'1-1'  => '1:1',
				),
			)
		);
		$this->add_control(
			'editor_placeholder',
			array(
				'label'       => __( 'Placeholder (tryb edycji)', 'campsflow' ),
				'type'        => Controls_Manager::TEXT,
				'description' => __( 'Widoczny tylko w edytorze gdy brak URL wideo.', 'campsflow' ),
			)
		);
		$this->end_controls_section();
	}

	private function registerStyleSection(): void {
		$this->start_controls_section(
			'section_style',
			array(
				'label' => __( 'Kontener wideo', 'campsflow' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);
		$this->add_control(
			'border_radius',
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
					'{{WRAPPER}} .cf-video-wrap' => 'border-radius: {{SIZE}}{{UNIT}}; overflow: hidden',
				),
			)
		);
		$this->end_controls_section();
	}

	protected function render(): void {
		$s           = $this->get_settings_for_display();
		$postId      = (int) get_the_ID();
		$aspectRatio = (string) ( $s['aspect_ratio'] ?? '16-9' );
		$placeholder = sanitize_text_field( (string) ( $s['editor_placeholder'] ?? '' ) );

		$url = $postId ? (string) get_post_meta( $postId, 'cf_lead_video_url', true ) : '';

		if ( '' === $url ) {
			if ( '' !== $placeholder && \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<p class="cf-video--placeholder">' . esc_html( $placeholder ) . '</p>';
			}
			return;
		}

		$paddingMap = array(
			'16-9' => '56.25',
			'4-3'  => '75',
			'1-1'  => '100',
		);
		$padding    = $paddingMap[ $aspectRatio ] ?? '56.25';

		$embedUrl = $this->buildEmbedUrl( $url );

		echo '<div class="cf-video-wrap" style="padding-bottom:' . esc_attr( $padding ) . '%">';
		if ( '' !== $embedUrl ) {
			echo '<iframe src="' . esc_url( $embedUrl ) . '" allowfullscreen loading="lazy"></iframe>';
		} else {
			echo '<video src="' . esc_url( $url ) . '" controls></video>';
		}
		echo '</div>';
	}

	private function buildEmbedUrl( string $url ): string {
		$ytId = $this->extractYouTubeId( $url );
		if ( '' !== $ytId ) {
			return 'https://www.youtube.com/embed/' . $ytId;
		}

		$vimeoId = $this->extractVimeoId( $url );
		if ( '' !== $vimeoId ) {
			return 'https://player.vimeo.com/video/' . $vimeoId;
		}

		return '';
	}

	private function extractYouTubeId( string $url ): string {
		if ( preg_match( '#youtu\.be/([a-zA-Z0-9_-]{11})#', $url, $m ) ) {
			return $m[1];
		}
		if ( preg_match( '#[?&]v=([a-zA-Z0-9_-]{11})#', $url, $m ) ) {
			return $m[1];
		}
		return '';
	}

	private function extractVimeoId( string $url ): string {
		if ( preg_match( '#vimeo\.com/(?:video/)?(\d+)#', $url, $m ) ) {
			return $m[1];
		}
		return '';
	}
}
