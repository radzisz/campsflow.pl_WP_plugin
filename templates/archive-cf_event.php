<?php
/**
 * Template: Lista imprez (archiwum CPT cf_event)
 * URL: /obozy/
 *
 * Theme override: copy this file to your theme root as archive-cf_event.php
 */
defined('ABSPATH') || exit;

get_header();
?>

<main id="cf-main" class="cf-page cf-page--archive">

    <header class="cf-page__header">
        <h1 class="cf-page__title">
            <?php echo esc_html(post_type_archive_title('', false)); ?>
        </h1>
    </header>

    <div class="cf-page__content">
        <?php
        $view = isset($_GET['view']) && $_GET['view'] === 'sessions' ? 'sessions' : 'events';
        echo do_shortcode('[campsflow_listing view="' . esc_attr($view) . '"]');
        ?>
    </div>

</main>

<?php get_footer(); ?>
