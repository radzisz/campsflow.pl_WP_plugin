<?php
declare(strict_types=1);

namespace Campsflow\Admin;

use Campsflow\Config;
use Campsflow\CurrencyFormatter;
use Campsflow\PostType\EventPostType;
use Campsflow\PostType\SessionPostType;
use Campsflow\Taxonomy\DestinationTaxonomy;

/**
 * Adds "Otwórz w CampsFlow" action column to cf_event and cf_session list tables.
 */
final class AdminColumns {

	/** @var array<string,string> */
	private const CLASS_LABELS = array(
		'YOUTH_CAMP'    => 'Obóz młodzieżowy',
		'KIDS_CAMP'     => 'Obóz dla dzieci',
		'FAMILY_CAMP'   => 'Obóz rodzinny',
		'REGULAR_CAMP'  => 'Obóz wypoczynkowy',
		'LANGUAGE_CAMP' => 'Obóz językowy',
		'SPORTS_CAMP'   => 'Obóz sportowy',
		'SCHOOL_TRIP'   => 'Wycieczka szkolna',
		'DAY_CAMP'      => 'Półkolonie',
	);

	public function register(): void {
		add_filter( 'manage_' . EventPostType::SLUG . '_posts_columns', array( $this, 'addEventColumn' ) );
		add_action( 'manage_' . EventPostType::SLUG . '_posts_custom_column', array( $this, 'renderEventColumn' ), 10, 2 );
		add_action( 'restrict_manage_posts', array( $this, 'renderEventClassFilter' ) );
		add_action( 'pre_get_posts', array( $this, 'applyEventClassFilter' ) );

		add_filter( 'manage_' . SessionPostType::SLUG . '_posts_columns', array( $this, 'addSessionColumn' ) );
		add_action( 'manage_' . SessionPostType::SLUG . '_posts_custom_column', array( $this, 'renderSessionColumn' ), 10, 2 );
	}

	/**
	 * @param array<string, string> $columns
	 * @return array<string, string>
	 */
	public function addEventColumn( array $columns ): array {
		unset( $columns[ 'taxonomy-' . DestinationTaxonomy::SLUG ] );
		unset( $columns['taxonomy-cf_event_category'] );

		$result = array();
		foreach ( $columns as $key => $label ) {
			$result[ $key ] = $label;
			if ( $key === 'title' ) {
				$result['cf_destination_path']   = __( 'Kierunek', 'campsflow' );
				$result['cf_event_category_col'] = __( 'Kategoria', 'campsflow' );
				$result['cf_event_class']        = __( 'Klasa obozu', 'campsflow' );
			}
		}
		$result['cf_open'] = '<span class="dashicons dashicons-external" title="' . esc_attr__( 'Otwórz w CampsFlow', 'campsflow' ) . '"></span>';
		return $result;
	}

	public function renderEventColumn( string $column, int $postId ): void {
		if ( $column === 'cf_destination_path' ) {
			$this->renderDestinationPath( $postId );
			return;
		}

		if ( $column === 'cf_event_category_col' ) {
			$terms = get_the_terms( $postId, 'cf_event_category' );
			$name  = ( is_array( $terms ) && ! empty( $terms ) && $terms[0] instanceof \WP_Term )
				? $terms[0]->name
				: '';
			echo $name !== '' ? esc_html( $name ) : '<span style="color:#d1d5db">—</span>';
			return;
		}

		if ( $column === 'cf_event_class' ) {
			$code = (string) get_post_meta( $postId, 'cf_event_class', true );
			echo $code !== ''
				? esc_html( self::CLASS_LABELS[ $code ] ?? $code )
				: '<span style="color:#d1d5db">—</span>';
			return;
		}

		if ( $column !== 'cf_open' ) {
			return;
		}

		$cfEventId  = (string) get_post_meta( $postId, 'cf_event_id', true );
		$tenantSlug = (string) get_option( 'campsflow_tenant_slug', '' );

		if ( ! $cfEventId || ! $tenantSlug ) {
			echo '<span style="color:#d1d5db">—</span>';
			return;
		}

		$url = Config::adminEventUrl( $tenantSlug, $cfEventId );
		$this->renderLink( $url, $cfEventId );
	}

	/**
	 * @param array<string, string> $columns
	 * @return array<string, string>
	 */
	public function addSessionColumn( array $columns ): array {
		$columns['cf_sess_date_from'] = __( 'Początek', 'campsflow' );
		$columns['cf_sess_date_to']   = __( 'Koniec', 'campsflow' );
		$columns['cf_sess_transport'] = __( 'Transport', 'campsflow' );
		$columns['cf_sess_start']     = __( 'Zbiórka', 'campsflow' );
		$columns['cf_sess_price']     = __( 'Cena', 'campsflow' );
		$columns['cf_open']           = '<span class="dashicons dashicons-external" title="' . esc_attr__( 'Otwórz w CampsFlow', 'campsflow' ) . '"></span>';
		return $columns;
	}

	public function renderSessionColumn( string $column, int $postId ): void {
		match ( $column ) {
			'cf_sess_date_from' => $this->renderSessionDate( $postId, 'cf_date_from' ),
			'cf_sess_date_to'   => $this->renderSessionDate( $postId, 'cf_date_to' ),
			'cf_sess_transport' => $this->renderSessionTransport( $postId ),
			'cf_sess_start'     => $this->renderSessionStart( $postId ),
			'cf_sess_price'     => $this->renderSessionPrice( $postId ),
			default             => null,
		};

		if ( $column !== 'cf_open' ) {
			return;
		}

		$cfSessionId = (string) get_post_meta( $postId, 'cf_turnus_id', true );
		$tenantSlug  = (string) get_option( 'campsflow_tenant_slug', '' );

		if ( ! $cfSessionId || ! $tenantSlug ) {
			echo '<span style="color:#d1d5db">—</span>';
			return;
		}

		// Sessions need the parent event ID for the URL
		$eventPostId = (int) wp_get_post_parent_id( $postId );
		$cfEventId   = $eventPostId
			? (string) get_post_meta( $eventPostId, 'cf_event_id', true )
			: '';

		$url = $cfEventId
			? Config::adminSessionUrl( $tenantSlug, $cfEventId, $cfSessionId )
			: Config::adminUrl() . '/' . $tenantSlug;

		$this->renderLink( $url, $cfSessionId );
	}

	private function renderSessionDate( int $postId, string $metaKey ): void {
		$raw = (string) get_post_meta( $postId, $metaKey, true );
		if ( $raw === '' ) {
			echo '<span style="color:#d1d5db">—</span>';
			return;
		}
		echo esc_html( date_i18n( 'd.m.Y', strtotime( $raw ) ) );
	}

	private function renderSessionTransport( int $postId ): void {
		$raw       = (string) get_post_meta( $postId, 'cf_transport', true );
		$transport = json_decode( $raw, true );
		$type      = is_array( $transport ) ? (string) ( $transport['type'] ?? '' ) : '';

		if ( $type === '' ) {
			echo '<span style="color:#d1d5db">—</span>';
			return;
		}

		echo esc_html( $type );
	}

	private function renderSessionStart( int $postId ): void {
		$raw    = (string) get_post_meta( $postId, 'cf_meeting_points_start', true );
		$points = json_decode( $raw, true );

		if ( ! is_array( $points ) || $points === array() ) {
			echo '<span style="color:#d1d5db">—</span>';
			return;
		}

		$first = $points[0];
		$name  = is_array( $first ) ? (string) ( $first['name'] ?? '' ) : '';

		if ( $name === '' ) {
			echo '<span style="color:#d1d5db">—</span>';
			return;
		}

		echo esc_html( $name );
	}

	private function renderSessionPrice( int $postId ): void {
		$grosze = (int) get_post_meta( $postId, 'cf_price_from', true );

		if ( $grosze <= 0 ) {
			echo '<span style="color:#d1d5db">—</span>';
			return;
		}

		$rawCurrency = get_post_meta( $postId, 'cf_currency', true );
		$currency    = $rawCurrency !== '' && $rawCurrency !== false ? (string) $rawCurrency : 'PLN';
		echo esc_html( CurrencyFormatter::format( $grosze, $currency ) );
	}

	public function renderEventClassFilter( string $postType ): void {
		if ( $postType !== EventPostType::SLUG ) {
			return;
		}

		$current = isset( $_GET['cf_event_class'] ) ? sanitize_key( (string) $_GET['cf_event_class'] ) : '';

		echo '<select name="cf_event_class">';
		echo '<option value="">' . esc_html__( 'Wszystkie typy', 'campsflow' ) . '</option>';
		foreach ( self::CLASS_LABELS as $code => $label ) {
			$selected = selected( $current, $code, false );
			echo '<option value="' . esc_attr( $code ) . '"' . $selected . '>' . esc_html( $label ) . '</option>';
		}
		echo '</select>';
	}

	public function applyEventClassFilter( \WP_Query $query ): void {
		if ( ! is_admin() || ! $query->is_main_query() ) {
			return;
		}

		$postType = $query->get( 'post_type' );
		if ( $postType !== EventPostType::SLUG ) {
			return;
		}

		$code = isset( $_GET['cf_event_class'] ) ? sanitize_key( (string) $_GET['cf_event_class'] ) : '';
		if ( $code === '' ) {
			return;
		}

		$existing = $query->get( 'meta_query' );
		$meta     = is_array( $existing ) ? $existing : array();
		$meta[]   = array(
			'key'     => 'cf_event_class',
			'value'   => $code,
			'compare' => '=',
		);
		$query->set( 'meta_query', $meta );
	}

	private function renderDestinationPath( int $postId ): void {
		$terms = get_the_terms( $postId, DestinationTaxonomy::SLUG );

		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			echo '<span style="color:#d1d5db">—</span>';
			return;
		}

		$parts = array();
		foreach ( $terms as $term ) {
			$filterUrl = add_query_arg(
				array(
					'post_type'               => EventPostType::SLUG,
					DestinationTaxonomy::SLUG => $term->slug,
				),
				admin_url( 'edit.php' )
			);

			$label = $term->name;
			if ( $term->parent > 0 ) {
				$parent = get_term( $term->parent, DestinationTaxonomy::SLUG );
				if ( $parent instanceof \WP_Term ) {
					$label = $parent->name . ' → ' . $term->name;
				}
			}

			$parts[] = '<a href="' . esc_url( $filterUrl ) . '">' . esc_html( $label ) . '</a>';
		}

		echo implode( ', ', $parts );
	}

	private function renderLink( string $url, string $cfId ): void {
		$shortId = substr( $cfId, 0, 8 ) . '…';
		echo '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener" class="cf-open-link" title="' . esc_attr( $cfId ) . '">';
		echo '<span class="dashicons dashicons-external"></span>';
		echo '<span class="cf-open-link__id">' . esc_html( $shortId ) . '</span>';
		echo '</a>';
		echo '<style>
            .cf-open-link { display:inline-flex; align-items:center; gap:4px; text-decoration:none; color:#2563eb; font-size:12px; }
            .cf-open-link:hover { color:#1d4ed8; }
            .cf-open-link .dashicons { font-size:14px; width:14px; height:14px; }
            .cf-open-link__id { font-family:monospace; font-size:11px; color:#6b7280; }
            .column-cf_open { width:120px; }
        </style>';
	}
}
