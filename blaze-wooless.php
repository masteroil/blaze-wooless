<?php
/*
Plugin Name: Blaze Typesense Wooless
Plugin URI: https://www.blaze.online
Description: A plugin that integrates with Typesense server.
Version: 1.2
Author: Blaze Online
Author URI: https://www.blaze.online
*/
require 'inc/vendor/autoload.php';
require_once plugin_dir_path(__FILE__) . 'inc/product/index.php';
require_once plugin_dir_path(__FILE__) . 'inc/menu/index.php';
require_once plugin_dir_path(__FILE__) . 'inc/taxonomy/index.php';
require_once plugin_dir_path(__FILE__) . 'inc/site-info/index.php';
include plugin_dir_path(__FILE__) . 'views/settings.php';
include plugin_dir_path(__FILE__) . 'views/index-page.php';
require_once plugin_dir_path(__FILE__) . 'inc/class-typesense.php';



add_action('admin_enqueue_scripts', 'enqueue_typesense_product_indexer_scripts');
add_action('admin_menu', 'add_typesense_product_indexer_menu');
add_action('wp_ajax_index_data_to_typesense', 'index_data_to_typesense');
add_action('wp_ajax_save_typesense_api_key', array($blaze_typesense, 'save_typesense_api_key'));
add_action('wp_ajax_get_typesense_collections', array($blaze_typesense, 'get_typesense_collections'));
add_action('edited_term', 'update_typesense_document_on_taxonomy_edit', 10, 3);
add_action('updated_option', 'site_info_update', 10, 3);
add_action('woocommerce_new_product', 'bwl_on_product_save', 10, 2);
add_action('woocommerce_update_product', 'bwl_on_product_save', 10, 2);
add_action('woocommerce_order_status_changed', 'bwl_on_order_status_changed', 10, 4);
add_action('wp_update_nav_menu', 'update_typesense_document_on_menu_update', 10, 2);
// Replace the action hooks with the following code:





// Instantiate the Blaze_Typesense class
$blaze_typesense = new bwl_Blaze_Typesense();

// Use the class instance to call functions, e.g.:
$blaze_typesense->save_typesense_api_key();



function enqueue_typesense_product_indexer_scripts()
{
    wp_enqueue_script('jquery');
}
function typesense_enqueue_google_fonts($hook)
{
    // Only load the font on your plugin's page
    if ('toplevel_page_typesense-product-indexer' !== $hook) {
        return;
    }

    // Register and enqueue the 'Poppins' Google Font
    wp_register_style('google-font-poppins', 'https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,300;0,500;1,400&display=swap', array(), null);
    wp_enqueue_style('google-font-poppins');
}

add_action('admin_enqueue_scripts', 'typesense_enqueue_google_fonts');


function typesense_enqueue_styles($hook)
{
    // Only load styles on your plugin's page
    if ('toplevel_page_typesense-product-indexer' !== $hook) {
        return;
    }

    // Register and enqueue your stylesheet
    wp_register_style('typesense_admin_styles', plugin_dir_url(__FILE__) . 'assets/css/style.css', array(), '1.0.0');
    wp_enqueue_style('typesense_admin_styles');
    wp_register_script('typesense_admin_script', plugin_dir_url(__FILE__) . 'assets/js/blaze-wooles.js', array('jquery'), '1.0.0');
    wp_enqueue_script('typesense_admin_script');

}
add_action('admin_enqueue_scripts', 'typesense_enqueue_styles');
add_action('admin_enqueue_scripts', 'typesense_enqueue_scripts');




function add_typesense_product_indexer_menu()
{
    add_menu_page(
        'Typesense Product Indexer',
        'Typesense Product Indexer',
        'manage_options',
        'typesense-product-indexer',
        'typesense_product_indexer_page',
        'dashicons-admin-generic'
    );
}
function typesense_product_indexer_page()
{

    echo '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;700&display=swap">';
    $private_key_master = get_option('private_key_master', '');
    ?>
    <div class="indexer_page">
        <h1>Typesense Product Indexer</h1>
        <div id="wrapper-id" class="message-wrapper">
            <div class="message-image">
                <img src="<?php echo plugins_url('blaze-wooless/assets/image/Shape.png'); ?>" alt="" srcset="">
            </div>
            <div class="wooless_message">
                <div class="message_success">Success</div>
                <div id="message"></div>
            </div>
        </div>
        <div class="wrapper">
            <label class="api_label" for="api_key">API Private Key: </label>
            <div class="input-wrapper">
                <input class="input_p" type="password" id="api_key" name="api_key"
                    value="<?php echo esc_attr($private_key_master); ?>" />
                <div class="error-icon" id="error_id" style="display: none;">
                    <img src="<?php echo plugins_url('blaze-wooless/assets/image/error.png'); ?>" alt="" srcset="">
                    <div id=" error_message">
                    </div>
                </div>
            </div>
            <input type="checkbox" id="show_api_key" onclick="toggleApiKeyVisibility()">
            <label class="checkbox_Label">Show API Key</label>
        </div>
        <div class="item_wrapper_indexer_page">
            <button id="index_products" onclick="indexData()" disabled>Manual Sync
            </button>
            <button id="check_api_key" onclick="checkApiKey()">Save</button>
            <div id="jsdecoded" style="margin-top: 10px;"></div>
            <div id="phpdecoded" style="margin-top: 10px;"></div>
        </div>
    </div>

    <?php

}





function index_data_to_typesense()
{
    $collection_name = !(empty($_POST['collection_name'])) ? $_POST['collection_name'] : '';
    if ($collection_name == 'products') {
        products_to_typesense();
    } else if ($collection_name == 'site_info') {
        site_info_index_to_typesense();
    } else if ($collection_name == 'taxonomy') {
        taxonmy_index_to_typesense();
    } else if ($collection_name == 'menu') {
        menu_index_to_typesense();
    } else {
        echo "Collection name not found";
    }
    wp_die();
}