<?php
declare(strict_types=1);

namespace Campsflow\Presentation;

use Elementor\Controls_Manager;
use Elementor\Widget_Base;

final class EventGalleryWidget extends Widget_Base {

	public function get_name(): string {
		return 'campsflow_event_gallery';
	}

	public function get_title(): string {
		return __( 'CampsFlow — Galeria', 'campsflow' );
	}

	public function get_icon(): string {
		return 'eicon-gallery-grid';
	}

	public function get_categories(): array {
		return array( 'campsflow' );
	}

	protected function register_controls(): void {
		$this->registerContentSection();
		$this->registerStyleGridSection();
		$this->registerStyleSliderSection();
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
				'default' => 'built-in',
				'options' => array(
					'built-in' => __( 'Siatka + lightbox', 'campsflow' ),
					'slider'   => __( 'Slider', 'campsflow' ),
					'custom'   => __( 'Własny (data-atrybut)', 'campsflow' ),
				),
			)
		);
		$this->registerGridControls();
		$this->registerSliderControls();
		$this->registerCustomControls();
		$this->add_control(
			'editor_placeholder',
			array(
				'label'       => __( 'Placeholder (tryb edycji)', 'campsflow' ),
				'type'        => Controls_Manager::TEXT,
				'description' => __( 'Widoczny tylko w edytorze gdy brak zdjęć.', 'campsflow' ),
			)
		);
		$this->end_controls_section();
	}

	private function registerGridControls(): void {
		$this->add_control(
			'columns',
			array(
				'label'     => __( 'Kolumny', 'campsflow' ),
				'type'      => Controls_Manager::NUMBER,
				'default'   => 3,
				'min'       => 2,
				'max'       => 6,
				'condition' => array( 'mode' => 'built-in' ),
				'selectors' => array(
					'{{WRAPPER}} .cf-gallery__grid' => 'grid-template-columns: repeat({{VALUE}}, 1fr)',
				),
			)
		);
	}

	private function registerSliderControls(): void {
		$cond = array( 'mode' => 'slider' );
		$this->add_control(
			'slides_per_view',
			array(
				'label'     => __( 'Widocznych zdjęć obok siebie', 'campsflow' ),
				'type'      => Controls_Manager::NUMBER,
				'default'   => 3,
				'min'       => 1,
				'max'       => 6,
				'condition' => $cond,
			)
		);
		$this->add_control(
			'show_arrows',
			array(
				'label'     => __( 'Pokaż strzałki', 'campsflow' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'label_on'  => __( 'Tak', 'campsflow' ),
				'label_off' => __( 'Nie', 'campsflow' ),
				'condition' => $cond,
			)
		);
		$this->add_control(
			'show_dots',
			array(
				'label'     => __( 'Pokaż kropki', 'campsflow' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => 'yes',
				'label_on'  => __( 'Tak', 'campsflow' ),
				'label_off' => __( 'Nie', 'campsflow' ),
				'condition' => $cond,
			)
		);
		$this->add_control(
			'autoplay',
			array(
				'label'     => __( 'Autoplay', 'campsflow' ),
				'type'      => Controls_Manager::SWITCHER,
				'default'   => '',
				'label_on'  => __( 'Tak', 'campsflow' ),
				'label_off' => __( 'Nie', 'campsflow' ),
				'condition' => $cond,
			)
		);
		$this->add_control(
			'autoplay_speed',
			array(
				'label'     => __( 'Czas między slajdami (ms)', 'campsflow' ),
				'type'      => Controls_Manager::NUMBER,
				'default'   => 3000,
				'min'       => 500,
				'max'       => 10000,
				'step'      => 500,
				'condition' => array(
					'mode'     => 'slider',
					'autoplay' => 'yes',
				),
			)
		);
		$this->add_control(
			'animation_speed',
			array(
				'label'     => __( 'Czas animacji (ms)', 'campsflow' ),
				'type'      => Controls_Manager::NUMBER,
				'default'   => 400,
				'min'       => 100,
				'max'       => 1500,
				'step'      => 50,
				'condition' => $cond,
			)
		);
	}

	private function registerCustomControls(): void {
		$this->add_control(
			'custom_class',
			array(
				'label'     => __( 'Klasa CSS kontenera', 'campsflow' ),
				'type'      => Controls_Manager::TEXT,
				'condition' => array( 'mode' => 'custom' ),
			)
		);
		$this->add_control(
			'custom_attr',
			array(
				'label'     => __( 'Nazwa data-atrybutu', 'campsflow' ),
				'type'      => Controls_Manager::TEXT,
				'default'   => 'cf-gallery',
				'condition' => array( 'mode' => 'custom' ),
			)
		);
	}

	private function registerStyleGridSection(): void {
		$this->start_controls_section(
			'section_style_grid',
			array(
				'label'     => __( 'Siatka', 'campsflow' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'mode' => 'built-in' ),
			)
		);
		$this->add_control(
			'gallery_gap',
			array(
				'label'      => __( 'Odstęp między zdjęciami', 'campsflow' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 4,
						'max' => 48,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 12,
				),
				'selectors'  => array(
					'{{WRAPPER}} .cf-gallery__grid' => 'gap: {{SIZE}}{{UNIT}}',
				),
			)
		);
		$this->add_control(
			'img_radius',
			array(
				'label'      => __( 'Zaokrąglenie rogów', 'campsflow' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 32,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 6,
				),
				'selectors'  => array(
					'{{WRAPPER}} .cf-gallery__item' => 'border-radius: {{SIZE}}{{UNIT}}',
				),
			)
		);
		$this->end_controls_section();
	}

	private function registerStyleSliderSection(): void {
		$this->start_controls_section(
			'section_style_slider',
			array(
				'label'     => __( 'Slider', 'campsflow' ),
				'tab'       => Controls_Manager::TAB_STYLE,
				'condition' => array( 'mode' => 'slider' ),
			)
		);
		$this->add_control(
			'slide_height',
			array(
				'label'      => __( 'Wysokość slajdu', 'campsflow' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 80,
						'max' => 800,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 280,
				),
			)
		);
		$this->add_control(
			'slide_gap',
			array(
				'label'      => __( 'Odstęp między zdjęciami', 'campsflow' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 40,
					),
				),
				'default'    => array(
					'unit' => 'px',
					'size' => 8,
				),
			)
		);
		$this->add_control(
			'slider_radius',
			array(
				'label'      => __( 'Zaokrąglenie rogów zdjęć', 'campsflow' ),
				'type'       => Controls_Manager::SLIDER,
				'size_units' => array( 'px' ),
				'range'      => array(
					'px' => array(
						'min' => 0,
						'max' => 32,
					),
				),
				'selectors'  => array(
					'{{WRAPPER}} .cf-slider__slide' => 'border-radius: {{SIZE}}{{UNIT}}; overflow: hidden',
				),
			)
		);
		$this->add_control(
			'dot_color',
			array(
				'label'     => __( 'Kolor aktywnej kropki', 'campsflow' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .cf-slider__dot.is-active' => 'background: {{VALUE}}',
				),
			)
		);
		$this->add_control(
			'arrow_bg',
			array(
				'label'     => __( 'Tło strzałek', 'campsflow' ),
				'type'      => Controls_Manager::COLOR,
				'selectors' => array(
					'{{WRAPPER}} .cf-slider__prev, {{WRAPPER}} .cf-slider__next' => 'background: {{VALUE}}',
				),
			)
		);
		$this->end_controls_section();
	}

	protected function render(): void {
		$s           = $this->get_settings_for_display();
		$postId      = (int) get_the_ID();
		$mode        = (string) ( $s['mode'] ?? 'built-in' );
		$placeholder = sanitize_text_field( (string) ( $s['editor_placeholder'] ?? '' ) );

		$urls = array();
		if ( $postId ) {
			$decoded = json_decode( (string) get_post_meta( $postId, 'cf_multimedia_urls', true ), true );
			$urls    = is_array( $decoded ) ? array_values( array_filter( $decoded, 'is_string' ) ) : array();
		}

		if ( empty( $urls ) ) {
			if ( '' !== $placeholder && \Elementor\Plugin::$instance->editor->is_edit_mode() ) {
				echo '<p class="cf-gallery--placeholder">' . esc_html( $placeholder ) . '</p>';
			}
			return;
		}

		if ( 'custom' === $mode ) {
			$this->renderCustomMode( $s, $urls );
			return;
		}
		if ( 'slider' === $mode ) {
			$this->renderSliderMode( $s, $urls );
			return;
		}
		$this->renderGridMode( $urls );
	}

	/** @param array<string,mixed> $s @param list<string> $urls */
	private function renderCustomMode( array $s, array $urls ): void {
		$class    = sanitize_html_class( (string) ( $s['custom_class'] ?? '' ) );
		$attr     = sanitize_key( (string) ( $s['custom_attr'] ?? 'cf-gallery' ) );
		$json     = (string) wp_json_encode( $urls );
		$classStr = '' !== $class ? ' class="' . esc_attr( $class ) . '"' : '';
		echo '<div' . $classStr . ' data-' . esc_attr( $attr ) . "='" . esc_attr( $json ) . "'></div>";
	}

	/** @param list<string> $urls */
	private function renderGridMode( array $urls ): void {
		$dialogId = 'cf-gal-' . esc_attr( $this->get_id() );
		echo '<div class="cf-gallery__grid">';
		foreach ( $urls as $url ) {
			echo '<figure class="cf-gallery__item">';
			echo '<img class="cf-gallery__img" src="' . esc_url( $url ) . '" loading="lazy" data-dialog="' . esc_attr( $dialogId ) . '" alt="" style="cursor:pointer">';
			echo '</figure>';
		}
		echo '</div>';
		echo '<dialog id="' . esc_attr( $dialogId ) . '" class="cf-gallery-dialog">';
		echo '<img class="cf-gallery-dialog__img" src="" alt="" style="max-width:90vw;max-height:90vh;display:block">';
		echo '<button class="cf-gallery-dialog__close" style="position:absolute;top:.5rem;right:.75rem;background:none;border:none;font-size:2rem;cursor:pointer;color:#fff">&times;</button>';
		echo '</dialog>';
		$this->echoInlineDialogScript( $dialogId );
	}

	/** @param array<string,mixed> $s @param list<string> $urls */
	private function renderSliderMode( array $s, array $urls ): void {
		$sliderId   = 'cf-slider-' . esc_attr( $this->get_id() );
		$spv        = max( 1, min( 6, (int) ( $s['slides_per_view'] ?? 3 ) ) );
		$showArrows = ( $s['show_arrows'] ?? 'yes' ) === 'yes';
		$showDots   = ( $s['show_dots'] ?? 'yes' ) === 'yes';
		$autoplay   = ( $s['autoplay'] ?? '' ) === 'yes';
		$autoSpeed  = max( 500, (int) ( $s['autoplay_speed'] ?? 3000 ) );
		$animSpeed  = max( 100, (int) ( $s['animation_speed'] ?? 400 ) );
		$gap        = isset( $s['slide_gap']['size'] ) ? max( 0, (int) $s['slide_gap']['size'] ) : 8;
		$height     = isset( $s['slide_height']['size'] ) ? max( 80, (int) $s['slide_height']['size'] ) : 280;
		$flexBasis  = 'calc((100% - ' . ( ( $spv - 1 ) * $gap ) . 'px) / ' . $spv . ')';
		$dotStyle   = 'display:inline-block;width:12px;height:12px;border-radius:50%;margin:0 5px;cursor:pointer;transition:background .2s';

		echo '<div id="' . esc_attr( $sliderId ) . '" class="cf-gallery__slider" data-spv="' . $spv . '" data-gap="' . $gap . '">';

		/* wrapper: position:relative so arrows anchor to it, height = viewport height */
		echo '<div style="position:relative">';

		/* viewport: overflow:hidden clips the sliding track — arrows are OUTSIDE this */
		echo '<div class="cf-slider__viewport" style="overflow:hidden">';
		echo '<div class="cf-slider__track" style="display:flex;gap:' . $gap . 'px">';
		foreach ( $urls as $url ) {
			echo '<div class="cf-slider__slide" style="flex:0 0 ' . esc_attr( $flexBasis ) . ';height:' . $height . 'px">';
			echo '<img src="' . esc_url( $url ) . '" alt="" style="width:100%;height:100%;object-fit:cover;display:block">';
			echo '</div>';
		}
		echo '</div>';
		echo '</div>'; /* end viewport */

		/* arrows sit on top of the viewport, centered on its height */
		if ( $showArrows ) {
			$btn = 'position:absolute;top:50%;transform:translateY(-50%);background:rgba(0,0,0,.5);color:#fff;border:none;cursor:pointer;padding:.3rem .6rem;font-size:2.25rem;line-height:1;z-index:2;border-radius:3px';
			echo '<button class="cf-slider__prev" style="' . $btn . ';left:0" aria-label="' . esc_attr__( 'Poprzednie', 'campsflow' ) . '">&#8249;</button>';
			echo '<button class="cf-slider__next" style="' . $btn . ';right:0" aria-label="' . esc_attr__( 'Następne', 'campsflow' ) . '">&#8250;</button>';
		}

		echo '</div>'; /* end wrapper */

		/* dots are outside overflow:hidden — always visible */
		if ( $showDots ) {
			echo '<div class="cf-slider__dots" style="text-align:center;padding:.75rem 0">';
			foreach ( array_keys( $urls ) as $i ) {
				$bg = 0 === $i ? '#333' : 'rgba(0,0,0,.2)';
				echo '<span class="cf-slider__dot' . ( 0 === $i ? ' is-active' : '' ) . '" style="' . $dotStyle . ';background:' . $bg . '"></span>';
			}
			echo '</div>';
		}

		echo '</div>'; /* end slider */
		$this->echoInlineSliderScript( $sliderId, $spv, $showArrows, $showDots, $autoplay, $autoSpeed, $animSpeed );
	}

	private function echoInlineSliderScript(
		string $sliderId,
		int $spv,
		bool $showArrows,
		bool $showDots,
		bool $autoplay,
		int $autoSpeed,
		int $animSpeed
	): void {
		$id  = esc_js( $sliderId );
		$arr = $showArrows ? 'true' : 'false';
		$dot = $showDots ? 'true' : 'false';
		$aut = $autoplay ? 'true' : 'false';

		echo '<script>(function(){';
		echo 'var s=document.getElementById("' . $id . '");';
		echo 'var vp=s.querySelector(".cf-slider__viewport");';
		echo 'var track=s.querySelector(".cf-slider__track");';
		echo 'var orig=Array.prototype.slice.call(track.children);';
		echo 'var total=orig.length,spv=' . (int) $spv . ',anim=' . (int) $animSpeed . ';';
		echo 'var hasArr=' . $arr . ',hasDots=' . $dot . ';';
		/* clone last spv before, first spv after for infinite loop */
		echo 'for(var i=spv-1;i>=0;i--)track.insertBefore(orig[(total-spv+i+total)%total].cloneNode(true),track.firstChild);';
		echo 'for(var j=0;j<spv;j++)track.appendChild(orig[j%total].cloneNode(true));';
		echo 'var cur=0;';
		echo 'function gap(){return parseInt(s.dataset.gap)||0;}';
		echo 'function step(){return track.children[spv].offsetWidth+gap();}';
		echo 'function moveTo(idx,animate){';
		echo 'track.style.transition=animate?"transform "+anim+"ms ease":"none";';
		echo 'track.style.transform="translateX(-"+(idx*step())+"px)";}';
		echo 'moveTo(spv,false);';
		echo 'function dots(r){if(!hasDots)return;';
		echo 'var real=((r%total+total)%total);';
		echo 's.querySelectorAll(".cf-slider__dot").forEach(function(d,i){';
		echo 'var a=i===real;d.classList.toggle("is-active",a);';
		echo 'd.style.background=a?"#333":"rgba(0,0,0,.2)";});}';
		echo 'function go(n){cur=n;moveTo(cur+spv,true);dots(cur);}';
		echo 'track.addEventListener("transitionend",function(){';
		echo 'var ti=cur+spv;';
		echo 'if(ti>=spv+total){cur=0;moveTo(spv,false);dots(0);}';
		echo 'else if(ti<spv){cur=total-1;moveTo(spv+total-1,false);dots(total-1);}';
		echo '});';
		echo 'if(hasArr){';
		echo 's.querySelector(".cf-slider__prev").addEventListener("click",function(){go(cur-1);});';
		echo 's.querySelector(".cf-slider__next").addEventListener("click",function(){go(cur+1);});';
		echo '}';
		echo 'if(hasDots)s.querySelectorAll(".cf-slider__dot").forEach(function(d,i){d.addEventListener("click",function(){go(i);});});';
		echo 'if(' . $aut . '&&total>1)setInterval(function(){go(cur+1);},' . (int) $autoSpeed . ');';
		echo 'var rt;window.addEventListener("resize",function(){clearTimeout(rt);moveTo(cur+spv,false);rt=setTimeout(function(){},100);});';
		echo '})();</script>';
	}

	private function echoInlineDialogScript( string $dialogId ): void {
		$id = esc_js( $dialogId );
		echo '<script>(function(){';
		echo 'var d=document.getElementById("' . $id . '"),i=d.querySelector("img");';
		echo 'document.querySelectorAll("[data-dialog=\"' . $id . '\"]").forEach(function(el){';
		echo 'el.addEventListener("click",function(){i.src=el.src;d.showModal();});});';
		echo 'd.addEventListener("click",function(e){if(e.target===d)d.close();});';
		echo 'd.querySelector("button").addEventListener("click",function(){d.close();});';
		echo '})();</script>';
	}
}
