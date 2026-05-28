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

		$this->add_control(
			'show_home',
			array(
				'label'        => __( 'Link "Główna"', 'campsflow' ),
				'type'         => Controls_Manager::SWITCHER,
				'default'      => 'yes',
				'label_on'     => __( 'Tak', 'campsflow' ),
				'label_off'    => __( 'Nie', 'campsflow' ),
				'return_value' => 'yes',
			)
		);

		$this->add_control(
			'home_label',
			array(
				'label'       => __( 'Etykieta "Główna"', 'campsflow' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'Główna', 'campsflow' ),
				'description' => __( 'Zostaw puste, żeby wyświetlić ikonę domu.', 'campsflow' ),
				'condition'   => array( 'show_home' => 'yes' ),
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
					'{{WRAPPER}} .cf-breadcrumb__link' => 'color: {{VALUE}}',
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
		$showHome  = ( $s['show_home'] ?? 'yes' ) === 'yes';
		$homeLabel = (string) ( $s['home_label'] ?? __( 'Główna', 'campsflow' ) );
		$postId    = (int) get_the_ID();

		if ( ! $postId ) {
			if ( \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				$this->renderPlaceholder( $mode, $separator, $showHome, $homeLabel );
			}
			return;
		}

		if ( 'season_class' === $mode ) {
			$this->renderSeasonClass( $postId, $separator, $showHome, $homeLabel );
		} else {
			$this->renderLocalization( $postId, $separator, $depth, $showHome, $homeLabel );
		}
	}

	private function renderLocalization( int $postId, string $separator, string $depth, bool $showHome, string $homeLabel ): void {
		$raw = get_post_meta( $postId, 'cf_localization', true );
		$loc = is_array( $raw ) ? $raw : json_decode( (string) $raw, true );

		if ( ! is_array( $loc ) ) {
			return;
		}

		$countryCode = (string) ( is_array( $loc['address'] ?? null ) ? ( $loc['address']['country'] ?? '' ) : '' );
		$countryName = $countryCode !== '' ? self::resolveCountryName( $countryCode ) : '';
		$destination = (string) ( $loc['destination'] ?? '' );
		$city        = (string) ( is_array( $loc['address'] ?? null ) ? ( $loc['address']['city'] ?? '' ) : '' );

		$searchUrl   = SearchPage::pageUrl();
		$countrySlug = '';
		$destSlug    = '';

		$destTerms = get_the_terms( $postId, 'cf_destination' );
		if ( is_array( $destTerms ) && ! empty( $destTerms ) && $destTerms[0] instanceof \WP_Term ) {
			$child    = $destTerms[0];
			$destSlug = $child->slug;
			if ( $child->parent > 0 ) {
				$parent = get_term( $child->parent, 'cf_destination' );
				if ( $parent instanceof \WP_Term ) {
					$countrySlug = $parent->slug;
				}
			}
		}

		$items = array();
		if ( $countryName !== '' ) {
			$url     = $countrySlug !== '' ? add_query_arg( 'destination', $countrySlug, $searchUrl ) : $searchUrl;
			$items[] = array( $countryName, $url );
		}
		if ( $destination !== '' ) {
			$url     = $destSlug !== '' ? add_query_arg( 'destination', $destSlug, $searchUrl ) : $searchUrl;
			$items[] = array( $destination, $url );
		}
		if ( '3' === $depth && $city !== '' ) {
			$url     = $destSlug !== '' ? add_query_arg( 'destination', $destSlug, $searchUrl ) : $searchUrl;
			$items[] = array( $city, $url );
		}

		$this->echoItems( $items, $separator, $showHome, $homeLabel );
	}

	private function renderSeasonClass( int $postId, string $separator, bool $showHome, string $homeLabel ): void {
		$searchUrl = SearchPage::pageUrl();

		$seasons = wp_get_post_terms( $postId, 'cf_season' );
		$season  = ( is_array( $seasons ) && ! empty( $seasons ) && $seasons[0] instanceof \WP_Term )
			? $seasons[0]->name
			: '';

		$cats         = wp_get_post_terms( $postId, 'cf_event_category' );
		$categoryTerm = ( is_array( $cats ) && ! empty( $cats ) && $cats[0] instanceof \WP_Term )
			? $cats[0]
			: null;
		$category     = $categoryTerm ? $categoryTerm->name : '';

		$items = array();
		if ( $season !== '' ) {
			$items[] = array( $season, $searchUrl );
		}
		if ( $category !== '' ) {
			$url     = $categoryTerm ? add_query_arg( 'category', $categoryTerm->slug, $searchUrl ) : $searchUrl;
			$items[] = array( $category, $url );
		}

		$this->echoItems( $items, $separator, $showHome, $homeLabel );
	}

	private function renderPlaceholder( string $mode, string $separator, bool $showHome, string $homeLabel ): void {
		$searchUrl = SearchPage::pageUrl();
		$items     = 'season_class' === $mode
			? array(
				array( __( 'Lato', 'campsflow' ), $searchUrl ),
				array( __( 'Obóz przygodowy', 'campsflow' ), $searchUrl ),
			)
			: array(
				array( __( 'Polska', 'campsflow' ), add_query_arg( 'destination', 'pl', $searchUrl ) ),
				array( __( 'Bieszczady', 'campsflow' ), add_query_arg( 'destination', 'bieszczady', $searchUrl ) ),
			);
		$this->echoItems( $items, $separator, $showHome, $homeLabel );
	}

	/**
	 * @param array<int, array{string, string}> $items [label, url] pairs
	 */
	private function echoItems( array $items, string $separator, bool $showHome, string $homeLabel ): void {
		if ( empty( $items ) && ! $showHome ) {
			return;
		}

		$sep = $separator !== '' ? $separator : '›';

		echo '<nav class="cf-breadcrumb" aria-label="' . esc_attr__( 'Ścieżka nawigacji', 'campsflow' ) . '">';

		if ( $showHome ) {
			$homeUrl = SearchPage::pageUrl();
			echo '<a class="cf-breadcrumb__link cf-breadcrumb__home" href="' . esc_url( $homeUrl ) . '">';
			if ( $homeLabel !== '' ) {
				echo esc_html( $homeLabel );
			} else {
				echo '<svg width="14" height="14" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" focusable="false"><path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/></svg>';
			}
			echo '</a>';
			if ( ! empty( $items ) ) {
				echo '<span class="cf-breadcrumb__sep" aria-hidden="true">' . esc_html( $sep ) . '</span>';
			}
		}

		$last = array_key_last( $items );
		foreach ( $items as $idx => [ $label, $url ] ) {
			if ( $idx === $last ) {
				echo '<span class="cf-breadcrumb__item" aria-current="page">' . esc_html( $label ) . '</span>';
			} else {
				echo '<a class="cf-breadcrumb__link cf-breadcrumb__item" href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>';
			}
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
