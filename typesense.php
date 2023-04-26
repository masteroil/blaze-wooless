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

use Symfony\Component\HttpClient\HttplugClient;
use Typesense\Client;

function getTypeSenseClient($typesense_private_key)
{
    $client = new Client([
        'api_key' => $typesense_private_key, 
        'nodes' => [
            [
                'host' => 'gq6r7nsikma359hep-1.a1.typesense.net',
                'port' => '443',
                'protocol' => 'https',
            ],
        ],
        'client' => new HttplugClient(),
    ]);

    return $client;
}

add_action('admin_enqueue_scripts', 'enqueue_typesense_product_indexer_scripts');
add_action('admin_menu', 'add_typesense_product_indexer_menu');
add_action('wp_ajax_index_data_to_typesense', 'index_data_to_typesense');
add_action('wp_ajax_get_typesense_collections', 'get_typesense_collections');
add_action('wp_ajax_save_typesense_api_key', 'save_typesense_api_key');

add_action('edited_term', 'update_typesense_document_on_taxonomy_edit', 10, 3);
add_action('updated_option', 'site_info_update', 10, 3);



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
}

add_action('admin_enqueue_scripts', 'typesense_enqueue_styles');

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
                <div id="error_message"></div>
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



<script>
function toggleApiKeyVisibility() {
    var apiKeyInput = document.getElementById("api_key");
    var showApiKeyCheckbox = document.getElementById("show_api_key");

    if (showApiKeyCheckbox.checked) {
        apiKeyInput.type = "text";
    } else {
        apiKeyInput.type = "password";
    }
}

function decodeAndSaveApiKey(apiKey) {
    var decodedApiKey = atob(apiKey);
    var trimmedApiKey = decodedApiKey.split(':');
    var typesensePrivateKey = trimmedApiKey[0];
    var woolessSiteId = trimmedApiKey[1];

    // Display API key and store ID for testing purposes
    //document.getElementById("jsdecoded").innerHTML = 'Typesense Private Key: ' + typesensePrivateKey +
    //  '<br> Store ID: ' +
    //woolessSiteId;

    // Save the API key, store ID, and private key
    jQuery.post(ajaxurl, {
        'action': 'save_typesense_api_key',
        'api_key': apiKey, // Add the private key in the request
        'typesense_api_key': typesensePrivateKey,
        'store_id': woolessSiteId,
    }, function(save_response) {
        setTimeout(function() {
            document.getElementById("message").textContent += ' - ' + save_response;
        }, 1000);
    });

}

function checkApiKey() {
    var apiKey = document.getElementById("api_key").value;
    var data = {
        'action': 'get_typesense_collections',
        'api_key': apiKey,
    };
    document.getElementById("wrapper-id").style.display = "none";
    document.getElementById("index_products").disabled = true;
    document.getElementById("check_api_key").disabled = true;
    document.getElementById("check_api_key").style.cursor = "no-drop";
    document.getElementById("index_products").style.cursor = "no-drop";
    jQuery.post(ajaxurl, data, function(response) {
        console.log(response);
        var parsedResponse = JSON.parse(response);
        if (parsedResponse.status === "success") {
            //alert(parsedResponse.message);

            // Log the collection data
            console.log("Collection data:", parsedResponse.collection);
            // Decode and save the API key
            decodeAndSaveApiKey(apiKey);
            indexData();
            document.getElementById("index_products").disabled = false;
            document.getElementById("wrapper-id").style.display = "none";
            document.getElementById("error_id").style.display = "none";
            document.getElementById("index_products").style.cursor = "pointer";
        } else {
            //alert("Invalid API key. There was an error connecting to Typesense.");
            var errorMessage = "Invalid API key.";
            document.getElementById("error_message").textContent = errorMessage;
            document.getElementById("index_products").disabled = true;
            document.getElementById("error_id").style.display = "flex";
            document.getElementById("index_products").disabled = false;
            document.getElementById("check_api_key").disabled = false;
            document.getElementById("check_api_key").style.cursor = "pointer";
            document.getElementById("index_products").style.cursor = "pointer";

        }
    });
}



function indexData() {
    var apiKey = document.getElementById("api_key").value;
    var data = {
        'action': 'index_data_to_typesense',
        'api_key': apiKey,
        'collection_name': 'products',

    };
    document.getElementById("wrapper-id").style.display = "none";
    document.getElementById("message").textContent = "Indexing Data...";
    document.getElementById("check_api_key").textContent = "Indexing Data...";
    document.getElementById("index_products").disabled = true;
    document.getElementById("check_api_key").disabled = true;
    document.getElementById("check_api_key").style.cursor = "no-drop";
    document.getElementById("index_products").style.display = "none";
    jQuery.post(ajaxurl, data, function(response) {
        document.getElementById("message").textContent = response;
        data.collection_name = 'taxonomy';
        jQuery.post(ajaxurl, data, function(response) {
            data.collection_name = 'menu';
            jQuery.post(ajaxurl, data, function(response) {
                data.collection_name = 'site_info';
                jQuery.post(ajaxurl, data, function(response) {
                    document.getElementById("message").textContent = response;
                    document.getElementById("check_api_key").disabled = false;
                    document.getElementById("check_api_key").textContent =
                        "Save";
                    document.getElementById("index_products").style.display =
                        "flex";
                    document.getElementById("check_api_key").style.cursor =
                        "pointer";
                    document.getElementById("wrapper-id").style.display = "flex";
                });
            });
        });
    });
}




// Enable or disable the 'Index Products' button based on the saved API key
if (document.getElementById("api_key").value !== "") {
    document.getElementById("index_products").disabled = false;
}
</script>
<?php
}

function save_typesense_api_key()
{
    if (isset($_POST['api_key'])) {
        $private_key = $_POST['api_key'];
        $decoded_api_key = base64_decode($private_key);
        $trimmed_api_key = explode(':', $decoded_api_key);
        $typesense_api_key = $trimmed_api_key[0];
        $store_id = $trimmed_api_key[1];

        update_option('private_key_master', $private_key);
        update_option('typesense_api_key', $typesense_api_key);
        update_option('store_id', $store_id);

        //echo "Private key, API key, and store ID saved successfully.";
        // Construct the message to display
        $phpmessage = "Private key: " . $private_key . "<br>";
        $phpmessage .= "Typesense API key: " . $typesense_api_key . "<br>";
        $phpmessage .= "Store ID: " . $store_id;

        // Echo the message to the div
        //echo "<script>document.getElementById('phpdecoded').innerHTML = 'Private key, API key, and store ID saved successfully.';</script>";
    } else {
        echo "Error: Private key not provided.";
    }

    wp_die();
}

function get_typesense_collections()
{
    if (isset($_POST['api_key'])) {
        $encoded_api_key = sanitize_text_field($_POST['api_key']);
        $decoded_api_key = base64_decode($encoded_api_key);
        $trimmed_api_key = explode(':', $decoded_api_key);
        $typesense_private_key = $trimmed_api_key[0];
        $wooless_site_id = $trimmed_api_key[1];

        $client = getTypeSenseClient($typesense_private_key);


        try {
            $collection_name = 'product-' . $wooless_site_id;
            $collections = $client->collections[$collection_name]->retrieve();
            if (!empty($collections)) {
                echo json_encode(['status' => 'success', 'message' => 'Typesense is working!', 'collection' => $collections]);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'No collection found for store ID: ' . $wooless_site_id]);
            }
        } catch (Typesense\Exception\ObjectNotFound $e) {
            echo json_encode(['status' => 'error', 'message' => 'Collection not found: ' . $e->getMessage()]);
        } catch (Typesense\Exception\TypesenseClientError $e) {
            echo json_encode(['status' => 'error', 'message' => 'Typesense client error: ' . $e->getMessage()]);
        } catch (Exception $e) {
            echo json_encode(['status' => 'error', 'message' => 'There was an error connecting to Typesense: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'API key not provided.']);
    }

    wp_die();
}





function taxonmy_index_to_typesense()
{
    $typesense_private_key = get_option('typesense_api_key');
    $client = getTypeSenseClient($typesense_private_key);

    // Fetch the store ID from the saved options
    $wooless_site_id = get_option('store_id');
    $collection_taxonomy = 'taxonomy-' . $wooless_site_id;
    //indexing taxonmy terms
    try {
        // Initialize the Typesense client
        $client = getTypeSenseClient($typesense_private_key);

        // Delete the existing 'TaxonomyTerms-gb' collection (if it exists)
        try {
            $client->collections[$collection_taxonomy]->delete();
        } catch (Exception $e) {
            // Don't error out if the collection was not found
        }
        $client->collections->create([
            'name' => $collection_taxonomy,
            'fields' => [
                ['name' => 'slug', 'type' => 'string', 'facet' => true],
                ['name' => 'name', 'type' => 'string', 'facet' => true, 'infix' => true],
                ['name' => 'description', 'type' => 'string'],
                ['name' => 'type', 'type' => 'string', 'facet' => true, 'infix' => true],
                ['name' => 'permalink', 'type' => 'string'],
                ['name' => 'updatedAt', 'type' => 'int64'],
                ['name' => 'bannerThumbnail', 'type' => 'string'],
                ['name' => 'bannerText', 'type' => 'string'],
            ],
            'default_sorting_field' => 'updatedAt',
        ]);

        // Add the custom taxonomies to this array
        $taxonomies = get_taxonomies([], 'names');

        // Fetch terms for all taxonomies except those starting with 'ef_'
        foreach ($taxonomies as $taxonomy) {
            // Skip taxonomies starting with 'ef_'
            if (preg_match('/^(ef_|elementor|pa_|nav_|ml-|ufaq|product_visibility)/', $taxonomy)) {
                continue;
            }

            $args = [
                'taxonomy' => $taxonomy,
                'hide_empty' => false,
            ];

            $terms = get_terms($args);

            if (!empty($terms) && !is_wp_error($terms)) {
                foreach ($terms as $term) {

                    $latest_modified_date = null;

                    $query_args = [
                        'post_type' => 'any',
                        'posts_per_page' => 1,
                        'orderby' => 'modified',
                        'order' => 'DESC',
                        'tax_query' => [
                            [
                                'taxonomy' => $taxonomy,
                                'field' => 'term_id',
                                'terms' => $term->term_id,
                            ],
                        ],
                    ];

                    $latest_post_query = new WP_Query($query_args);

                    if ($latest_post_query->have_posts()) {
                        while ($latest_post_query->have_posts()) {
                            $latest_post_query->the_post();
                            $latest_modified_date = get_the_modified_date('Y-m-d H:i:s', get_the_ID());
                        }
                        wp_reset_postdata();
                    }

                    // Get the custom fields (bannerThumbnail and bannerText)
                    $bannerThumbnail = get_term_meta($term->term_id, 'wpcf-image', true);
                    $bannerText = get_term_meta($term->term_id, 'wpcf-term-banner-text', true);

                    if ($latest_modified_date) {
                        $timestamp = strtotime($latest_modified_date);
                        //var_dump($latest_modified_date, $timestamp);
                    }

                    // Prepare the data to be indexed
                    $document = [
                        'slug' => $term->slug,
                        'name' => $term->name,
                        'description' => $term->description,
                        'type' => $taxonomy,
                        'permalink' => get_term_link($term),
                        'updatedAt' => $latest_modified_date ? (int) strtotime($latest_modified_date) : 0,
                        'bannerThumbnail' => $bannerThumbnail,
                        'bannerText' => $bannerText,

                    ];
                    // Index the term data in Typesense
                    try {
                        $client->collections[$collection_taxonomy]->documents->create($document);
                    } catch (Exception $e) {
                        echo "Error adding term '{$term->name}' to Typesense: " . $e->getMessage() . "\n";
                    }
                }
            }
        }

        echo "taxonomy added successfully!\n";
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
function site_info_index_to_typesense()
{
    $typesense_private_key = get_option('typesense_api_key');
    $client = getTypeSenseClient($typesense_private_key);

    // Fetch the store ID from the saved options
    $wooless_site_id = get_option('store_id');
    $collection_site_info = 'site_info-' . $wooless_site_id;
    //Indexing Site Info
    try {
        // Initialize the Typesense client
        $client = getTypeSenseClient($typesense_private_key);

        // Delete the existing 'site_info' collection (if it exists)
        try {
            $client->collections[$collection_site_info]->delete();
        } catch (Exception $e) {
            // Don't error out if the collection was not found
        }
        // Create the 'site_info' collection with the required schema
        $client->collections->create([
            'name' => $collection_site_info,
            'fields' => [
                [
                    'name' => 'name',
                    'type' => 'string',
                ],
                [
                    'name' => 'value',
                    'type' => 'string',
                ],
                [
                    'name' => 'updated_at',
                    'type' => 'int64',
                ],

            ],
            'default_sorting_field' => 'updated_at',
        ]);
        //display site title
        $SiteTitle = get_bloginfo('name');
        $SiteTagline = get_bloginfo('description');
        $updatedAt = time(); // The current Unix timestamp
        //display logo
        $logo_id = get_theme_mod('custom_logo');
        $logo_image = wp_get_attachment_image_src($logo_id, 'full');
        $logo_metadata = wp_get_attachment_metadata($logo_id);
        $logo_updated_at = isset($logo_metadata['file']) ? strtotime(date("Y-m-d H:i:s", filemtime(get_attached_file($logo_id)))) : null;
        $updatedAt = $logo_updated_at ? strtotime(date("Y-m-d H:i:s", $logo_updated_at)) : time();
        // Display the logo URL as a JSON object
        if ($logo_image) {
            $logo_url = $logo_image[0];
            //echo "Logo URL: " . $logo_url;
        }
        $store_notice = get_option('woocommerce_demo_store_notice');


        if ($store_notice) {
            //echo "Store Notice: " . $store_notice . "\n";

            // Get the last updated timestamp of the 'woocommerce_demo_store_notice' option
            global $wpdb;
            $store_notice_updated_at = $wpdb->get_var("SELECT UNIX_TIMESTAMP(option_value) FROM {$wpdb->options} WHERE option_name = '_transient_timeout_woocommerce_demo_store_notice'") ?: time();

        }
        //display the time format and its timestamp
        $time_format = get_option('time_format');
        $current_unix_timestamp = time();
        //display the search_engine and its timestamp
        $search_engine = get_option('blog_public');
        $search_engine_last_updated = get_option('blog_public_last_updated', time());
        //    $site_id = get_current_blog_id();
        $site_id = get_current_blog_id();
        $site_id_string = strval($site_id);
        //$wordpress_address_url = get_site_url();
        $wordpress_address_url = get_site_url();

        // Get the Site Icon URL
        $site_icon_id = get_option('site_icon');
        $favicon_url = $site_icon_id ? wp_get_attachment_image_url($site_icon_id, 'full') : '';

        // Get the favicon last updated timestamp
        if ($site_icon_id) {
            $favicon_updated_at = strtotime(get_the_modified_date('Y-m-d H:i:s', $site_icon_id));
        } else {
            $favicon_updated_at = 0;
        }


        //admin email
        function my_admin_email_updated_callback($old_value, $new_value, $option)
        {
            update_option('admin_email_last_updated', time());
        }
        add_action('update_option_admin_email', 'my_admin_email_updated_callback', 10, 3);

        add_action('update_option_admin_email', 'my_admin_email_updated_callback', 10, 3);
        $admin_email = get_option('admin_email');
        $admin_email_last_updated = get_option('admin_email_last_updated', time());
        //language
        function my_locale_updated_callback($old_value, $new_value, $option)
        {
            update_option('locale_last_updated', time());
        }
        add_action('update_option_WPLANG', 'my_locale_updated_callback', 10, 3);
        $language = get_locale();
        $language_last_updated = get_option('locale_last_updated', time());
        //timezone
        function my_timezone_updated_callback($old_value, $new_value, $option)
        {
            update_option('timezone_last_updated', time());
        }
        add_action('update_option_timezone_string', 'my_timezone_updated_callback', 10, 3);
        $timezone = date_default_timezone_get();
        $wp_timezone_last_updated = get_option('timezone_last_updated', time());
        //Date format
        function my_date_format_updated_callback($old_value, $new_value, $option)
        {
            update_option('date_format_last_updated', time());
        }
        add_action('update_option_date_format', 'my_date_format_updated_callback', 10, 3);
        $date_format = get_option('date_format');
        $date_format_last_updated = get_option('date_format_last_updated', time());
        
        // Get available payment gateways
        $available_gateways = WC()->payment_gateways->get_available_payment_gateways();
        $payment_methods = [];
        
        if ( ! empty( $available_gateways ) ) {
            foreach ( $available_gateways as $gateway ) {
                $payment_methods[] = $gateway->get_title() . ' (ID: ' . $gateway->id . ')';
            }
        }
        
        // Convert payment methods array to a JSON string
        $payment_methods_json = json_encode($payment_methods);

 
        global $wpdb;

        // Fetch the 'active_plugins' option from the WordPress options table
        $active_plugins_serialized = $wpdb->get_var("SELECT option_value FROM " . $wpdb->options . " WHERE option_name = 'active_plugins'");
        $active_plugins = unserialize($active_plugins_serialized);

        // List of known review plugin slugs
        $review_plugin_slugs = [
            'reviewscouk-for-woocommerce',
            'wp-review',
            'wp-product-review-lite',
            'all-in-one-schemaorg-rich-snippets',
            'site-reviews',
            'ultimate-reviews',
            'taqyeem',
            'author-hreview',
            'rich-reviews',
            'customer-reviews-for-woocommerce',
            'reviewer',
            'yelp-widget-pro',
            'testimonials-widget',
            'google-reviews-widget',
            'reviewer-plugin',
            'wp-customer-reviews',
            'starcat-reviews',
            'trustpilot-reviews',
            'tripadvisor-reviews',
            'facebook-reviews-pro',
            'wp-reviews',
            'multi-rating-pro'
        ];
        // Filter the active plugins by the known review plugin slugs
        $filtered_plugins = array_filter($active_plugins, function ($plugin) use ($review_plugin_slugs) {
            foreach ($review_plugin_slugs as $slug) {
                if (strpos($plugin, $slug) !== false) {
                    return true;
                }
            }
            return false;
        });

        // Extract the plugin directory names
        $filtered_plugin_directories = array_map(function ($plugin) {
            return dirname($plugin);
        }, $filtered_plugins);

        // Convert the filtered plugin directory names array to a string
        $filtered_plugin_directories_string = implode(', ', $filtered_plugin_directories);

        // Get the permalink structure from WordPress
        $permalink_structure = get_option('woocommerce_permalinks');
        $product_base = isset($permalink_structure['product_base']) ? $permalink_structure['product_base'] : '';

        // If the product base does not start with a slash, add one
        if ($product_base && $product_base[0] !== '/') {
            $product_base = '/' . $product_base;
        }

        $category_base = get_option('category_base') ?: 'category';
        $tag_base = get_option('tag_base') ?: 'tag';
        $base_permalink_structure = get_option('permalink_structure');

        // Assemble the permalink structure JSON object
        $permalink_structure = [
            'product' => $product_base . '/%postname%',
            'category' => '/' . $category_base . '/%categoryname%',
            'tag' => '/' . $tag_base . '/%tagname%',
            'base' => $base_permalink_structure . '/%postname%',
            'posts' => '/blog/%postname%',
            'pages' => $base_permalink_structure . '/%pagename%',
        ];

        // Convert the permalink structure to a JSON-encoded string
        $permalink_structure = json_encode($permalink_structure);
        
        // Get WooCommerce stock settings
        $manage_stock = get_option('woocommerce_manage_stock'); // 'yes' or 'no'
        $stock_format = get_option('woocommerce_stock_format'); // 'always', 'never', or 'low_amount'

        // If stock management is disabled, the stock display format will be empty
        if ($manage_stock === 'no') {
            $stock_display_format = '';
        } else {
            // Determine the stock display format based on the 'woocommerce_stock_format' option
            switch ($stock_format) {
                case 'always':
                    $stock_display_format = 'always';
                    break;
                case 'never':
                    $stock_display_format = 'never';
                    break;
                case 'low_amount':
                default:
                    $stock_display_format = 'low_amount';
                    break;
            }
        }

        // Now, $stock_display_format contains the stock display format setting from WooCommerce

        // Send the stock display format value to Typesense
        $document_id = 'stock_display_format_setting'; // Set an appropriate document ID
        $updated_at = time(); // Use the current time as the updated_at value
        
        $client->collections[$collection_site_info]->documents->create([
            'name' => 'stock_display_format',
            'value' => $stock_display_format,
            'updated_at' => $updated_at,
        ]);

        // Add the permalink structure to Typesense
        $client->collections[$collection_site_info]->documents->create([
            'name' => 'permalink_structure',
            'value' => $permalink_structure,
            'updated_at' => time(),
        ]);


        $client->collections[$collection_site_info]->documents->create([
            'name' => 'reviews_plugin',
            'value' =>  $filtered_plugin_directories_string,
            'updated_at' => $updatedAt,
        ]);


        $client->collections[$collection_site_info]->documents->create([
            'name' => 'Payment_methods',
            'value' => $payment_methods_json,
            'updated_at' => $updatedAt,
        ]);


        $client->collections[$collection_site_info]->documents->create([
            'name' => 'Site_title',
            'value' => $SiteTitle,
            'updated_at' => $updatedAt,
        ]);

        $client->collections[$collection_site_info]->documents->create([
            'name' => 'Site_tagline',
            'value' => $SiteTagline,
            'updated_at' => $updatedAt,
        ]);
        $client->collections[$collection_site_info]->documents->create([
            'name' => 'site_logo',
            'value' => $logo_url,
            'updated_at' => $logo_updated_at,
        ]);
        $client->collections[$collection_site_info]->documents->create([
            'name' => 'Store_notice',
            'value' => $store_notice,
            'updated_at' => intval($store_notice_updated_at),
        ]);
        $client->collections[$collection_site_info]->documents->create([
            'name' => 'Time_format',
            'value' => $time_format,
            'updated_at' => intval($current_unix_timestamp),
        ]);
        $client->collections[$collection_site_info]->documents->create([
            'name' => 'Search_engine',
            'value' => $search_engine,
            'updated_at' => intval($search_engine_last_updated),
        ]);
        $client->collections[$collection_site_info]->documents->create([
            'name' => 'Site_ID',
            'value' => $site_id_string,
            'updated_at' => intval($search_engine_last_updated),
        ]);
        $client->collections[$collection_site_info]->documents->create([
            'name' => 'WordPressAddressURL',
            'value' => $wordpress_address_url,
            'updated_at' => intval($search_engine_last_updated),
        ]);
        $client->collections[$collection_site_info]->documents->create([
            'name' => 'AdministrationEmailAddress',
            'value' => $admin_email,
            'updated_at' => intval($admin_email_last_updated),
        ]);
        $client->collections[$collection_site_info]->documents->create([
            'name' => 'Language',
            'value' => $language,
            'updated_at' => intval($language_last_updated),
        ]);
        $client->collections[$collection_site_info]->documents->create([
            'name' => 'Time_zone',
            'value' => $timezone,
            'updated_at' => intval($wp_timezone_last_updated),
        ]);
        $client->collections[$collection_site_info]->documents->create([
            'name' => 'Date_format',
            'value' => $date_format,
            'updated_at' => intval($date_format_last_updated),
        ]);
        $client->collections[$collection_site_info]->documents->create([
            'name' => 'fav_icon',
            'value' => $favicon_url,
            'updated_at' => intval($store_notice_updated_at),
        ]);
        echo "Site info added successfully!";
    } catch (Exception $e) {
        echo $e->getMessage();
    }

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


function update_typesense_document_on_taxonomy_edit($term_id, $tt_id, $taxonomy)
{
    $typesense_private_key = get_option('typesense_api_key');
    $client = getTypeSenseClient($typesense_private_key);

    // Fetch the store ID from the saved options
    $wooless_site_id = get_option('store_id');
    $collection_taxonomy = 'taxonomy-' . $wooless_site_id;
    // Check if the taxonomy starts with 'ef_'
    if (strpos($taxonomy, 'ef_') === 0) {
        return;
    }

    // Get the term
    $term = get_term($term_id, $taxonomy);

    if (!$term || is_wp_error($term)) {
        return;
    }

    // Initialize the Typesense client
    $client = getTypeSenseClient($typesense_private_key);

    // Get the custom fields (bannerThumbnail and bannerText)
    $bannerThumbnail = get_term_meta($term->term_id, 'wpcf-image', true);
    $bannerText = get_term_meta($term->term_id, 'wpcf-term-banner-text', true);

    // Prepare the data to be updated
    $document = [
        'slug' => $term->slug,
        'name' => $term->name,
        'description' => $term->description,
        'type' => $taxonomy,
        'permalink' => get_term_link($term),
        'updatedAt' => time(),
        'bannerThumbnail' => $bannerThumbnail,
        'bannerText' => $bannerText,
    ];

    // Update the term data in Typesense
    try {
        $client->collections[$collection_taxonomy]->documents[strval($term->term_id)]->update($document);
    } catch (Exception $e) {
        error_log("Error updating term '{$term->name}' in Typesense: " . $e->getMessage());
    }
}

// Function to be called when an option is updated
function site_info_update($option_name, $old_value, $new_value) {
    // Array of target General Settings options
    $target_settings = array(
        'blogname',
        'blogdescription',
        'siteurl',
        'home',
        'admin_email',
        'users_can_register',
        'default_role',
        'timezone_string',
        'date_format',
        'time_format',
        'start_of_week',
        'WPLANG',
        'woocommerce_demo_store',
    );

    // Check if the updated option is in the array of target settings
    if (in_array($option_name, $target_settings)) {
        site_info_index_to_typesense();
    }
}