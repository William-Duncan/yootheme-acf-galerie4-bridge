<?php
/**
 * Plugin Name: YOOtheme ACF Galerie 4 Bridge
 * Plugin URI: https://github.com/William-Duncan/yootheme-acf-galerie4-bridge
 * Description: Exposes ACF Galerie 4 fields as YOOtheme Pro Multiple Items Sources for use in Gallery, Grid, and Slideshow elements.
 * Version: 1.0.0
 * Author: William Duncan
 * Author URI: https://www.william-duncan.com
 * License: GPL-2.0+
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain: yootheme-acf-galerie4-bridge
 * Requires at least: 6.0
 * Requires PHP: 7.4
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('YOOTHEME_ACF_GALERIE4_BRIDGE_VERSION', '1.0.0');
define('YOOTHEME_ACF_GALERIE4_BRIDGE_PATH', plugin_dir_path(__FILE__));

/**
 * Check plugin dependencies
 */
function yootheme_acf_galerie4_bridge_check_dependencies() {
    $errors = [];

    // Check for YOOtheme Pro
    if (!class_exists('YOOtheme\Application')) {
        $errors[] = __('YOOtheme Pro theme must be installed and active.', 'yootheme-acf-galerie4-bridge');
    }

    // Check for ACF - note: acf_get_field might not be available yet, check for the class instead
    if (!class_exists('ACF') && !function_exists('acf_get_field')) {
        $errors[] = __('Advanced Custom Fields must be installed and active.', 'yootheme-acf-galerie4-bridge');
    }

    // Check for ACF Galerie 4 - check if the plugin file exists
    // The class loads later on init hook, so we check the file
    $acf_galerie4_file = WP_PLUGIN_DIR . '/acf-galerie-4/acf-galerie-4.php';
    if (!file_exists($acf_galerie4_file)) {
        $errors[] = __('ACF Galerie 4 plugin must be installed and active.', 'yootheme-acf-galerie4-bridge');
    }

    return $errors;
}

/**
 * Display admin notice for missing dependencies
 */
function yootheme_acf_galerie4_bridge_admin_notice() {
    $errors = yootheme_acf_galerie4_bridge_check_dependencies();

    if (empty($errors)) {
        return;
    }

    echo '<div class="notice notice-error"><p><strong>' .
         esc_html__('YOOtheme ACF Galerie 4 Bridge', 'yootheme-acf-galerie4-bridge') .
         ':</strong></p><ul>';

    foreach ($errors as $error) {
        echo '<li>' . esc_html($error) . '</li>';
    }

    echo '</ul></div>';
}
add_action('admin_notices', 'yootheme_acf_galerie4_bridge_admin_notice');

/**
 * Initialize the plugin
 *
 * This hooks into YOOtheme's event system via after_setup_theme
 * to ensure YOOtheme is fully loaded before we try to extend it.
 */
function yootheme_acf_galerie4_bridge_init() {
    // Check dependencies
    $errors = yootheme_acf_galerie4_bridge_check_dependencies();
    if (!empty($errors)) {
        return;
    }

    // Load the YOOtheme module
    $app = \YOOtheme\app();
    $app->load(YOOTHEME_ACF_GALERIE4_BRIDGE_PATH . 'bootstrap.php');
}

// Hook into after_setup_theme with priority 20 to ensure YOOtheme is loaded (it uses priority 10)
add_action('after_setup_theme', 'yootheme_acf_galerie4_bridge_init', 20);
