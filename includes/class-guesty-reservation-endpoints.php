<?php
require_once plugin_dir_path(__FILE__) . 'class-guesty-api.php';

// Endpoint to handle property reservation requests
add_action('rest_api_init', function () {
    register_rest_route('guesty/v1', '/create-reservation', [
        'methods' => 'POST',
        'callback' => 'handle_create_reservation',
        'permission_callback' => '__return_true', // Adjust permissions as needed
    ]);
});

function handle_create_reservation(WP_REST_Request $request)
{
    // Extract parameters from the request
    $property_id = $request->get_param('property_id');
    $check_in = $request->get_param('check_in');
    $check_out = $request->get_param('check_out');
    $guests = $request->get_param('guests');

    // Validate required parameters
    if (!$property_id || !$check_in || !$check_out || !$guests) {
        return new WP_REST_Response(['error' => 'Missing required parameters.'], 400);
    }

    // Initialize Guesty API client
    $guesty_api = new Guesty_API();

    // Call the API to create a reservation quote
    $response = $guesty_api->create_reservation_quote($property_id, $check_in, $check_out, $guests);

    if (isset($response['error'])) {
        return new WP_REST_Response(['error' => $response['error']], 500);
    }

    return new WP_REST_Response($response, 200);
}
