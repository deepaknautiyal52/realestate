<?php
/**
 * Plugin Name: Real Estate Core
 * Description: Property listings, search, enquiry form and lead inbox. Works with Astra and Elementor.
 * Version: 1.0.0
 * Requires PHP: 7.4
 * Text Domain: realestate-core
 */

defined('ABSPATH') || exit;

define('REC_VERSION', '1.0.0');
define('REC_FILE', __FILE__);
define('REC_DIR', plugin_dir_path(__FILE__));
define('REC_URL', plugin_dir_url(__FILE__));

require_once REC_DIR . 'includes/helpers.php';
require_once REC_DIR . 'includes/post-types.php';
require_once REC_DIR . 'includes/meta-boxes.php';
require_once REC_DIR . 'includes/search.php';
require_once REC_DIR . 'includes/templates.php';
require_once REC_DIR . 'includes/shortcodes.php';
require_once REC_DIR . 'includes/enquiries.php';
require_once REC_DIR . 'includes/settings.php';

register_activation_hook(__FILE__, function () {
    rec_register_post_types();
    flush_rewrite_rules();
});

register_deactivation_hook(__FILE__, 'flush_rewrite_rules');

add_action('wp_enqueue_scripts', function () {
    wp_enqueue_style('rec-frontend', REC_URL . 'assets/frontend.css', [], REC_VERSION);
    wp_enqueue_script('rec-frontend', REC_URL . 'assets/frontend.js', [], REC_VERSION, true);
});
