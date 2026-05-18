<?php
/**
 * Plugin Name: Campsflow
 * Plugin URI:  https://campsflow.pl
 * Description: WordPress integration for Campsflow.pl reservation system. Synchronizes camps and sessions to local CPT, provides listing shortcodes, and embeds the Campsflow registration form via iframe.
 * Version:     0.1.0
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

if (! defined('ABSPATH')) {
    exit;
}

define('CAMPSFLOW_VERSION', '0.1.0');
define('CAMPSFLOW_PLUGIN_FILE', __FILE__);
define('CAMPSFLOW_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CAMPSFLOW_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once CAMPSFLOW_PLUGIN_DIR . 'vendor/autoload.php';

// Auto-updates via GitHub Releases
// Replace with your actual GitHub repo URL before first release.
$updateChecker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
    'https://github.com/campsflow/campsflow-wp/',
    __FILE__,
    'campsflow'
);
$updateChecker->getVcsApi()->enableReleaseAssets();

register_activation_hook(__FILE__, static function (): void {
    (new PostType\EventPostType())->registerPostType();
    (new PostType\SessionPostType())->registerPostType();
    flush_rewrite_rules();
    Sync\SyncScheduler::activate();
});

register_deactivation_hook(__FILE__, static function (): void {
    flush_rewrite_rules();
    Sync\SyncScheduler::deactivate();
});

add_action('plugins_loaded', static function (): void {
    (new Sync\SyncScheduler())->register();
    (new PostType\PostStatus())->register();
    (new PostType\EventPostType())->register();
    (new PostType\SessionPostType())->register();
    (new Taxonomy\CampTagTaxonomy())->register();
    (new Taxonomy\AgeGroupTaxonomy())->register();
    (new Presentation\ListingShortcode())->register();
    (new Presentation\EventShortcodes())->register();
    (new Presentation\TemplateLoader())->register();
    (new Presentation\ElementorIntegration())->register();
    (new Presentation\WpBakeryIntegration())->register();
    (new Presentation\WpBakeryDynamicContent())->register();
    (new Admin\SyncNotice())->register();
    (new Admin\SettingsPage())->register();
    (new Admin\ElementorLinks())->register();
    (new Admin\FixtureImporter())->register();
});

add_action('wp_enqueue_scripts', static function (): void {
    wp_enqueue_style(
        'campsflow',
        CAMPSFLOW_PLUGIN_URL . 'assets/css/campsflow.css',
        [],
        CAMPSFLOW_VERSION,
    );
});
