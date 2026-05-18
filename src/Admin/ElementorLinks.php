<?php
declare(strict_types=1);

namespace Campsflow\Admin;

use Campsflow\PostType\EventPostType;

/**
 * Adds "Edytuj wygląd" submenu links that open Elementor editor
 * for the listing page and (if Elementor Pro) the Theme Builder template.
 */
final class ElementorLinks
{
    public function register(): void
    {
        if (! did_action('elementor/loaded')) {
            add_action('elementor/loaded', [$this, 'addMenuItems']);
        } else {
            $this->addMenuItems();
        }
    }

    public function addMenuItems(): void
    {
        add_action('admin_menu', [$this, 'addSubmenus']);
        add_action('admin_head', [$this, 'fixMenuUrls']);
    }

    public function addSubmenus(): void
    {
        $listingPageId = $this->getListingPageId();

        // Listing page — link to Elementor editor (always shown if page exists)
        if ($listingPageId) {
            $editUrl = admin_url('post.php?post=' . $listingPageId . '&action=elementor');
            add_submenu_page(
                'edit.php?post_type=' . EventPostType::SLUG,
                __('Edytuj listę obozów', 'campsflow'),
                __('✏ Edytuj listę', 'campsflow'),
                'manage_options',
                'cf-edit-listing',
                static function () use ($editUrl): void {
                    wp_redirect($editUrl);
                    exit;
                },
            );
        }

        // WPBakery — standard edit screen (WPBakery overlays it)
        if ($listingPageId && $this->hasWpBakery()) {
            $wpbUrl = admin_url('post.php?post=' . $listingPageId . '&action=edit');
            add_submenu_page(
                'edit.php?post_type=' . EventPostType::SLUG,
                __('Edytuj listę — WPBakery', 'campsflow'),
                __('✏ Edytuj listę (WPB)', 'campsflow'),
                'manage_options',
                'cf-edit-listing-wpb',
                static function () use ($wpbUrl): void {
                    wp_redirect($wpbUrl);
                    exit;
                },
            );
        }

        // Elementor Pro Theme Builder — single cf_event template
        if ($this->hasElementorPro()) {
            $themeBuilderUrl = admin_url(
                'edit.php?post_type=elementor_library'
                . '&tabs_group=theme'
                . '&elementor_library_type=single-post'
            );
            add_submenu_page(
                'edit.php?post_type=' . EventPostType::SLUG,
                __('Szablon strony imprezy (Elementor Pro)', 'campsflow'),
                __('✏ Szablon imprezy', 'campsflow'),
                'manage_options',
                'cf-edit-single',
                static function () use ($themeBuilderUrl): void {
                    wp_redirect($themeBuilderUrl);
                    exit;
                },
            );
        }
    }

    /**
     * Replaces the submenu href with direct URLs so the browser navigates
     * without going through wp-admin/admin.php?page=cf-edit-*.
     */
    public function fixMenuUrls(): void
    {
        $listingPageId = $this->getListingPageId();
        $links         = [];

        if ($listingPageId) {
            $links['cf-edit-listing'] = admin_url(
                'post.php?post=' . $listingPageId . '&action=elementor'
            );
        }

        if ($this->hasElementorPro()) {
            $links['cf-edit-single'] = admin_url(
                'edit.php?post_type=elementor_library&tabs_group=theme&elementor_library_type=single-post'
            );
        }

        if (empty($links)) {
            return;
        }

        echo '<script>
        (function() {
            var links = ' . wp_json_encode($links) . ';
            document.querySelectorAll("#adminmenu a").forEach(function(a) {
                Object.keys(links).forEach(function(slug) {
                    if (a.href && a.href.indexOf("page=" + slug) !== -1) {
                        a.href = links[slug];
                        a.target = "_blank";
                        a.rel = "noopener";
                    }
                });
            });
        })();
        </script>';
    }

    private function getListingPageId(): int
    {
        // Try to find a page using the CPT archive slug
        $archiveSlug = get_post_type_archive_link('cf_event');
        if (! $archiveSlug) {
            return 0;
        }

        // Fall back to looking for a page named 'obozy'
        $page = get_page_by_path('obozy', OBJECT, 'page');
        return $page ? (int) $page->ID : 0;
    }

    private function hasElementorPro(): bool
    {
        return defined('ELEMENTOR_PRO_VERSION');
    }

    private function hasWpBakery(): bool
    {
        return defined('WPB_VC_VERSION') || class_exists('WPBMap');
    }
}
