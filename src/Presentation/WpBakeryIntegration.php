<?php
declare(strict_types=1);

namespace Campsflow\Presentation;

/**
 * WPBakery Page Builder integration.
 * Maps our shortcodes to drag-and-drop elements in the WPBakery editor.
 * Only activates when WPBakery is loaded.
 */
final class WpBakeryIntegration {

	public function register(): void {
		add_action( 'vc_before_init', array( $this, 'mapElements' ) );
	}

	public function mapElements(): void {
		$this->mapListing();
		$this->mapSearchFilter();
		$this->mapSearchFilterField();
		$this->mapSearchSort();
		$this->mapSearchResults();
		$this->mapEventMeta();
		$this->mapEventSessions();
		$this->mapEventTags();
		$this->mapEventAgeGroups();
		$this->mapEventLeadImage();
		$this->mapEventGallery();
		$this->mapEventLeadVideo();
		$this->mapEventMap();
		$this->mapEventField();
		$this->mapEventContact();
		$this->mapEventDocuments();
		$this->mapEventBreadcrumb();
	}

	private function mapSearchFilter(): void {
		vc_map(
			array(
				'name'        => __( 'CampsFlow — Filtry wyszukiwania', 'campsflow' ),
				'base'        => 'campsflow_search_filter',
				'category'    => 'CampsFlow',
				'icon'        => 'dashicons-search',
				'description' => __( 'Formularz filtrów AJAX — umieść obok widgetu Wyniki wyszukiwania', 'campsflow' ),
				'params'      => array(
					array(
						'type'        => 'textfield',
						'heading'     => __( 'Widoczne pola', 'campsflow' ),
						'param_name'  => 'fields',
						'value'       => 'category,age,destination,transport,child_age,season,dates',
						'description' => __( 'Lista pól oddzielona przecinkami: category, age, destination, transport, child_age, season, dates', 'campsflow' ),
					),
					array(
						'type'       => 'checkbox',
						'heading'    => __( 'Przycisk reset', 'campsflow' ),
						'param_name' => 'show_reset',
						'value'      => array( __( 'Pokaż przycisk wyczyść filtry', 'campsflow' ) => 'yes' ),
						'std'        => 'yes',
					),
					array(
						'type'       => 'textfield',
						'heading'    => __( 'Tekst przycisku reset', 'campsflow' ),
						'param_name' => 'reset_label',
						'value'      => __( 'Wyczyść filtry', 'campsflow' ),
						'dependency' => array(
							'element' => 'show_reset',
							'value'   => array( 'yes' ),
						),
					),
				),
			)
		);
	}

	private function mapSearchFilterField(): void {
		vc_map(
			array(
				'name'        => __( 'CampsFlow — Pole filtru', 'campsflow' ),
				'base'        => 'campsflow_search_filter_field',
				'category'    => 'CampsFlow',
				'icon'        => 'dashicons-filter',
				'description' => __( 'Pojedyncze pole filtru — umieść wielokrotnie obok widgetu Wyniki wyszukiwania', 'campsflow' ),
				'params'      => array(
					array(
						'type'       => 'dropdown',
						'heading'    => __( 'Typ pola', 'campsflow' ),
						'param_name' => 'field',
						'value'      => array(
							__( 'Profil', 'campsflow' )    => 'category',
							__( 'Grupa wiekowa', 'campsflow' ) => 'age',
							__( 'Wiek dziecka', 'campsflow' ) => 'child_age',
							__( 'Kierunek', 'campsflow' )  => 'destination',
							__( 'Transport', 'campsflow' ) => 'transport',
							__( 'Sezon', 'campsflow' )     => 'season',
							__( 'Termin (zakres dat)', 'campsflow' ) => 'dates',
						),
						'std'        => 'category',
					),
					array(
						'type'       => 'textfield',
						'heading'    => __( 'Nagłówek', 'campsflow' ),
						'param_name' => 'header',
						'value'      => '',
					),
					array(
						'type'        => 'textfield',
						'heading'     => __( 'Label (opcja pusta)', 'campsflow' ),
						'param_name'  => 'placeholder',
						'value'       => '',
						'description' => __( 'Pozostaw puste, aby użyć domyślnego.', 'campsflow' ),
					),
				),
			)
		);
	}

	private function mapSearchSort(): void {
		vc_map(
			array(
				'name'        => __( 'CampsFlow — Sortowanie', 'campsflow' ),
				'base'        => 'campsflow_search_sort',
				'category'    => 'CampsFlow',
				'icon'        => 'dashicons-sort',
				'description' => __( 'Klikalne etykiety sortowania — kliknięcie pokazuje strzałkę, drugie odwraca kierunek', 'campsflow' ),
				'params'      => array(
					array(
						'type'       => 'textfield',
						'heading'    => __( 'Nagłówek', 'campsflow' ),
						'param_name' => 'header',
						'value'      => '',
					),
					array(
						'type'       => 'textfield',
						'heading'    => __( 'Separator', 'campsflow' ),
						'param_name' => 'separator',
						'value'      => '/',
					),
					array(
						'type'        => 'textfield',
						'heading'     => __( 'Widoczne opcje', 'campsflow' ),
						'param_name'  => 'show',
						'value'       => 'title,date,price',
						'description' => __( 'Przecinek: title, date, price', 'campsflow' ),
					),
					array(
						'type'       => 'textfield',
						'heading'    => __( 'Etykieta: Nazwa', 'campsflow' ),
						'param_name' => 'label_title',
						'value'      => '',
					),
					array(
						'type'       => 'textfield',
						'heading'    => __( 'Etykieta: Termin', 'campsflow' ),
						'param_name' => 'label_date',
						'value'      => '',
					),
					array(
						'type'       => 'textfield',
						'heading'    => __( 'Etykieta: Cena', 'campsflow' ),
						'param_name' => 'label_price',
						'value'      => '',
					),
				),
			)
		);
	}

	private function mapSearchResults(): void {
		vc_map(
			array(
				'name'        => __( 'CampsFlow — Wyniki wyszukiwania', 'campsflow' ),
				'base'        => 'campsflow_search_results',
				'category'    => 'CampsFlow',
				'icon'        => 'dashicons-grid-view',
				'description' => __( 'Siatka wyników reagująca na filtry AJAX', 'campsflow' ),
				'params'      => array(
					array(
						'type'       => 'dropdown',
						'heading'    => __( 'Kolumny', 'campsflow' ),
						'param_name' => 'columns',
						'value'      => array(
							'1' => '1',
							'2' => '2',
							'3' => '3',
							'4' => '4',
						),
						'std'        => '3',
					),
					array(
						'type'        => 'textfield',
						'heading'     => __( 'Wyniki na stronę', 'campsflow' ),
						'param_name'  => 'per_page',
						'value'       => '12',
						'description' => __( '0 = pokaż wszystkie', 'campsflow' ),
					),
					array(
						'type'       => 'checkbox',
						'heading'    => __( 'Elementy karty', 'campsflow' ),
						'param_name' => 'show_title',
						'value'      => array( __( 'Pokaż tytuł', 'campsflow' ) => 'yes' ),
						'std'        => 'yes',
					),
					array(
						'type'       => 'checkbox',
						'heading'    => '',
						'param_name' => 'show_date',
						'value'      => array( __( 'Pokaż termin', 'campsflow' ) => 'yes' ),
						'std'        => 'yes',
					),
					array(
						'type'       => 'checkbox',
						'heading'    => '',
						'param_name' => 'show_location',
						'value'      => array( __( 'Pokaż lokalizację', 'campsflow' ) => 'yes' ),
						'std'        => 'yes',
					),
					array(
						'type'       => 'dropdown',
						'heading'    => __( 'Format lokalizacji', 'campsflow' ),
						'param_name' => 'location_mode',
						'value'      => array(
							__( 'Kraj / Destynacja', 'campsflow' )         => 'country_dest',
							__( 'Kraj / Destynacja / Miasto', 'campsflow' ) => 'country_dest_city',
						),
						'std'        => 'country_dest_city',
					),
					array(
						'type'       => 'checkbox',
						'heading'    => '',
						'param_name' => 'show_profile_tags',
						'value'      => array( __( 'Pokaż tagi profilu', 'campsflow' ) => 'yes' ),
						'std'        => 'yes',
					),
					array(
						'type'       => 'checkbox',
						'heading'    => '',
						'param_name' => 'show_event_tags',
						'value'      => array( __( 'Pokaż tagi marketingowe', 'campsflow' ) => 'yes' ),
						'std'        => 'yes',
					),
					array(
						'type'       => 'checkbox',
						'heading'    => '',
						'param_name' => 'show_age_tags',
						'value'      => array( __( 'Pokaż grupy wiekowe', 'campsflow' ) => 'yes' ),
						'std'        => 'yes',
					),
					array(
						'type'        => 'textfield',
						'heading'     => __( 'Tekst przycisku', 'campsflow' ),
						'param_name'  => 'button_text',
						'value'       => '',
						'description' => __( 'Puste = domyślny tekst z ustawień', 'campsflow' ),
					),
					array(
						'type'       => 'textfield',
						'heading'    => __( 'Sufiks ceny', 'campsflow' ),
						'param_name' => 'price_suffix',
						'value'      => '/os.',
					),
					array(
						'type'       => 'textfield',
						'heading'    => __( 'Tekst gdy brak ceny', 'campsflow' ),
						'param_name' => 'price_empty',
						'value'      => __( 'na zapytanie', 'campsflow' ),
					),
				),
			)
		);
	}

	private function mapListing(): void {
		vc_map(
			array(
				'name'        => __( 'CampsFlow — Lista obozów', 'campsflow' ),
				'base'        => 'campsflow_listing',
				'category'    => 'CampsFlow',
				'icon'        => 'dashicons-flag',
				'description' => __( 'Wyświetla listę wydarzeń lub turnusów z filtrami', 'campsflow' ),
				'params'      => array(
					array(
						'type'        => 'dropdown',
						'heading'     => __( 'Widok', 'campsflow' ),
						'param_name'  => 'view',
						'value'       => array(
							__( 'Lista wydarzeń', 'campsflow' ) => 'events',
							__( 'Lista turnusów (płaska)', 'campsflow' ) => 'sessions',
						),
						'description' => __( 'Wydarzeniowy — z wyborem turnusu. Płaski — bezpośrednie zapisy.', 'campsflow' ),
					),
					array(
						'type'       => 'dropdown',
						'heading'    => __( 'Kolumny', 'campsflow' ),
						'param_name' => 'columns',
						'value'      => array(
							'1' => '1',
							'2' => '2',
							'3' => '3',
							'4' => '4',
						),
						'std'        => '3',
					),
				),
			)
		);
	}

	private function mapEventMeta(): void {
		vc_map(
			array(
				'name'        => __( 'CampsFlow — Szczegóły wydarzenia', 'campsflow' ),
				'base'        => 'campsflow_event_meta',
				'category'    => 'CampsFlow',
				'icon'        => 'dashicons-info-outline',
				'description' => __( 'Lokalizacja, tagi i opis aktualnego wydarzenia', 'campsflow' ),
				'params'      => array(
					array(
						'type'       => 'checkbox',
						'heading'    => __( 'Widoczność', 'campsflow' ),
						'param_name' => 'show',
						'value'      => array(
							__( 'Lokalizacja', 'campsflow' ) => 'location',
							__( 'Tagi', 'campsflow' )      => 'tags',
							__( 'Opis', 'campsflow' )      => 'description',
							__( 'Zdjęcia', 'campsflow' )   => 'photos',
							__( 'Program', 'campsflow' )   => 'program',
							__( 'Co zawiera cena', 'campsflow' ) => 'price_include',
							__( 'Dokumenty', 'campsflow' ) => 'documents',
							__( 'Warunki ogólne', 'campsflow' ) => 'terms',
							__( 'Inf. praktyczne', 'campsflow' ) => 'instructions',
							__( 'Kontakt', 'campsflow' )   => 'contact',
							__( 'Pola własne', 'campsflow' ) => 'custom_fields',
						),
						'std'        => 'location,tags,description',
					),
				),
			)
		);
	}

	private function mapEventTags(): void {
		vc_map(
			array(
				'name'        => __( 'CampsFlow — Tagi', 'campsflow' ),
				'base'        => 'campsflow_event_tags',
				'category'    => 'CampsFlow',
				'icon'        => 'dashicons-tag',
				'description' => __( 'Tagi taksonomiczne wydarzenia jako pillsy', 'campsflow' ),
				'params'      => array(
					array(
						'type'       => 'dropdown',
						'heading'    => __( 'Taksonomia', 'campsflow' ),
						'param_name' => 'taxonomy',
						'value'      => array(
							__( 'Profil wydarzenia', 'campsflow' ) => 'cf_event_category',
							__( 'Tagi marketingowe', 'campsflow' ) => 'cf_event_tag',
							__( 'Grupy wiekowe', 'campsflow' )     => 'cf_age_group',
						),
						'std'        => 'cf_event_category',
					),
					array(
						'type'       => 'dropdown',
						'heading'    => __( 'Sortowanie', 'campsflow' ),
						'param_name' => 'sort',
						'value'      => array(
							__( 'Nazwa A→Z', 'campsflow' ) => 'name_asc',
							__( 'Nazwa Z→A', 'campsflow' ) => 'name_desc',
							__( 'Kolejność domyślna', 'campsflow' ) => 'default',
						),
						'std'        => 'name_asc',
					),
					array(
						'type'        => 'textfield',
						'heading'     => __( 'Maksymalna liczba tagów', 'campsflow' ),
						'param_name'  => 'max',
						'value'       => '0',
						'description' => __( '0 = pokaż wszystkie', 'campsflow' ),
					),
					array(
						'type'       => 'textfield',
						'heading'    => __( 'Odstęp między tagami (px)', 'campsflow' ),
						'param_name' => 'gap',
						'value'      => '6',
					),
					array(
						'type'       => 'checkbox',
						'heading'    => __( 'Nagłówek', 'campsflow' ),
						'param_name' => 'show_label',
						'value'      => array( __( 'Pokaż nagłówek', 'campsflow' ) => 'yes' ),
					),
					array(
						'type'       => 'textfield',
						'heading'    => __( 'Tekst nagłówka', 'campsflow' ),
						'param_name' => 'label_text',
						'value'      => __( 'Tagi', 'campsflow' ),
					),
				),
			)
		);
	}

	private function mapEventAgeGroups(): void {
		vc_map(
			array(
				'name'        => __( 'CampsFlow — Grupy wiekowe', 'campsflow' ),
				'base'        => 'campsflow_event_age_groups',
				'category'    => 'CampsFlow',
				'icon'        => 'dashicons-groups',
				'description' => __( 'Grupy wiekowe wydarzenia jako pillsy', 'campsflow' ),
				'params'      => array(
					array(
						'type'       => 'dropdown',
						'heading'    => __( 'Sortowanie', 'campsflow' ),
						'param_name' => 'sort',
						'value'      => array(
							__( 'Nazwa A→Z', 'campsflow' ) => 'name_asc',
							__( 'Nazwa Z→A', 'campsflow' ) => 'name_desc',
							__( 'Kolejność domyślna', 'campsflow' ) => 'default',
						),
						'std'        => 'name_asc',
					),
					array(
						'type'        => 'textfield',
						'heading'     => __( 'Maksymalna liczba grup', 'campsflow' ),
						'param_name'  => 'max',
						'value'       => '0',
						'description' => __( '0 = pokaż wszystkie', 'campsflow' ),
					),
					array(
						'type'       => 'textfield',
						'heading'    => __( 'Odstęp między pillsami (px)', 'campsflow' ),
						'param_name' => 'gap',
						'value'      => '6',
					),
				),
			)
		);
	}

	private function mapEventField(): void {
		vc_map(
			array(
				'name'        => __( 'CampsFlow — Pole wydarzenia', 'campsflow' ),
				'base'        => 'campsflow_event_field',
				'category'    => 'CampsFlow',
				'icon'        => 'dashicons-text',
				'description' => __( 'Wyświetla pojedyncze pole danych aktualnego wydarzenia', 'campsflow' ),
				'params'      => array(
					array(
						'type'       => 'dropdown',
						'heading'    => __( 'Pole', 'campsflow' ),
						'param_name' => 'field',
						'value'      => array(
							__( 'Tytuł wydarzenia', 'campsflow' ) => 'post_title',
							__( 'Opis ogólny', 'campsflow' ) => 'cf_desc_general',
							__( 'Program', 'campsflow' )   => 'cf_desc_program',
							__( 'Co w cenie', 'campsflow' ) => 'cf_desc_price_include',
							__( 'Jak się przygotować', 'campsflow' ) => 'cf_instr_prepare',
							__( 'Co zabrać', 'campsflow' ) => 'cf_instr_take',
							__( 'Lokalizacja: nazwa miejsca', 'campsflow' ) => 'cf_loc_name',
							__( 'Lokalizacja: miejscowość', 'campsflow' ) => 'cf_loc_destination',
							__( 'Lokalizacja: miasto', 'campsflow' ) => 'cf_loc_city',
							__( 'Lokalizacja: ulica', 'campsflow' ) => 'cf_loc_street',
							__( 'Lokalizacja: telefon', 'campsflow' ) => 'cf_loc_phone',
							__( 'Lokalizacja: e-mail', 'campsflow' ) => 'cf_loc_email',
							__( 'Lokalizacja: www', 'campsflow' ) => 'cf_loc_webpage',
							__( 'Warunki: ubezpieczenie', 'campsflow' ) => 'cf_terms_insurance',
							__( 'Warunki: zamawianie leków', 'campsflow' ) => 'cf_terms_drug',
							__( 'Warunki: dieta specjalna', 'campsflow' ) => 'cf_terms_diet',
							__( 'Warunki: terminy i dokumenty', 'campsflow' ) => 'cf_terms_deadlines',
							__( 'URL rezerwacji', 'campsflow' ) => 'cf_reservation_url',
							__( 'Pole własne', 'campsflow' ) => 'custom',
						),
						'std'        => 'post_title',
					),
					array(
						'type'        => 'textfield',
						'heading'     => __( 'Klucz pola własnego', 'campsflow' ),
						'param_name'  => 'custom_key',
						'value'       => '',
						'description' => __( 'Wypełnij gdy wybrano "Pole własne".', 'campsflow' ),
					),
					array(
						'type'       => 'dropdown',
						'heading'    => __( 'Tryb renderowania', 'campsflow' ),
						'param_name' => 'render_mode',
						'value'      => array(
							__( 'Auto (wykryj HTML)', 'campsflow' )  => 'auto',
							__( 'Tekst (uciecz HTML)', 'campsflow' ) => 'text',
							__( 'HTML (renderuj)', 'campsflow' )     => 'html',
						),
						'std'        => 'auto',
					),
					array(
						'type'       => 'checkbox',
						'heading'    => __( 'Nagłówek', 'campsflow' ),
						'param_name' => 'show_label',
						'value'      => array( __( 'Pokaż nagłówek pola', 'campsflow' ) => '1' ),
					),
					array(
						'type'       => 'textfield',
						'heading'    => __( 'Tekst nagłówka', 'campsflow' ),
						'param_name' => 'label',
						'value'      => '',
					),
				),
			)
		);
	}

	private function mapEventLeadImage(): void {
		vc_map(
			array(
				'name'        => __( 'CampsFlow — Zdjęcie główne', 'campsflow' ),
				'base'        => 'campsflow_event_lead_image',
				'category'    => 'CampsFlow',
				'icon'        => 'dashicons-format-image',
				'description' => __( 'Zdjęcie główne aktualnego wydarzenia', 'campsflow' ),
				'params'      => array(
					array(
						'type'        => 'textfield',
						'heading'     => __( 'Tekst alternatywny (alt)', 'campsflow' ),
						'param_name'  => 'alt',
						'value'       => '',
						'description' => __( 'Jeśli puste, użyty zostanie tytuł wydarzenia.', 'campsflow' ),
					),
				),
			)
		);
	}

	private function mapEventGallery(): void {
		vc_map(
			array(
				'name'        => __( 'CampsFlow — Galeria', 'campsflow' ),
				'base'        => 'campsflow_event_gallery',
				'category'    => 'CampsFlow',
				'icon'        => 'dashicons-images-alt2',
				'description' => __( 'Galeria zdjęć jako siatka z lightboxem lub slider', 'campsflow' ),
				'params'      => array(
					array(
						'type'       => 'dropdown',
						'heading'    => __( 'Tryb', 'campsflow' ),
						'param_name' => 'mode',
						'value'      => array(
							__( 'Siatka + lightbox', 'campsflow' ) => 'built-in',
							__( 'Slider', 'campsflow' ) => 'slider',
						),
						'std'        => 'built-in',
					),
					array(
						'type'        => 'textfield',
						'heading'     => __( 'Kolumny (siatka)', 'campsflow' ),
						'param_name'  => 'columns',
						'value'       => '3',
						'description' => __( 'Liczba kolumn 2–6 (tylko tryb siatka)', 'campsflow' ),
					),
					array(
						'type'        => 'textfield',
						'heading'     => __( 'Zdjęcia obok siebie (slider)', 'campsflow' ),
						'param_name'  => 'slides_per_view',
						'value'       => '3',
						'description' => __( '1–6 (tylko tryb slider)', 'campsflow' ),
					),
					array(
						'type'       => 'checkbox',
						'heading'    => __( 'Opcje slidera', 'campsflow' ),
						'param_name' => 'show_arrows',
						'value'      => array( __( 'Pokaż strzałki', 'campsflow' ) => '1' ),
						'std'        => '1',
					),
					array(
						'type'       => 'checkbox',
						'heading'    => '',
						'param_name' => 'show_dots',
						'value'      => array( __( 'Pokaż kropki', 'campsflow' ) => '1' ),
						'std'        => '1',
					),
					array(
						'type'       => 'checkbox',
						'heading'    => '',
						'param_name' => 'autoplay',
						'value'      => array( __( 'Autoplay', 'campsflow' ) => '1' ),
					),
					array(
						'type'        => 'textfield',
						'heading'     => __( 'Czas autoplay (ms)', 'campsflow' ),
						'param_name'  => 'autoplay_speed',
						'value'       => '3000',
						'description' => __( 'Czas między slajdami w ms', 'campsflow' ),
					),
					array(
						'type'        => 'textfield',
						'heading'     => __( 'Czas animacji (ms)', 'campsflow' ),
						'param_name'  => 'animation_speed',
						'value'       => '400',
						'description' => __( 'Czas przejścia slajdu w ms', 'campsflow' ),
					),
				),
			)
		);
	}

	private function mapEventLeadVideo(): void {
		vc_map(
			array(
				'name'        => __( 'CampsFlow — Wideo główne', 'campsflow' ),
				'base'        => 'campsflow_event_lead_video',
				'category'    => 'CampsFlow',
				'icon'        => 'dashicons-video-alt3',
				'description' => __( 'Osadzone wideo (YouTube/Vimeo/plik) aktualnego wydarzenia', 'campsflow' ),
				'params'      => array(
					array(
						'type'       => 'dropdown',
						'heading'    => __( 'Proporcje (aspekt)', 'campsflow' ),
						'param_name' => 'aspect_ratio',
						'value'      => array(
							'16:9' => '16-9',
							'4:3'  => '4-3',
							'1:1'  => '1-1',
						),
						'std'        => '16-9',
					),
				),
			)
		);
	}

	private function mapEventMap(): void {
		vc_map(
			array(
				'name'        => __( 'CampsFlow — Mapa', 'campsflow' ),
				'base'        => 'campsflow_event_map',
				'category'    => 'CampsFlow',
				'icon'        => 'dashicons-location-alt',
				'description' => __( 'Mapa lokalizacji aktualnego wydarzenia (Google Maps lub OpenStreetMap)', 'campsflow' ),
				'params'      => array(
					array(
						'type'       => 'dropdown',
						'heading'    => __( 'Dostawca mapy', 'campsflow' ),
						'param_name' => 'provider',
						'value'      => array(
							__( 'OpenStreetMap', 'campsflow' ) => 'openstreetmap',
							__( 'Google Maps', 'campsflow' )   => 'google',
						),
						'std'        => 'openstreetmap',
					),
					array(
						'type'       => 'textfield',
						'heading'    => __( 'Wysokość (px)', 'campsflow' ),
						'param_name' => 'height',
						'value'      => '400',
					),
					array(
						'type'       => 'textfield',
						'heading'    => __( 'Przybliżenie (zoom)', 'campsflow' ),
						'param_name' => 'zoom',
						'value'      => '14',
					),
					array(
						'type'       => 'dropdown',
						'heading'    => __( 'Typ mapy (Google)', 'campsflow' ),
						'param_name' => 'map_type',
						'value'      => array(
							__( 'Mapa drogowa', 'campsflow' ) => 'roadmap',
							__( 'Satelita', 'campsflow' )  => 'satellite',
							__( 'Hybrydowa', 'campsflow' ) => 'hybrid',
							__( 'Teren', 'campsflow' )     => 'terrain',
						),
						'std'        => 'roadmap',
						'dependency' => array(
							'element' => 'provider',
							'value'   => array( 'google' ),
						),
					),
				),
			)
		);
	}

	private function mapEventContact(): void {
		vc_map(
			array(
				'name'        => __( 'CampsFlow — Kontakt', 'campsflow' ),
				'base'        => 'campsflow_event_contact',
				'category'    => 'CampsFlow',
				'icon'        => 'dashicons-businessperson',
				'description' => __( 'Dane kontaktowe (imię, e-mail, telefon) aktualnego wydarzenia', 'campsflow' ),
				'params'      => array(
					array(
						'type'       => 'checkbox',
						'heading'    => __( 'Nagłówek', 'campsflow' ),
						'param_name' => 'show_label',
						'value'      => array( __( 'Pokaż nagłówek', 'campsflow' ) => 'yes' ),
					),
					array(
						'type'       => 'textfield',
						'heading'    => __( 'Tekst nagłówka', 'campsflow' ),
						'param_name' => 'label',
						'value'      => __( 'Kontakt', 'campsflow' ),
					),
				),
			)
		);
	}

	private function mapEventDocuments(): void {
		vc_map(
			array(
				'name'        => __( 'CampsFlow — Dokumenty', 'campsflow' ),
				'base'        => 'campsflow_event_documents',
				'category'    => 'CampsFlow',
				'icon'        => 'dashicons-media-document',
				'description' => __( 'Lista plików do pobrania przypisanych do aktualnego wydarzenia', 'campsflow' ),
				'params'      => array(
					array(
						'type'       => 'checkbox',
						'heading'    => __( 'Nagłówek', 'campsflow' ),
						'param_name' => 'show_label',
						'value'      => array( __( 'Pokaż nagłówek', 'campsflow' ) => 'yes' ),
					),
					array(
						'type'       => 'textfield',
						'heading'    => __( 'Tekst nagłówka', 'campsflow' ),
						'param_name' => 'label',
						'value'      => __( 'Dokumenty', 'campsflow' ),
					),
					array(
						'type'       => 'checkbox',
						'heading'    => __( 'Linki', 'campsflow' ),
						'param_name' => 'open_new_tab',
						'value'      => array( __( 'Otwieraj w nowej karcie', 'campsflow' ) => 'yes' ),
						'std'        => 'yes',
					),
				),
			)
		);
	}

	private function mapEventBreadcrumb(): void {
		vc_map(
			array(
				'name'        => __( 'CampsFlow — Breadcrumb', 'campsflow' ),
				'base'        => 'campsflow_event_breadcrumb',
				'category'    => 'CampsFlow',
				'icon'        => 'dashicons-navigation',
				'description' => __( 'Ścieżka nawigacji: lokalizacja lub sezon/rodzaj obozu', 'campsflow' ),
				'params'      => array(
					array(
						'type'       => 'dropdown',
						'heading'    => __( 'Tryb', 'campsflow' ),
						'param_name' => 'mode',
						'value'      => array(
							__( 'Lokalizacja (kraj › region › miasto)', 'campsflow' ) => 'localization',
							__( 'Sezon › Rodzaj obozu', 'campsflow' )                => 'season_class',
						),
						'std'        => 'localization',
					),
					array(
						'type'       => 'dropdown',
						'heading'    => __( 'Liczba poziomów (lokalizacja)', 'campsflow' ),
						'param_name' => 'depth',
						'value'      => array(
							__( '2 — Kraj › Region', 'campsflow' )          => '2',
							__( '3 — Kraj › Region › Miasto', 'campsflow' ) => '3',
						),
						'std'        => '2',
					),
					array(
						'type'       => 'textfield',
						'heading'    => __( 'Separator', 'campsflow' ),
						'param_name' => 'separator',
						'value'      => '›',
					),
				),
			)
		);
	}

	private function mapEventSessions(): void {
		vc_map(
			array(
				'name'        => __( 'CampsFlow — Turnusy', 'campsflow' ),
				'base'        => 'campsflow_event_sessions',
				'category'    => 'CampsFlow',
				'icon'        => 'dashicons-calendar-alt',
				'description' => __( 'Lista terminów z cenami i przyciskami zapisu', 'campsflow' ),
				'params'      => array(
					array(
						'type'       => 'textfield',
						'heading'    => __( 'Nagłówek sekcji', 'campsflow' ),
						'param_name' => 'title',
						'value'      => __( 'Dostępne terminy', 'campsflow' ),
					),
					array(
						'type'       => 'textfield',
						'heading'    => __( 'Tekst przycisku', 'campsflow' ),
						'param_name' => 'button_label',
						'value'      => __( 'Zapisz się', 'campsflow' ),
					),
					array(
						'type'       => 'checkbox',
						'heading'    => __( 'Opcje', 'campsflow' ),
						'param_name' => 'show_name',
						'value'      => array( __( 'Pokaż nazwę turnusu', 'campsflow' ) => '1' ),
						'std'        => '1',
					),
					array(
						'type'       => 'checkbox',
						'heading'    => '',
						'param_name' => 'show_meeting_points',
						'value'      => array( __( 'Pokaż punkty zbiórki', 'campsflow' ) => '1' ),
					),
					array(
						'type'       => 'checkbox',
						'heading'    => '',
						'param_name' => 'show_custom_fields',
						'value'      => array( __( 'Pokaż pola własne', 'campsflow' ) => '1' ),
					),
				),
			)
		);
	}
}
