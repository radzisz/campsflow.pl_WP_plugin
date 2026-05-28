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
		return array( 'campsflow' );
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
		$this->end_controls_section();
		$this->registerCardSection();
		$this->registerStyleCardSection();
	}

	private function registerCardSection(): void {
		$this->start_controls_section(
			'section_card',
			array(
				'label' => __( 'Karta wynikowa', 'campsflow' ),
				'tab'   => Controls_Manager::TAB_CONTENT,
			)
		);
		$this->registerCardTagControls();
		$this->registerCardInfoControls();
		$this->add_control(
			'button_text',
			array(
				'label'       => __( 'Tekst przycisku', 'campsflow' ),
				'type'        => Controls_Manager::TEXT,
				'default'     => '',
				'placeholder' => __( 'Szczegóły', 'campsflow' ),
			)
		);
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
				'name'     => 'card_shadow',
				'selector' => '{{WRAPPER}} .cf-card',
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

	private function registerCardInfoControls(): void {
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
				'default'   => 'country_dest',
				'options'   => array(
					'country_dest'      => __( 'Kraj / Destynacja', 'campsflow' ),
					'country_dest_city' => __( 'Kraj / Destynacja / Miasto', 'campsflow' ),
				),
				'condition' => array( 'show_location' => 'yes' ),
			)
		);
	}

	protected function render(): void {
		$s        = $this->get_settings_for_display();
		$columns  = max( 1, min( 4, (int) ( $s['columns'] ?? 3 ) ) );
		$config   = $this->buildCardConfig( $s );
		$endpoint = add_query_arg( $this->buildEndpointParams( $config ), rest_url( 'campsflow/v1/events' ) );
		$postIds  = $this->queryEventIds();
		$renderer = new EventCardRenderer( $config );

		echo '<div class="cf-search-results" data-endpoint="' . esc_url( $endpoint ) . '" style="--cf-columns:' . esc_attr( (string) $columns ) . '">';
		echo $postIds ? $renderer->renderGrid( $postIds ) : $renderer->renderEmpty(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo '</div>';
	}

	/**
	 * @param array<string, mixed> $s
	 * @return array<string, mixed>
	 */
	private function buildCardConfig( array $s ): array {
		return array(
			'location_mode'      => in_array( $s['location_mode'] ?? '', array( 'country_dest', 'country_dest_city' ), true ) ? (string) $s['location_mode'] : 'country_dest',
			'show_profile_tags'  => ( $s['show_profile_tags'] ?? 'yes' ) === 'yes',
			'profile_tags_label' => (string) ( $s['profile_tags_label'] ?? '' ),
			'show_event_tags'    => ( $s['show_event_tags'] ?? 'yes' ) === 'yes',
			'event_tags_label'   => (string) ( $s['event_tags_label'] ?? '' ),
			'show_age_tags'      => ( $s['show_age_tags'] ?? 'yes' ) === 'yes',
			'age_tags_label'     => (string) ( $s['age_tags_label'] ?? '' ),
			'show_date'          => ( $s['show_date'] ?? 'yes' ) === 'yes',
			'date_label'         => (string) ( $s['date_label'] ?? '' ),
			'show_location'      => ( $s['show_location'] ?? 'yes' ) === 'yes',
			'location_label'     => (string) ( $s['location_label'] ?? '' ),
			'button_text'        => (string) ( $s['button_text'] ?? '' ),
		);
	}

	/**
	 * @param array<string, mixed> $config
	 * @return array<string, string>
	 */
	private function buildEndpointParams( array $config ): array {
		return array(
			'locationMode'     => (string) $config['location_mode'],
			'showProfileTags'  => $config['show_profile_tags'] ? '1' : '0',
			'profileTagsLabel' => (string) $config['profile_tags_label'],
			'showEventTags'    => $config['show_event_tags'] ? '1' : '0',
			'eventTagsLabel'   => (string) $config['event_tags_label'],
			'showAgeTags'      => $config['show_age_tags'] ? '1' : '0',
			'ageTagsLabel'     => (string) $config['age_tags_label'],
			'showDate'         => $config['show_date'] ? '1' : '0',
			'dateLabel'        => (string) $config['date_label'],
			'showLocation'     => $config['show_location'] ? '1' : '0',
			'locationLabel'    => (string) $config['location_label'],
			'buttonText'       => (string) $config['button_text'],
		);
	}

	/**
	 * @return int[]
	 */
	private function queryEventIds(): array {
		$childAge  = absint( $_GET['childAge'] ?? 0 );
		$dateFrom  = sanitize_text_field( $_GET['dateFrom'] ?? '' );
		$dateTo    = sanitize_text_field( $_GET['dateTo'] ?? '' );
		$category  = sanitize_text_field( $_GET['category'] ?? '' );
		$age       = sanitize_text_field( $_GET['age'] ?? '' );
		$dest      = sanitize_text_field( $_GET['destination'] ?? '' );
		$transport = sanitize_text_field( $_GET['transport'] ?? '' );
		$sort      = sanitize_text_field( $_GET['sort'] ?? '' );

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
			'posts_per_page' => 24,
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

		$query = new WP_Query( $args );
		return array_map( static fn( $p ) => (int) ( $p instanceof \WP_Post ? $p->ID : $p ), (array) $query->posts );
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
