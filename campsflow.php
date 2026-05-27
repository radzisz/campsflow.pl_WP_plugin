<?php
/**
 * Plugin Name: Campsflow
 * Plugin URI:  https://campsflow.pl
 * Description: WordPress integration for Campsflow.pl reservation system. Synchronizes camps and sessions to local CPT, provides listing shortcodes, and embeds the Campsflow registration form via iframe.
 * Version:     0.1.22
 * Author:      Campsflow
 * Author URI:  https://campsflow.pl
 * License:     GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: campsflow
 * Domain Path: /languages
 * Requires PHP: 8.1
 * Requires at least: 6.4
 */

declare(strict_types=1);

namespace Campsflow;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CAMPSFLOW_VERSION', '0.1.22' );
define( 'CAMPSFLOW_PLUGIN_FILE', __FILE__ );
define( 'CAMPSFLOW_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CAMPSFLOW_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once CAMPSFLOW_PLUGIN_DIR . 'vendor/autoload.php';

// Auto-updates via GitHub Releases (release assets only — never raw source)
$updateChecker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
	'https://github.com/campsflow-pl/campsflow.pl_plugin_wp/',
	__FILE__,
	'campsflow'
);
$updateChecker->getVcsApi()->enableReleaseAssets();

// Reject any update whose download URL is a raw source archive (no vendor/).
// This happens when GitHub Actions hasn't finished building the release ZIP yet.
add_filter(
	'puc_request_info_result-campsflow',
	static function ( $info ) {
		if ( $info && isset( $info->downloadUrl ) && str_contains( (string) $info->downloadUrl, '/zipball/' ) ) {
			return null;
		}
		return $info;
	}
);

register_activation_hook(
	__FILE__,
	static function (): void {
		( new PostType\EventPostType() )->registerPostType();
		( new PostType\SessionPostType() )->registerPostType();
		flush_rewrite_rules();
		Sync\SyncScheduler::activate();
		Presentation\RegistrationFormShortcode::createPageIfMissing();
	}
);

register_deactivation_hook(
	__FILE__,
	static function (): void {
		flush_rewrite_rules();
		Sync\SyncScheduler::deactivate();
	}
);

add_action(
	'plugins_loaded',
	static function (): void {
		( new Sync\SyncScheduler() )->register();
		( new PostType\PostStatus() )->register();
		( new PostType\EventPostType() )->register();
		( new PostType\SessionPostType() )->register();
		( new Taxonomy\EventTagTaxonomy() )->register();
		( new Taxonomy\EventCategoryTaxonomy() )->register();
		( new Taxonomy\AgeGroupTaxonomy() )->register();
		( new Taxonomy\DestinationTaxonomy() )->register();
		( new Taxonomy\TransportTypeTaxonomy() )->register();
		( new Taxonomy\SeasonTaxonomy() )->register();
		( new Presentation\ListingShortcode() )->register();
		( new Presentation\EventShortcodes() )->register();
		( new Presentation\SearchFilterShortcode() )->register();
		( new Presentation\SearchFilterFieldShortcode() )->register();
		( new Presentation\SearchSortShortcode() )->register();
		( new Presentation\SearchResultsShortcode() )->register();
		( new Presentation\EventMapShortcode() )->register();
		( new Presentation\EventMultimediaShortcodes() )->register();
		( new Presentation\EventFieldShortcode() )->register();
		( new Presentation\RegistrationFormShortcode() )->register();
		( new Presentation\TemplateLoader() )->register();
		( new Presentation\ElementorIntegration() )->register();
		( new Presentation\WpBakeryIntegration() )->register();
		( new Presentation\WpBakeryDynamicContent() )->register();
		( new Api\EventsEndpoint() )->register();
		( new Admin\SyncNotice() )->register();
		( new Admin\AdminColumns() )->register();
		( new Admin\SettingsPage() )->register();
		( new Admin\ElementorLinks() )->register();
		( new Admin\FixtureImporter() )->register();
	}
);

add_action(
	'wp_enqueue_scripts',
	static function (): void {
		wp_enqueue_style(
			'campsflow',
			CAMPSFLOW_PLUGIN_URL . 'assets/css/campsflow.css',
			array(),
			CAMPSFLOW_VERSION,
		);
		wp_enqueue_script(
			'campsflow-search-filters',
			CAMPSFLOW_PLUGIN_URL . 'assets/js/search-filters.js',
			array(),
			CAMPSFLOW_VERSION,
			true,
		);
		wp_enqueue_script(
			'campsflow-event-map',
			CAMPSFLOW_PLUGIN_URL . 'assets/js/event-map.js',
			array(),
			CAMPSFLOW_VERSION,
			true,
		);
		wp_localize_script(
			'campsflow-event-map',
			'cfMapConfig',
			array(
				'googleApiKey' => (string) get_option( 'campsflow_google_maps_api_key', '' ),
			)
		);
	}
);
