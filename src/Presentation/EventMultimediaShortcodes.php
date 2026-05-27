<?php
declare(strict_types=1);

namespace Campsflow\Presentation;

/**
 * [campsflow_event_lead_image alt=""]
 * [campsflow_event_gallery mode="built-in" columns="3" slides_per_view="3" show_arrows="1" show_dots="1" autoplay="0" autoplay_speed="3000" animation_speed="400"]
 * [campsflow_event_lead_video aspect_ratio="16-9"]
 */
final class EventMultimediaShortcodes {

	public function register(): void {
		add_shortcode( 'campsflow_event_lead_image', array( $this, 'renderLeadImage' ) );
		add_shortcode( 'campsflow_event_gallery', array( $this, 'renderGallery' ) );
		add_shortcode( 'campsflow_event_lead_video', array( $this, 'renderLeadVideo' ) );
	}

	// ── Lead image ────────────────────────────────────────────────────────────

	/** @param array<string,string>|string $atts */
	public function renderLeadImage( array|string $atts ): string {
		$atts   = shortcode_atts(
			array( 'alt' => '' ),
			is_array( $atts ) ? $atts : array(),
			'campsflow_event_lead_image'
		);
		$postId = (int) get_the_ID();
		if ( ! $postId ) {
			return '';
		}
		$url = (string) get_post_meta( $postId, 'cf_lead_image_url', true );
		if ( '' === $url ) {
			return '';
		}
		$altText = '' !== $atts['alt'] ? sanitize_text_field( $atts['alt'] ) : (string) get_the_title( $postId );
		return '<img class="cf-lead-image" src="' . esc_url( $url ) . '" alt="' . esc_attr( $altText ) . '" loading="lazy">';
	}

	// ── Gallery ───────────────────────────────────────────────────────────────

	/** @param array<string,string>|string $atts */
	public function renderGallery( array|string $atts ): string {
		$atts   = shortcode_atts(
			array(
				'mode'            => 'built-in',
				'columns'         => '3',
				'slides_per_view' => '3',
				'show_arrows'     => '1',
				'show_dots'       => '1',
				'autoplay'        => '0',
				'autoplay_speed'  => '3000',
				'animation_speed' => '400',
			),
			is_array( $atts ) ? $atts : array(),
			'campsflow_event_gallery'
		);
		$postId = (int) get_the_ID();
		if ( ! $postId ) {
			return '';
		}
		$decoded = json_decode( (string) get_post_meta( $postId, 'cf_multimedia_urls', true ), true );
		$urls    = is_array( $decoded ) ? array_values( array_filter( $decoded, 'is_string' ) ) : array();
		if ( empty( $urls ) ) {
			return '';
		}
		if ( 'slider' === sanitize_key( $atts['mode'] ) ) {
			return $this->renderGallerySlider( $atts, $urls );
		}
		return $this->renderGalleryGrid( (int) $atts['columns'], $urls );
	}

	/**
	 * @param list<string> $urls
	 */
	private function renderGalleryGrid( int $columns, array $urls ): string {
		$columns   = max( 2, min( 6, $columns ) );
		$galleryId = wp_unique_id( 'cf-gal-' );

		ob_start();
		echo '<div class="cf-gallery__grid" style="grid-template-columns:repeat(' . $columns . ',1fr)">';
		foreach ( $urls as $url ) {
			echo '<figure class="cf-gallery__item">';
			echo '<img class="cf-gallery__img" src="' . esc_url( $url ) . '" loading="lazy" data-dialog="' . esc_attr( $galleryId ) . '" alt="" style="cursor:pointer">';
			echo '</figure>';
		}
		echo '</div>';
		echo '<dialog id="' . esc_attr( $galleryId ) . '" class="cf-gallery-dialog">';
		echo '<img class="cf-gallery-dialog__img" src="" alt="" style="max-width:90vw;max-height:90vh;display:block">';
		echo '<button class="cf-gallery-dialog__close" style="position:absolute;top:.5rem;right:.75rem;background:none;border:none;font-size:2rem;cursor:pointer;color:#fff">&times;</button>';
		echo '</dialog>';
		$this->echoDialogScript( $galleryId );
		return (string) ob_get_clean();
	}

	/**
	 * @param array<string,string> $atts
	 * @param list<string> $urls
	 */
	private function renderGallerySlider( array $atts, array $urls ): string {
		$sliderId   = wp_unique_id( 'cf-slider-' );
		$spv        = max( 1, min( 6, (int) $atts['slides_per_view'] ) );
		$showArrows = '1' === $atts['show_arrows'];
		$showDots   = '1' === $atts['show_dots'];
		$autoplay   = '1' === $atts['autoplay'];
		$autoSpeed  = max( 500, (int) $atts['autoplay_speed'] );
		$animSpeed  = max( 100, (int) $atts['animation_speed'] );
		$gap        = 8;
		$height     = 280;
		$flexBasis  = 'calc((100% - ' . ( ( $spv - 1 ) * $gap ) . 'px) / ' . $spv . ')';
		$dotStyle   = 'display:inline-block;width:12px;height:12px;border-radius:50%;margin:0 5px;cursor:pointer;transition:background .2s';
		$btnStyle   = 'position:absolute;top:50%;transform:translateY(-50%);background:rgba(0,0,0,.5);color:#fff;border:none;cursor:pointer;padding:.3rem .6rem;font-size:2.25rem;line-height:1;z-index:2;border-radius:3px';

		ob_start();
		echo '<div id="' . esc_attr( $sliderId ) . '" class="cf-gallery__slider" data-spv="' . $spv . '" data-gap="' . $gap . '">';
		echo '<div style="position:relative">';
		echo '<div class="cf-slider__viewport" style="overflow:hidden">';
		echo '<div class="cf-slider__track" style="display:flex;gap:' . $gap . 'px">';
		foreach ( $urls as $url ) {
			echo '<div class="cf-slider__slide" style="flex:0 0 ' . esc_attr( $flexBasis ) . ';height:' . $height . 'px">';
			echo '<img src="' . esc_url( $url ) . '" alt="" style="width:100%;height:100%;object-fit:cover;display:block">';
			echo '</div>';
		}
		echo '</div></div>';
		if ( $showArrows ) {
			echo '<button class="cf-slider__prev" style="' . $btnStyle . ';left:0" aria-label="' . esc_attr__( 'Poprzednie', 'campsflow' ) . '">&#8249;</button>';
			echo '<button class="cf-slider__next" style="' . $btnStyle . ';right:0" aria-label="' . esc_attr__( 'Następne', 'campsflow' ) . '">&#8250;</button>';
		}
		echo '</div>';
		if ( $showDots ) {
			echo '<div class="cf-slider__dots" style="text-align:center;padding:.75rem 0">';
			foreach ( array_keys( $urls ) as $i ) {
				$bg = 0 === $i ? '#333' : 'rgba(0,0,0,.2)';
				echo '<span class="cf-slider__dot' . ( 0 === $i ? ' is-active' : '' ) . '" style="' . $dotStyle . ';background:' . $bg . '"></span>';
			}
			echo '</div>';
		}
		echo '</div>';
		$this->echoSliderScript( $sliderId, $spv, $showArrows, $showDots, $autoplay, $autoSpeed, $animSpeed );
		return (string) ob_get_clean();
	}

	// ── Lead video ────────────────────────────────────────────────────────────

	/** @param array<string,string>|string $atts */
	public function renderLeadVideo( array|string $atts ): string {
		$atts   = shortcode_atts(
			array( 'aspect_ratio' => '16-9' ),
			is_array( $atts ) ? $atts : array(),
			'campsflow_event_lead_video'
		);
		$postId = (int) get_the_ID();
		if ( ! $postId ) {
			return '';
		}
		$url = (string) get_post_meta( $postId, 'cf_lead_video_url', true );
		if ( '' === $url ) {
			return '';
		}
		$paddingMap  = array(
			'16-9' => '56.25',
			'4-3'  => '75',
			'1-1'  => '100',
		);
		$aspectRatio = sanitize_key( $atts['aspect_ratio'] );
		$padding     = $paddingMap[ $aspectRatio ] ?? '56.25';
		$embedUrl    = $this->buildEmbedUrl( $url );

		ob_start();
		echo '<div class="cf-video-wrap" style="padding-bottom:' . esc_attr( $padding ) . '%">';
		if ( '' !== $embedUrl ) {
			echo '<iframe src="' . esc_url( $embedUrl ) . '" allowfullscreen loading="lazy"></iframe>';
		} else {
			echo '<video src="' . esc_url( $url ) . '" controls></video>';
		}
		echo '</div>';
		return (string) ob_get_clean();
	}

	// ── Helpers ───────────────────────────────────────────────────────────────

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

	private function echoDialogScript( string $dialogId ): void {
		$id = esc_js( $dialogId );
		echo '<script>(function(){';
		echo 'var d=document.getElementById("' . $id . '"),i=d.querySelector("img");';
		echo 'document.querySelectorAll("[data-dialog=\"' . $id . '\"]").forEach(function(el){';
		echo 'el.addEventListener("click",function(){i.src=el.src;d.showModal();});});';
		echo 'd.addEventListener("click",function(e){if(e.target===d)d.close();});';
		echo 'd.querySelector("button").addEventListener("click",function(){d.close();});';
		echo '})();</script>';
	}

	private function echoSliderScript(
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
		echo 'var track=s.querySelector(".cf-slider__track");';
		echo 'var orig=Array.prototype.slice.call(track.children);';
		echo 'var total=orig.length,spv=' . (int) $spv . ',anim=' . (int) $animSpeed . ';';
		echo 'var hasArr=' . $arr . ',hasDots=' . $dot . ';';
		echo 'for(var i=spv-1;i>=0;i--)track.insertBefore(orig[(total-spv+i+total)%total].cloneNode(true),track.firstChild);';
		echo 'for(var j=0;j<spv;j++)track.appendChild(orig[j%total].cloneNode(true));';
		echo 'var cur=0;';
		echo 'function gap(){return parseInt(s.dataset.gap)||0;}';
		echo 'function step(){return track.children[spv].offsetWidth+gap();}';
		echo 'function moveTo(idx,animate){track.style.transition=animate?"transform "+anim+"ms ease":"none";track.style.transform="translateX(-"+(idx*step())+"px)";}';
		echo 'moveTo(spv,false);';
		echo 'function dots(r){if(!hasDots)return;var real=((r%total+total)%total);';
		echo 's.querySelectorAll(".cf-slider__dot").forEach(function(d,i){var a=i===real;d.classList.toggle("is-active",a);d.style.background=a?"#333":"rgba(0,0,0,.2)";});}';
		echo 'function go(n){cur=n;moveTo(cur+spv,true);dots(cur);}';
		echo 'track.addEventListener("transitionend",function(){var ti=cur+spv;';
		echo 'if(ti>=spv+total){cur=0;moveTo(spv,false);dots(0);}';
		echo 'else if(ti<spv){cur=total-1;moveTo(spv+total-1,false);dots(total-1);}});';
		echo 'if(hasArr){s.querySelector(".cf-slider__prev").addEventListener("click",function(){go(cur-1);});';
		echo 's.querySelector(".cf-slider__next").addEventListener("click",function(){go(cur+1);});}';
		echo 'if(hasDots)s.querySelectorAll(".cf-slider__dot").forEach(function(d,i){d.addEventListener("click",function(){go(i);});});';
		echo 'if(' . $aut . '&&total>1)setInterval(function(){go(cur+1);},' . (int) $autoSpeed . ');';
		echo 'var rt;window.addEventListener("resize",function(){clearTimeout(rt);moveTo(cur+spv,false);rt=setTimeout(function(){},100);});';
		echo '})();</script>';
	}
}
