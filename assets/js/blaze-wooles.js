jQuery(document).ready(function ($){

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
    jQuery.ajax({
        type: "POST",
        url: ajaxurl,
        data: {
            action: "save_typesense_api_key",
            api_key: apiKey,
        },
        success: function (response) {
            console.log("Saving API Key response:", response);
            document.getElementById("phpdecoded").innerHTML = response;
        },
        error: function (response) {
            console.log("Error saving API Key:", response);
        },
    });
}


function checkApiKey() {
    var apiKey = document.getElementById("api_key").value;
    var data = {
        'action': 'get_typesense_collections',
        'api_key': typesensePrivateKey,
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

    // Assign functions to global scope
    window.checkApiKey = checkApiKey;
    window.indexData = indexData;
    window.toggleApiKeyVisibility = toggleApiKeyVisibility;
    window.decodeAndSaveApiKey = decodeAndSaveApiKey;
});