<?php
declare(strict_types=1);

namespace Campsflow\Presentation;

use Campsflow\PostType\EventPostType;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;
use WP_Query;

final class SearchResultsWidget extends Widget_Base {

	public function get_name(): string {
		return 'campsflow_search_results';
	}

	public function get_title(): string {
		return __( 'CampsFlow — Wyniki wyszukiwania', 'campsflow' );
	}

	public function get_icon(): string {
		return 'eicon-gallery-grid';
	}

	public function get_categories(): array {
		return array( 'campsflow_search' );
	}

	protected function register_controls(): void {
		$this->start_controls_section(
			'section_layout',
			array(
				'label' => __( 'Układ', 'campsflow' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);
		$this->add_control(
			'cf_results_tip',
			array(
				'type'            => Controls_Manager::RAW_HTML,
				'raw'             => __( 'Reaguje na każdą zmianę URL wywoływaną przez widgety <strong>Pole filtru</strong>. Umieść go w dowolnym miejscu strony — nie musi być obok filtrów.', 'campsflow' ),
				'content_classes' => 'elementor-descriptor',
			)
		);
		$this->add_control(
			'columns',
			array(
				'label'   => __( 'Liczba kolumn', 'campsflow' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 3,
				'min'     => 1,
				'max'     => 4,
				'step'    => 1,
			)
		);
		$this->add_control(
			'per_page',
			array(
				'label'   => __( 'Kart na stronę', 'campsflow' ),
				'type'    => Controls_Manager::NUMBER,
				'default' => 12,
				'min'     => 1,
				'max'     => 100,
				'step'    => 1,
			)
		);
		$this->end_controls_section();
		$this->registerCardSection();
		$this->registerStyleCardSection();
		$this->registerStylePaginationSection();
	}

	private function registerCardSection(): void {
		$this->start_controls_section(
			'section_card',
			array(
				'label' => __( 'Karta wynikowa', 'campsflow' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);
		$this->add_control(
			'heading_title',
			array(
				'label'     => __( 'Tytuł', 'campsflow' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);
		$this->add_control(
			'show_title',
			array(
				'label'     => __( 'Pokaż tytuł', 'campsflow' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'label_on'  => __( 'Tak', 'campsflow' ),
				'label_off' => __( 'Nie', 'campsflow' ),
			)
		);
		$this->addPositionControls( 'title', 5, array( 'show_title' => 'yes' ), 'on_image_bottom_right' );
		$this->registerCardTagControls();
		$this->registerCardInfoControls();
		$this->add_control(
			'heading_button',
			array(
				'label'     => __( 'Przycisk', 'campsflow' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);
		$this->add_control(
			'button_text',
			array(
				'label'       => __( 'Tekst przycisku', 'campsflow' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => __( 'Szczegóły', 'campsflow' ),
			)
		);
		$this->addPositionControls( 'button', 60 );
		$this->end_controls_section();
	}

	private function registerCardTagControls(): void {
		$this->add_control(
			'heading_profile_tags',
			array(
				'label'     => __( 'Profil obozu', 'campsflow' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);
		$this->add_control(
			'show_profile_tags',
			array(
				'label'     => __( 'Pokaż', 'campsflow' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'label_on'  => __( 'Tak', 'campsflow' ),
				'label_off' => __( 'Nie', 'campsflow' ),
			)
		);
		$this->add_control(
			'profile_tags_label',
			array(
				'label'     => __( 'Nagłówek sekcji', 'campsflow' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => '',
				'condition' => array( 'show_profile_tags' => 'yes' ),
			)
		);
		$this->add_control(
			'profile_tags_style',
			array(
				'label'     => __( 'Styl wyświetlania', 'campsflow' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'badge',
				'options'   => array(
					'badge' => __( 'Odznaka (badge)', 'campsflow' ),
					'text'  => __( 'Tekst', 'campsflow' ),
				),
				'condition' => array( 'show_profile_tags' => 'yes' ),
			)
		);
		$this->addPositionControls( 'profile_tags', 10, array( 'show_profile_tags' => 'yes' ) );

		$this->add_control(
			'heading_event_tags',
			array(
				'label'     => __( 'Tagi promocyjne', 'campsflow' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);
		$this->add_control(
			'show_event_tags',
			array(
				'label'     => __( 'Pokaż', 'campsflow' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'label_on'  => __( 'Tak', 'campsflow' ),
				'label_off' => __( 'Nie', 'campsflow' ),
			)
		);
		$this->add_control(
			'event_tags_label',
			array(
				'label'     => __( 'Nagłówek sekcji', 'campsflow' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => '',
				'condition' => array( 'show_event_tags' => 'yes' ),
			)
		);
		$this->addPositionControls( 'event_tags', 20, array( 'show_event_tags' => 'yes' ), 'on_image_top_left' );

		$this->add_control(
			'heading_age_tags',
			array(
				'label'     => __( 'Grupa wiekowa', 'campsflow' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);
		$this->add_control(
			'show_age_tags',
			array(
				'label'     => __( 'Pokaż', 'campsflow' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'label_on'  => __( 'Tak', 'campsflow' ),
				'label_off' => __( 'Nie', 'campsflow' ),
			)
		);
		$this->add_control(
			'age_tags_label',
			array(
				'label'     => __( 'Nagłówek sekcji', 'campsflow' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => '',
				'condition' => array( 'show_age_tags' => 'yes' ),
			)
		);
		$this->add_control(
			'age_tags_style',
			array(
				'label'     => __( 'Styl wyświetlania', 'campsflow' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'badge',
				'options'   => array(
					'badge' => __( 'Odznaka (badge)', 'campsflow' ),
					'text'  => __( 'Tekst', 'campsflow' ),
				),
				'condition' => array( 'show_age_tags' => 'yes' ),
			)
		);
		$this->addPositionControls( 'age_tags', 30, array( 'show_age_tags' => 'yes' ) );
	}

	/**
	 * @param array<string, string> $condition
	 */
	private function addPositionControls( string $key, int $defaultOrder, array $condition = array(), string $defaultPlacement = 'below' ): void {
		$this->add_control(
			$key . '_placement',
			array(
				'label'     => __( 'Położenie', 'campsflow' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => $defaultPlacement,
				'options'   => array(
					'below'                 => __( 'Pod obrazkiem', 'campsflow' ),
					'on_image_top_left'     => __( 'Na obrazku — lewy górny', 'campsflow' ),
					'on_image_top_right'    => __( 'Na obrazku — prawy górny', 'campsflow' ),
					'on_image_bottom_left'  => __( 'Na obrazku — lewy dolny', 'campsflow' ),
					'on_image_bottom_right' => __( 'Na obrazku — prawy dolny', 'campsflow' ),
				),
				'condition' => $condition,
			)
		);
		$this->add_control(
			$key . '_order',
			array(
				'label'     => __( 'Kolejność', 'campsflow' ),
				'type'      => Controls_Manager::NUMBER,
				'default'   => $defaultOrder,
				'min'       => 1,
				'max'       => 999,
				'step'      => 1,
				'condition' => $condition,
			)
		);
	}

	private function registerStyleCardSection(): void {
		$this->start_controls_section(
			'section_style_card',
			array(
				'label' => __( 'Karta wynikowa — styl', 'campsflow' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);
		$this->add_control(
			'card_bg',
			array(
				'label'     => __( 'Tło karty', 'campsflow' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .cf-card' => 'background: {{VALUE}}',
				),
			)
		);
		$this->add_control(
			'card_border_radius',
			array(
				'label'      => __( 'Zaokrąglenie rogów', 'campsflow' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .cf-card' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}; overflow: hidden',
				),
			)
		);
		$this->add_group_control(
			Group_Control_Border::get_type(),
			array(
				'name'     => 'card_border',
				'selector' => '{{WRAPPER}} .cf-card',
			)
		);
		$this->add_group_control(
			Group_Control_Box_Shadow::get_type(),
			array(
				'name'           => 'card_shadow',
				'selector'       => '{{WRAPPER}} .cf-card',
				'fields_options' => array(
					'box_shadow_type' => array(
						'default' => 'yes',
					),
					'box_shadow'      => array(
						'default' => array(
							'horizontal' => 0,
							'vertical'   => 2,
							'blur'       => 8,
							'spread'     => 0,
							'color'      => 'rgba(0,0,0,0.08)',
						),
					),
				),
			)
		);
		$this->add_control(
			'card_title_color',
			array(
				'label'     => __( 'Kolor tytułu', 'campsflow' ),
				'type'      => Controls_Manager::COLOR,
				'separator' => 'before',
				'selectors' => array(
					'{{WRAPPER}} .cf-card__title a' => 'color: {{VALUE}}',
				),
			)
		);
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'card_title_typography',
				'selector' => '{{WRAPPER}} .cf-card__title a',
			)
		);
		$this->add_control(
			'card_accent',
			array(
				'label'     => __( 'Kolor akcentu (przycisk)', 'campsflow' ),
				'type'      => Controls_Manager::COLOR,
				'separator' => 'before',
				'selectors' => array(
					'{{WRAPPER}} .cf-btn' => 'background: {{VALUE}}',
				),
			)
		);
		$this->add_control(
			'card_btn_color',
			array(
				'label'     => __( 'Kolor tekstu przycisku', 'campsflow' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .cf-btn' => 'color: {{VALUE}}',
				),
			)
		);
		$this->add_control(
			'card_btn_radius',
			array(
				'label'      => __( 'Zaokrąglenie przycisku', 'campsflow' ),
				'type'       => Controls_Manager::DIMENSIONS,
				'size_units' => array( 'px', '%' ),
				'selectors'  => array(
					'{{WRAPPER}} .cf-btn' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}}',
				),
			)
		);
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'card_btn_typography',
				'selector' => '{{WRAPPER}} .cf-btn',
			)
		);
		$this->end_controls_section();
	}

	private function registerStylePaginationSection(): void {
		$this->start_controls_section(
			'section_style_pagination',
			array(
				'label' => __( 'Paginacja — styl', 'campsflow' ),
				'tab'   => Controls_Manager::TAB_STYLE,
			)
		);
		$this->add_control(
			'pagination_btn_bg',
			array(
				'label'     => __( 'Tło przycisku', 'campsflow' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .cf-pagination__btn' => 'background: {{VALUE}}',
				),
			)
		);
		$this->add_control(
			'pagination_btn_color',
			array(
				'label'     => __( 'Kolor tekstu', 'campsflow' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .cf-pagination__btn' => 'color: {{VALUE}}',
				),
			)
		);
		$this->add_control(
			'pagination_btn_border_color',
			array(
				'label'     => __( 'Kolor ramki', 'campsflow' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .cf-pagination__btn' => 'border-color: {{VALUE}}',
				),
			)
		);
		$this->add_control(
			'pagination_btn_radius',
			array(
				'label'      => __( 'Zaokrąglenie rogów', 'campsflow' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px', '%' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 50,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .cf-pagination__btn' => 'border-radius: {{SIZE}}{{UNIT}}',
				),
			)
		);
		$this->add_group_control(
			Group_Control_Typography::get_type(),
			array(
				'name'     => 'pagination_typography',
				'selector' => '{{WRAPPER}} .cf-pagination__btn',
			)
		);
		$this->add_control(
			'pagination_active_bg',
			array(
				'label'     => __( 'Tło aktywnej strony', 'campsflow' ),
				'type'      => Controls_Manager::COLOR,
				'separator' => 'before',
				'selectors' => array(
					'{{WRAPPER}} .cf-pagination__btn.is-active' => 'background: {{VALUE}}',
				),
			)
		);
		$this->add_control(
			'pagination_active_color',
			array(
				'label'     => __( 'Kolor tekstu aktywnej strony', 'campsflow' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .cf-pagination__btn.is-active' => 'color: {{VALUE}}',
				),
			)
		);
		$this->add_control(
			'pagination_active_border_color',
			array(
				'label'     => __( 'Kolor ramki aktywnej strony', 'campsflow' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .cf-pagination__btn.is-active' => 'border-color: {{VALUE}}',
				),
			)
		);
		$this->add_control(
			'pagination_gap',
			array(
				'label'     => __( 'Odstęp między przyciskami', 'campsflow' ),
				'type'      => Controls_Manager::SLIDER,
				'separator' => 'before',
				'range'     => array(
					'px' => array(
						'min'  => 0,
						'max'  => 24,
						'step' => 1,
					),
				),
				'selectors' => array(
					'{{WRAPPER}} .cf-pagination' => 'gap: {{SIZE}}px',
				),
			)
		);
		$this->end_controls_section();
	}

	private function registerCardInfoControls(): void {
		$this->add_control(
			'heading_price',
			array(
				'label'     => __( 'Cena', 'campsflow' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);
		$this->add_control(
			'price_suffix',
			array(
				'label'       => __( 'Sufiks ceny', 'campsflow' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '/os.',
				'placeholder' => '/os.',
			)
		);
		$this->add_control(
			'price_empty',
			array(
				'label'       => __( 'Tekst gdy brak ceny', 'campsflow' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => __( 'na zapytanie', 'campsflow' ),
				'placeholder' => __( 'na zapytanie', 'campsflow' ),
			)
		);
		$this->add_control(
			'heading_date',
			array(
				'label'     => __( 'Data', 'campsflow' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);
		$this->add_control(
			'show_date',
			array(
				'label'     => __( 'Pokaż', 'campsflow' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'label_on'  => __( 'Tak', 'campsflow' ),
				'label_off' => __( 'Nie', 'campsflow' ),
			)
		);
		$this->add_control(
			'date_label',
			array(
				'label'     => __( 'Nagłówek sekcji', 'campsflow' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => '',
				'condition' => array( 'show_date' => 'yes' ),
			)
		);
		$this->addPositionControls( 'date', 40, array( 'show_date' => 'yes' ) );

		$this->add_control(
			'heading_location',
			array(
				'label'     => __( 'Lokalizacja', 'campsflow' ),
				'type'      => Controls_Manager::HEADING,
				'separator' => 'before',
			)
		);
		$this->add_control(
			'show_location',
			array(
				'label'     => __( 'Pokaż', 'campsflow' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'label_on'  => __( 'Tak', 'campsflow' ),
				'label_off' => __( 'Nie', 'campsflow' ),
			)
		);
		$this->add_control(
			'location_label',
			array(
				'label'     => __( 'Nagłówek sekcji', 'campsflow' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => '',
				'condition' => array( 'show_location' => 'yes' ),
			)
		);
		$this->add_control(
			'location_mode',
			array(
				'label'     => __( 'Format', 'campsflow' ),
				'type'      => Controls_Manager::SELECT,
				'default'   => 'country_dest_city',
				'options'   => array(
					'country_dest'      => __( 'Kraj / Destynacja', 'campsflow' ),
					'country_dest_city' => __( 'Kraj / Destynacja / Miasto', 'campsflow' ),
				),
				'condition' => array( 'show_location' => 'yes' ),
			)
		);
		$this->addPositionControls( 'location', 50, array( 'show_location' => 'yes' ) );
	}

	protected function render(): void {
		$s       = $this->get_settings_for_display();
		$columns = max( 1, min( 4, (int) ( $s['columns'] ?? 3 ) ) );
		$perPage = min( 100, max( 1, (int) ( $s['per_page'] ?? 12 ) ) );
		$page    = max( 1, absint( sanitize_text_field( (string) ( $_GET['page'] ?? '1' ) ) ) );
		$config  = $this->buildCardConfig( $s );

		$endpointParams            = $this->buildEndpointParams( $config );
		$endpointParams['perPage'] = (string) $perPage;
		$endpoint                  = add_query_arg( $endpointParams, rest_url( 'campsflow/v1/events' ) );

		$result     = $this->queryEvents( $perPage, $page );
		$postIds    = $result['ids'];
		$totalPages = $result['total_pages'];
		$renderer   = new EventCardRenderer( $config );

		echo '<div class="cf-search-results" data-endpoint="' . esc_url( $endpoint ) . '" data-per-page="' . esc_attr( (string) $perPage ) . '" style="--cf-columns:' . esc_attr( (string) $columns ) . '">';
		echo $postIds ? $renderer->renderGrid( $postIds ) : $renderer->renderEmpty(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</div>';
		echo '<div class="cf-pagination-wrap">' . $this->renderPagination( $totalPages, $page ) . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * @param array<string, mixed> $s
	 * @return array<string, mixed>
	 */
	private function buildCardConfig( array $s ): array {
		$config   = array(
			'location_mode'      => in_array( $s['location_mode'] ?? '', array( 'country_dest', 'country_dest_city' ), true ) ? (string) $s['location_mode'] : 'country_dest_city',
			'show_title'         => ( $s['show_title'] ?? 'yes' ) === 'yes',
			'show_profile_tags'  => ( $s['show_profile_tags'] ?? 'yes' ) === 'yes',
			'profile_tags_label' => (string) ( $s['profile_tags_label'] ?? '' ),
			'profile_tags_style' => in_array( $s['profile_tags_style'] ?? 'badge', array( 'badge', 'text' ), true ) ? (string) $s['profile_tags_style'] : 'badge',
			'show_event_tags'    => ( $s['show_event_tags'] ?? 'yes' ) === 'yes',
			'event_tags_label'   => (string) ( $s['event_tags_label'] ?? '' ),
			'show_age_tags'      => ( $s['show_age_tags'] ?? 'yes' ) === 'yes',
			'age_tags_label'     => (string) ( $s['age_tags_label'] ?? '' ),
			'age_tags_style'     => in_array( $s['age_tags_style'] ?? 'badge', array( 'badge', 'text' ), true ) ? (string) $s['age_tags_style'] : 'badge',
			'show_date'          => ( $s['show_date'] ?? 'yes' ) === 'yes',
			'date_label'         => (string) ( $s['date_label'] ?? '' ),
			'show_location'      => ( $s['show_location'] ?? 'yes' ) === 'yes',
			'location_label'     => (string) ( $s['location_label'] ?? '' ),
			'button_text'        => (string) ( $s['button_text'] ?? '' ),
			'price_suffix'       => (string) ( $s['price_suffix'] ?? '/os.' ),
			'price_empty'        => (string) ( $s['price_empty'] ?? __( 'na zapytanie', 'campsflow' ) ),
		);
		$defaults = array(
			'title'        => 5,
			'profile_tags' => 10,
			'event_tags'   => 20,
			'age_tags'     => 30,
			'date'         => 40,
			'location'     => 50,
			'button'       => 60,
		);
		$validPl  = array( 'below', 'on_image_top_left', 'on_image_top_right', 'on_image_bottom_left', 'on_image_bottom_right' );
		foreach ( array_keys( $defaults ) as $key ) {
			$pl                            = (string) ( $s[ $key . '_placement' ] ?? '' );
			$config[ $key . '_placement' ] = in_array( $pl, $validPl, true ) ? $pl : 'below';
			$config[ $key . '_order' ]     = max( 1, (int) ( $s[ $key . '_order' ] ?? $defaults[ $key ] ) );
		}
		return $config;
	}

	/**
	 * @param array<string, mixed> $config
	 * @return array<string, string>
	 */
	private function buildEndpointParams( array $config ): array {
		$params = array(
			'locationMode'     => (string) $config['location_mode'],
			'showTitle'        => $config['show_title'] ? '1' : '0',
			'showProfileTags'  => $config['show_profile_tags'] ? '1' : '0',
			'profileTagsLabel' => (string) $config['profile_tags_label'],
			'profileTagsStyle' => (string) $config['profile_tags_style'],
			'showEventTags'    => $config['show_event_tags'] ? '1' : '0',
			'eventTagsLabel'   => (string) $config['event_tags_label'],
			'showAgeTags'      => $config['show_age_tags'] ? '1' : '0',
			'ageTagsLabel'     => (string) $config['age_tags_label'],
			'ageTagsStyle'     => (string) $config['age_tags_style'],
			'showDate'         => $config['show_date'] ? '1' : '0',
			'dateLabel'        => (string) $config['date_label'],
			'showLocation'     => $config['show_location'] ? '1' : '0',
			'locationLabel'    => (string) $config['location_label'],
			'buttonText'       => (string) $config['button_text'],
			'priceSuffix'      => (string) $config['price_suffix'],
			'priceEmpty'       => (string) $config['price_empty'],
		);
		foreach ( array( 'title', 'profile_tags', 'event_tags', 'age_tags', 'date', 'location', 'button' ) as $key ) {
			$params[ $key . '_placement' ] = (string) $config[ $key . '_placement' ];
			$params[ $key . '_order' ]     = (string) $config[ $key . '_order' ];
		}
		return $params;
	}

	/**
	 * @return array{ids: int[], total_pages: int}
	 */
	private function queryEvents( int $perPage, int $page ): array {
		$childAge  = absint( $_GET['childAge'] ?? 0 );
		$dateFrom  = sanitize_text_field( (string) ( $_GET['dateFrom'] ?? '' ) );
		$dateTo    = sanitize_text_field( (string) ( $_GET['dateTo'] ?? '' ) );
		$category  = sanitize_text_field( (string) ( $_GET['category'] ?? '' ) );
		$age       = sanitize_text_field( (string) ( $_GET['age'] ?? '' ) );
		$dest      = sanitize_text_field( (string) ( $_GET['destination'] ?? '' ) );
		$transport = sanitize_text_field( (string) ( $_GET['transport'] ?? '' ) );
		$sort      = sanitize_text_field( (string) ( $_GET['sort'] ?? '' ) );

		$taxQuery = array();
		if ( $category ) {
			$taxQuery[] = array(
				'taxonomy' => 'cf_event_category',
				'field'    => 'slug',
				'terms'    => $category,
			);
		}
		if ( $age ) {
			$taxQuery[] = array(
				'taxonomy' => 'cf_age_group',
				'field'    => 'slug',
				'terms'    => $age,
			);
		}
		if ( $dest ) {
			$taxQuery[] = array(
				'taxonomy' => 'cf_destination',
				'field'    => 'slug',
				'terms'    => $dest,
			);
		}
		if ( $transport ) {
			$taxQuery[] = array(
				'taxonomy' => 'cf_transport_type',
				'field'    => 'slug',
				'terms'    => $transport,
			);
		}

		$metaQuery = array();
		if ( $childAge >= 1 && $childAge <= 99 ) {
			$metaQuery[] = array(
				'key'     => 'cf_min_age',
				'value'   => $childAge,
				'compare' => '<=',
				'type'    => 'NUMERIC',
			);
			$metaQuery[] = array(
				'key'     => 'cf_max_age',
				'value'   => $childAge,
				'compare' => '>=',
				'type'    => 'NUMERIC',
			);
		}
		if ( $dateFrom ) {
			$metaQuery[] = array(
				'key'     => 'cf_date_earliest',
				'value'   => $dateFrom,
				'compare' => '>=',
				'type'    => 'DATE',
			);
		}
		if ( $dateTo ) {
			$metaQuery[] = array(
				'key'     => 'cf_date_earliest',
				'value'   => $dateTo,
				'compare' => '<=',
				'type'    => 'DATE',
			);
		}

		$orderArgs = $this->buildOrderArgs( $sort );
		$args      = array(
			'post_type'      => EventPostType::SLUG,
			'post_status'    => 'publish',
			'posts_per_page' => $perPage,
			'offset'         => ( $page - 1 ) * $perPage,
			'orderby'        => $orderArgs['orderby'],
			'order'          => $orderArgs['order'],
			'fields'         => 'ids',
		);
		if ( isset( $orderArgs['meta_key'] ) ) {
			$args['meta_key'] = $orderArgs['meta_key'];
		}
		if ( ! empty( $taxQuery ) ) {
			$args['tax_query'] = $taxQuery;
		}
		if ( ! empty( $metaQuery ) ) {
			$args['meta_query'] = $metaQuery;
		}

		$query      = new WP_Query( $args );
		$postIds    = array_map( static fn( $p ) => (int) ( $p instanceof \WP_Post ? $p->ID : $p ), (array) $query->posts );
		$totalCount = $query->found_posts;
		$totalPages = $perPage > 0 ? (int) ceil( $totalCount / $perPage ) : 1;

		return array(
			'ids'         => $postIds,
			'total_pages' => max( 1, $totalPages ),
		);
	}

	private function renderPagination( int $totalPages, int $currentPage ): string {
		if ( $totalPages <= 1 ) {
			return '';
		}

		// Show first, last, and a ±2 window around current page.
		$show = array();
		for ( $i = 1; $i <= $totalPages; $i++ ) {
			if ( $i === 1 || $i === $totalPages || abs( $i - $currentPage ) <= 2 ) {
				$show[] = $i;
			}
		}

		$html = '<nav class="cf-pagination" aria-label="' . esc_attr__( 'Strony wyników', 'campsflow' ) . '">';
		$prev = 0;
		foreach ( $show as $i ) {
			if ( $prev > 0 && $i - $prev > 1 ) {
				$html .= '<span class="cf-pagination__gap" aria-hidden="true">&#8230;</span>';
			}
			$cls   = $i === $currentPage ? 'cf-pagination__btn is-active' : 'cf-pagination__btn';
			$aria  = $i === $currentPage ? ' aria-current="page"' : '';
			$html .= '<button class="' . esc_attr( $cls ) . '" data-page="' . esc_attr( (string) $i ) . '"' . $aria . '>' . esc_html( (string) $i ) . '</button>';
			$prev  = $i;
		}
		$html .= '</nav>';
		return $html;
	}

	private function buildOrderArgs( string $sort ): array {
		if ( $sort === 'title_desc' ) {
			return array(
				'orderby' => 'title',
				'order'   => 'DESC',
			);
		}
		if ( $sort === 'date_asc' ) {
			return array(
				'orderby'  => 'meta_value',
				'meta_key' => 'cf_date_earliest',
				'order'    => 'ASC',
			);
		}
		if ( $sort === 'date_desc' ) {
			return array(
				'orderby'  => 'meta_value',
				'meta_key' => 'cf_date_earliest',
				'order'    => 'DESC',
			);
		}
		if ( $sort === 'price_asc' ) {
			return array(
				'orderby'  => 'meta_value_num',
				'meta_key' => 'cf_event_min_price',
				'order'    => 'ASC',
			);
		}
		if ( $sort === 'price_desc' ) {
			return array(
				'orderby'  => 'meta_value_num',
				'meta_key' => 'cf_event_min_price',
				'order'    => 'DESC',
			);
		}
		return array(
			'orderby' => 'title',
			'order'   => 'ASC',
		);
	}
}
