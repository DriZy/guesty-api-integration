<?php

class Guesty_API
{
    private $base_url = 'https://booking.guesty.com/';
    private $client_id;
    private $client_secret;
    private $token;
    public function __construct()
    {
        $this->client_id = get_option('guesty_api_client_id');
        $this->client_secret = get_option('guesty_api_client_secret');
        $this->token = get_transient('guesty_api_token');
        if (!$this->token) {
            $this->authenticate();
        }
    }
    public function log_plugin_event($message)
    {
        if (!function_exists('guesty_api_log_event')) {
            require_once plugin_dir_path(__DIR__) . '../guesty-api-integration.php';
        }
        guesty_api_log_event($message);
    }

    public function authenticate()
    {
        if (empty($this->client_id) || empty($this->client_secret)) {
            $msg = 'Guesty API client ID or secret is not set.';
            $this->log_plugin_event($msg);
            wp_send_json_error([
                'success' => false,
                'message' => $msg
            ], 400);
        }

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL => $this->base_url . 'oauth2/token',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => http_build_query([
                'grant_type' => 'client_credentials',
                'scope' => 'booking_engine:api',
                'client_secret' => $this->client_secret,
                'client_id' => $this->client_id
            ]),
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/x-www-form-urlencoded'
            ],
        ]);

        $response = curl_exec($curl);
        $error = curl_error($curl);
        curl_close($curl);

        if ($error) {
            $msg = 'cURL error: ' . $error;
            $this->log_plugin_event($msg);
            wp_send_json_error([
                'success' => false,
                'message' => $msg
            ], 500);
        }

        $msg = 'response: ' . $response;

        $this->log_plugin_event($msg);


        $response_body = json_decode($response, true);

        if (isset($response_body['access_token'])) {
            $this->token = $response_body['access_token'];
            set_transient('guesty_api_token', $this->token, $response_body['expires_in']);
            $this->log_plugin_event('Successfully authenticated with Guesty API. Access token: ' . $this->token . ' Expires in: ' . $response_body['expires_in'] . ' seconds');
            wp_send_json_success([
                'success' => true,
                'access_token' => $this->token,
                'expires_in' => $response_body['expires_in']
            ]);
        } else {
            $msg = 'Failed to retrieve access token from Guesty API: ' . ($response_body['errorSummary'] ?? 'Unknown error');
            $this->log_plugin_event($msg);
            wp_send_json_error([
                'success' => false,
                'message' => $msg
            ], 400);
        }
    }
    private function sanitize_data($data)
    {
        if (is_array($data)) {
            return array_map([$this, 'sanitize_data'], $data);
        }
        return is_null($data) ? '' : sanitize_text_field($data);
    }

    private function request($endpoint, $args = [], $method = 'GET')
    {
        $url = $this->base_url . ltrim($endpoint, '/');
        $headers = ['Authorization' => 'Bearer ' . $this->token];
        $request_args = ['headers' => $headers, 'timeout' => 15];

        if ($method === 'POST') {
            $request_args['body'] = json_encode($args);
            $request_args['headers']['Content-Type'] = 'application/json';
        } elseif ($method === 'GET' && !empty($args)) {
            $url = add_query_arg($args, $url);
        }

        $response = wp_remote_request($url, array_merge($request_args, ['method' => $method]));

        if (is_wp_error($response)) {
            return ['error' => $response->get_error_message()];
        }

        $code = wp_remote_retrieve_response_code($response);
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if ($code >= 400) {
            return ['error' => $body['message'] ?? __('API Error', 'guesty-api-integration')];
        }

        return $this->sanitize_data($body);
    }
    public function get_properties($args = [])
    {
        return $this->request('api/listings', $args);
    }
    public function get_property($id)
    {
        return $this->request('api/listings/' . $id);
    }
    public function create_booking($data)
    {
        return $this->request('bookings', $data, 'POST');
    }
    public function insert_listings_into_property_post_type($listings)
    {
        if (empty($listings) || !is_array($listings)) {
            $this->log_plugin_event('No valid listings provided for insertion into the property post type.');
            return null; // Updated to return null explicitly
        }

        $this->log_plugin_event(sprintf('Preparing to insert %d listings into the property post type.', count($listings)));

        foreach ($listings as $listing) {
            // Prepare post data
            $post_data = [
                'post_title'    => $listing['title'] ?? 'Untitled Property',
                'property_title'    => $listing['title'] ?? 'Untitled Property',
                'guesty_id'    => $listing['_id'],
                '_description'  => $listing['publicDescription']['space'] ?? '',
                'bedrooms'  => $listing['bedrooms'] ?? 1,
                'bathrooms'  => $listing['bathrooms'] ?? 1,
                'post_status'   => 'publish',
                'post_type'     => 'property',
            ];

            // Insert the post
            $post_id = wp_insert_post($post_data);

            if ($post_id) {
                $this->log_plugin_event(sprintf('Successfully inserted listing with ID %d.', $post_id));

                // Save custom meta fields
                update_post_meta($post_id, 'property_title', $listing['title'] ?? 'Untitled Property');
                update_post_meta($post_id, 'guesty_id', $listing['_id']);
                update_post_meta($post_id, '_description', $listing['publicDescription']['space'] ?? '');
                update_post_meta($post_id, 'bedrooms', $listing['bedrooms'] ?? 1);
                update_post_meta($post_id, 'bathrooms', $listing['bathrooms'] ?? 1);

                // Add featured image if available
                if (!empty($listing['picture']) && is_array($listing['picture'])) {
                    $featured_image = $listing['picture']; // Use the first image as the featured image
                    $this->upload_image_and_set_as_thumbnail($featured_image['thumbnail'], $featured_image['caption'] ?? 'No caption', $post_id);
                }
            } else {
                $this->log_plugin_event('Failed to insert a listing.');
            }
        }

        $this->log_plugin_event(sprintf('Completed inserting %d listings into the property post type.', count($listings)));
    }
    public function upload_image_and_set_as_thumbnail($image_url, $image_caption, $post_id)
    {
        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        // Step 1: Download the image to a temporary file
        $tmp = download_url($image_url);

        // Check for download errors
        if (is_wp_error($tmp)) {
            error_log('Image download failed: ' . $tmp->get_error_message());
            return false; // Updated to return false explicitly
        }

        // Step 2: Prepare the file array
        $file_array = array(
            'name'     => basename(parse_url($image_url, PHP_URL_PATH)),
            'tmp_name' => $tmp,
        );

        // Step 3: Upload the file to the media library
        $attachment_id = media_handle_sideload($file_array, $post_id);

        // Check for sideload errors
        if (is_wp_error($attachment_id)) {
            @unlink($tmp); // Clean up
            error_log('Image upload failed: ' . $attachment_id->get_error_message());
            return false; // Explicitly return false
        }

        // Step 4: Update attachment with caption and alt text
        wp_update_post(array(
            'ID'           => $attachment_id,
            'post_excerpt' => $image_caption, // Caption
            'post_content' => $image_caption, // Description
        ));
        update_post_meta($attachment_id, '_wp_attachment_image_alt', $image_caption);

        // Step 5: Set as post thumbnail
        set_post_thumbnail($post_id, $attachment_id);

        // Step 6: Return success or failure
        return true; // Updated to return true explicitly
    }

    public function get_availability($listing_id, $start_date, $end_date)
    {
        $endpoint = "api/listings/{$listing_id}/calendar";
        $args = [
            'from' => $start_date,
            'to' => $end_date
        ];

        $max_retries = 3; // Maximum number of retries
        $retry_delay = 2; // Initial delay in seconds

        for ($attempt = 1; $attempt <= $max_retries; $attempt++) {
            $response = $this->request($endpoint, $args);

            if (isset($response['error']['code']) && $response['error']['code'] === 'TOO_MANY_REQUESTS') {
                error_log("Rate limit hit. Attempt {$attempt} of {$max_retries}.");

                if ($attempt < $max_retries) {
                    sleep($retry_delay); // Wait before retrying
                    $retry_delay *= 2; // Exponential backoff
                    continue;
                } else {
                    error_log('Max retries reached. Unable to fetch availability.');
                    return [
                        'success' => false,
                        'message' => 'Rate limit exceeded. Please try again later.'
                    ];
                }
            }

            // If no rate limit error, return the response
            return $response;
        }

        // Fallback in case of unexpected failure
        return [
            'success' => false,
            'message' => 'Failed to fetch availability due to an unknown error.'
        ];
    }
}
