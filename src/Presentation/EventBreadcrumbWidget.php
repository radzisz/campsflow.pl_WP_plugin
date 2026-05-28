<?php
declare(strict_types=1);

namespace Campsflow\Presentation;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;

final class EventBreadcrumbWidget extends Widget_Base {

	public function get_name(): string {
		return 'campsflow_event_breadcrumb';
	}

	public function get_title(): string {
		return __( 'CampsFlow — Breadcrumb', 'campsflow' );
	}

	public function get_icon(): string {
		return 'eicon-navigation-horizontal';
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
			'mode',
			array(
				'label'   => __( 'Tryb', 'campsflow' ),
				'type'    => Controls_Manager::SELECT,
				'default' => 'localization',
				'options' => array(
					'localization' => __( 'Lokalizacja (kraj › region › miasto)', 'campsflow' ),
					'season_class' => __( 'Sezon › Rodzaj obozu', 'campsflow' ),
				),
			)
		);

		$this->add_control(
			'depth',
			array(
				'label'     => __( 'Liczba poziomów', 'campsflow' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => '2',
				'options'   => array(
					'2' => __( '2 — Kraj › Region', 'campsflow' ),
					'3' => __( '3 — Kraj › Region › Miasto', 'campsflow' ),
				),
				'condition' => array( 'mode' => 'localization' ),
			)
		);

		$this->add_control(
			'separator',
			array(
				'label'   => __( 'Separator', 'campsflow' ),
				'type'    => Controls_Manager::TEXT,
				'default' => '›',
			)
		);

		$this->end_controls_section();
	}

	private function registerStyleSection(): void {
		$this->start_controls_section(
			'section_style',
			array(
				'label' => __( 'Styl', 'campsflow' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);

		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'item_typography',
				'selector' => '{{WRAPPER}} .cf-breadcrumb',
			)
		);

		$this->add_control(
			'item_color',
			array(
				'label'     => __( 'Kolor tekstu', 'campsflow' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .cf-breadcrumb__item' => 'color: {{VALUE}}',
				),
			)
		);

		$this->add_control(
			'sep_color',
			array(
				'label'     => __( 'Kolor separatora', 'campsflow' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .cf-breadcrumb__sep' => 'color: {{VALUE}}',
				),
			)
		);

		$this->add_control(
			'gap',
			array(
				'label'     => __( 'Odstęp między elementami', 'campsflow' ),
				'type'      => Controls_Manager::SLIDER,
				'default'   => array( 'size' => 6 ),
				'range'     => array(
					'px' => array(
						'min'  => 0,
						'max'  => 32,
						'step' => 1,
					),
				),
				'selectors' => array(
					'{{WRAPPER}} .cf-breadcrumb' => 'gap: {{SIZE}}px',
				),
			)
		);

		$this->end_controls_section();
	}

	protected function render(): void {
		$s         = $this->get_settings_for_display();
		$mode      = (string) ( $s['mode'] ?? 'localization' );
		$depth     = (string) ( $s['depth'] ?? '2' );
		$separator = (string) ( $s['separator'] ?? '›' );
		$postId    = (int) get_the_ID();

		if ( ! $postId ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				$this->renderPlaceholder( $mode, $separator );
			}
			return;
		}

		if ( 'season_class' === $mode ) {
			$this->renderSeasonClass( $postId, $separator );
		} else {
			$this->renderLocalization( $postId, $separator, $depth );
		}
	}

	private function renderLocalization( int $postId, string $separator, string $depth ): void {
		$raw = (string) get_post_meta( $postId, 'cf_localization', true );
		$loc = json_decode( $raw, true );

		if ( ! is_array( $loc ) ) {
			return;
		}

		$countryCode = (string) ( is_array( $loc['address'] ?? null ) ? ( $loc['address']['country'] ?? '' ) : '' );
		$countryName = $countryCode !== '' ? self::resolveCountryName( $countryCode ) : '';
		$destination = (string) ( $loc['destination'] ?? '' );
		$city        = (string) ( is_array( $loc['address'] ?? null ) ? ( $loc['address']['city'] ?? '' ) : '' );

		$items = array_values( array_filter( array( $countryName, $destination ) ) );
		if ( '3' === $depth && $city !== '' ) {
			$items[] = $city;
		}

		$this->echoItems( $items, $separator );
	}

	private function renderSeasonClass( int $postId, string $separator ): void {
		$seasons = wp_get_post_terms( $postId, 'cf_season' );
		$season  = ( is_array( $seasons ) && ! empty( $seasons ) && $seasons[0] instanceof \WP_Term )
			? $seasons[0]->name
			: '';

		$cats     = wp_get_post_terms( $postId, 'cf_event_category' );
		$category = ( is_array( $cats ) && ! empty( $cats ) && $cats[0] instanceof \WP_Term )
			? $cats[0]->name
			: '';

		$items = array_values( array_filter( array( $season, $category ) ) );
		$this->echoItems( $items, $separator );
	}

	private function renderPlaceholder( string $mode, string $separator ): void {
		$items = 'season_class' === $mode
			? array( __( 'Lato', 'campsflow' ), __( 'Obóz przygodowy', 'campsflow' ) )
			: array( __( 'Polska', 'campsflow' ), __( 'Bieszczady', 'campsflow' ) );
		$this->echoItems( $items, $separator );
	}

	/**
	 * @param string[] $items
	 */
	private function echoItems( array $items, string $separator ): void {
		if ( empty( $items ) ) {
			return;
		}

		$sep = $separator !== '' ? $separator : '›';

		echo '<nav class="cf-breadcrumb" aria-label="' . esc_attr__( 'Ścieżka nawigacji', 'campsflow' ) . '">';
		$last = array_key_last( $items );
		foreach ( $items as $idx => $item ) {
			echo '<span class="cf-breadcrumb__item">' . esc_html( $item ) . '</span>';
			if ( $idx !== $last ) {
				echo '<span class="cf-breadcrumb__sep" aria-hidden="true">' . esc_html( $sep ) . '</span>';
			}
		}
		echo '</nav>';
	}

	private static function resolveCountryName( string $code ): string {
		$map = array(
			'PL' => 'Polska',
			'DE' => 'Niemcy',
			'CZ' => 'Czechy',
			'SK' => 'Słowacja',
			'AT' => 'Austria',
			'CH' => 'Szwajcaria',
			'IT' => 'Włochy',
			'FR' => 'Francja',
			'ES' => 'Hiszpania',
			'PT' => 'Portugalia',
			'GR' => 'Grecja',
			'HR' => 'Chorwacja',
			'SI' => 'Słowenia',
			'HU' => 'Węgry',
			'RO' => 'Rumunia',
			'BG' => 'Bułgaria',
			'TR' => 'Turcja',
			'MT' => 'Malta',
			'GB' => 'Wielka Brytania',
			'IE' => 'Irlandia',
			'NL' => 'Holandia',
			'BE' => 'Belgia',
			'DK' => 'Dania',
			'SE' => 'Szwecja',
			'NO' => 'Norwegia',
			'FI' => 'Finlandia',
			'ME' => 'Czarnogóra',
			'RS' => 'Serbia',
			'BA' => 'Bośnia i Hercegowina',
			'AL' => 'Albania',
			'MK' => 'Macedonia Północna',
		);
		return $map[ strtoupper( $code ) ] ?? $code;
	}
}
