<?php
declare(strict_types=1);

namespace Campsflow\Presentation;

use Campsflow\PostType\EventPostType;
use Campsflow\PostType\SessionPostType;

/**
 * Loads plugin templates for cf_event CPT if the active theme
 * does not provide its own (standard WP template hierarchy).
 *
 * Theme override: place archive-cf_event.php or single-cf_event.php
 * in the theme root to fully replace the plugin default.
 */
final class TemplateLoader {

	public function register(): void {
		add_filter( 'template_include', array( $this, 'load' ) );
	}

	public function load( string $template ): string {
		if ( is_post_type_archive( EventPostType::SLUG ) ) {
			return $this->resolve( 'archive-cf_event.php', $template );
		}

		if ( is_singular( EventPostType::SLUG ) ) {
			return $this->resolve( 'single-cf_event.php', $template );
		}

		return $template;
	}

	private function resolve( string $filename, string $fallback ): string {
		// Theme gets priority
		$themeFile = locate_template( $filename );
		if ( $themeFile ) {
			return $themeFile;
		}

		$pluginFile = CAMPSFLOW_PLUGIN_DIR . 'templates/' . $filename;
		return file_exists( $pluginFile ) ? $pluginFile : $fallback;
	}
}
