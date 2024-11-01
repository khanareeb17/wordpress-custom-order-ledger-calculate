<?php
/*
Plugin Name: Car Ledger
Description: A plugin to manage car sales ledger & maintain its history for admin & customers.
Version: 1.0.0
Author: Areeb Khan
*/

// Include required files
require_once(plugin_dir_path(__FILE__) . 'includes/class-cl-database.php');
require_once(plugin_dir_path(__FILE__) . 'includes/class-cl-admin.php');
require_once(plugin_dir_path(__FILE__) . 'includes/class-cl-customer.php');

// Register activation and deactivation hooks
register_activation_hook(__FILE__, ['CL_Database', 'create_table']);
// register_deactivation_hook(__FILE__, ['CL_Database', 'drop_table']);

// Register activation and deactivation hooks Enable these when the ledger history menu is not shown to customer account dashboard
// register_activation_hook(__FILE__, 'cl_flush_rewrite_rules');
// register_deactivation_hook(__FILE__, 'cl_rewrite_flush');

// Initialize plugin
add_action('admin_menu', ['CL_Admin', 'add_menu']);
add_action('admin_enqueue_scripts', ['CL_Admin', 'enqueue_scripts']);
add_action('wp_ajax_update_ledger', ['CL_Admin', 'update_ledger']);
add_action('wp_ajax_fetch_ledger_history', ['CL_Admin', 'fetch_ledger_history']); //Fetch Ledger history
add_action('admin_enqueue_scripts', ['CL_Admin', 'enqueue_styles']);
add_shortcode('ledger_history', array('CL_Customer', 'display_ledger_history_customer_account'));
add_action('wp_enqueue_scripts', ['CL_Customer', 'cl_enqueue_customer_styles']);
// Register endpoint
function add_ledger_history_endpoint() {
    add_rewrite_endpoint('ledger-history', EP_ROOT | EP_PAGES);
}
add_action('init', 'add_ledger_history_endpoint');

// Add endpoint to WooCommerce My Account menu
function add_ledger_history_link_my_account($items) {
    $items['ledger-history'] = 'Ledger History';
    return $items;
}
add_filter('woocommerce_account_menu_items', 'add_ledger_history_link_my_account');

// Display content for the endpoint
function ledger_history_content() {
    echo do_shortcode('[ledger_history]');
}
add_action('woocommerce_account_ledger-history_endpoint', 'ledger_history_content');

// Flush rewrite rules on plugin activation and deactivation
function cl_flush_rewrite_rules() {
    add_ledger_history_endpoint();
    flush_rewrite_rules();
}

function cl_rewrite_flush() {
    flush_rewrite_rules();
}