<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

// Integration tests load WP — detected by WP_TESTS_DIR env var set by wp-env.
// Unit tests use Brain\Monkey only.
if (getenv('WP_TESTS_DIR')) {
    require_once getenv('WP_TESTS_DIR') . '/includes/bootstrap.php';
}
