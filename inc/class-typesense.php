<?php

require 'inc/vendor/autoload.php';
use Symfony\Component\HttpClient\HttplugClient;
use Typesense\Client;

class bwl_Blaze_Typesense
{



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
}