<?php
/*
Plugin Name: Guesty API Integration
Description: Integrate Guesty API with WordPress. Provides property search, list/grid/single views, booking, and admin configuration for API, caching, and webhooks.
Version: 1.0.0
Author: Tabi IdrisZ
Text Domain: guesty-api-integration
Domain Path: /languages
*/

if ( ! defined( 'ABSPATH' ) ) exit;

// Autoload classes
spl_autoload_register( function ( $class ) {
    if ( strpos( $class, 'Guesty_' ) === 0 ) {
        $file = plugin_dir_path( __FILE__ ) . 'includes/class-' . strtolower( str_replace( '_', '-', $class ) ) . '.php';
        if ( file_exists( $file ) ) {
            require_once $file;
        }
    }
});

// Log plugin events to a file in the plugin directory
function guesty_api_log_event($message) {
    $log_file = plugin_dir_path(__FILE__) . 'guesty-api-integration.log';
    $date = date('Y-m-d H:i:s');
    $entry = "[$date] $message\n";
    $result = @file_put_contents($log_file, $entry, FILE_APPEND | LOCK_EX);
    if ($result === false) {
        error_log('[Guesty API Integration] Failed to write to log file: ' . $log_file);
        error_log('[Guesty API Integration] Last error: ' . print_r(error_get_last(), true));
    }
}

// Log activation
register_activation_hook(__FILE__, function() {
    guesty_api_log_event('Plugin activated.');

    // Flush rewrite rules to ensure the custom post type is registered correctly
    flush_rewrite_rules();
});

// Register the custom post type during WordPress initialization
add_action('init', function() {
    if (!post_type_exists('property')) {
        register_post_type('property', [
            'labels' => [
                'name' => __('Properties', 'guesty-api-integration'),
                'singular_name' => __('Property', 'guesty-api-integration')
            ],
            'public' => true,
            'has_archive' => true,
            'supports' => ['title', 'editor', 'thumbnail'],
        ]);
    }
});

// Log deactivation
register_deactivation_hook(__FILE__, function() {
    guesty_api_log_event('Plugin deactivated.');
});

// Main plugin init
add_action( 'plugins_loaded', function() {
    load_plugin_textdomain( 'guesty-api-integration', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
    Guesty_Plugin::instance();
});

// Add a custom cron schedule for every 12 hours.
add_filter( 'cron_schedules', 'guesty_add_cron_interval' );
function guesty_add_cron_interval( $schedules ) {
    $schedules['twelve_hours'] = array(
        'interval' => 43200,
        'display'  => esc_html__( 'Every 12 Hours' ),
    );
    return $schedules;
}

// Schedule the cron job.
add_action( 'wp', 'guesty_schedule_cron' );
function guesty_schedule_cron() {
    if ( ! wp_next_scheduled( 'guesty_fetch_properties_cron' ) ) {
        wp_schedule_event( time(), 'twelve_hours', 'guesty_fetch_properties_cron' );
    }
}

// Hook the function to the cron job.
add_action( 'guesty_fetch_properties_cron', 'guesty_fetch_properties' );
function guesty_fetch_properties() {
    require_once plugin_dir_path(__FILE__) . 'includes/class-guesty-api.php';
    $api = new Guesty_API();
    $result = $api->get_properties(['limit' => 100]);
    if (!empty($result['results'])) {
        $api->insert_listings_into_property_post_type($result['results']);
    }
}

// Display Guesty API authentication result in admin notices
add_action('admin_notices', function() {
    if ( $result = get_transient('guesty_api_auth_result') ) {
        $class = $result['success'] ? 'notice-success' : 'notice-error';
        printf('<div class="notice %s is-dismissible"><p>%s</p></div>', esc_attr($class), esc_html($result['message']));
        delete_transient('guesty_api_auth_result');
    }
});

// Enqueue admin JS and localize AJAX URL/nonce
add_action('admin_enqueue_scripts', function($hook) {
    if (strpos($hook, 'guesty-api') !== false) {
        wp_enqueue_script('guesty-admin-js', plugin_dir_url(__FILE__) . 'assets/js/admin-guesty.js', ['jquery'], null, true);
        wp_localize_script('guesty-admin-js', 'guestyApi', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('guesty_api_nonce')
        ]);
    }
});

// Enqueue dynamic calendar JS for single property pages
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_script('fullcalendar-js', plugin_dir_url(__FILE__) . 'assets/js/fullcalendar.min.js', ['jquery'], null, true);
    if (is_singular('property')) {
        wp_enqueue_script('dynamic-calendar', plugin_dir_url(__FILE__) . 'assets/js/dynamic-calendar.js', ['jquery'], null, true);
        wp_localize_script('dynamic-calendar', 'guestyApi', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('guesty_api_nonce'),
            'post_id'  => get_the_ID(),
            'post_title' => get_the_title(),
        ]);
    }
    wp_enqueue_script('jquery-modal-js', plugin_dir_url(__FILE__) . 'assets/js/jquery-modal.min.js', ['jquery'], null, true);
    wp_enqueue_style('jquery-modal-css', plugin_dir_url(__FILE__) . 'assets/css/jquery-modal.min.css', [], null);
});


// AJAX handler for test connection
add_action('wp_ajax_guesty_test_connection', function() {
    check_ajax_referer('guesty_api_nonce');
    require_once plugin_dir_path(__FILE__) . 'includes/class-guesty-api.php';
    $api = new Guesty_API();
    $auth_result = $api->authenticate();
    if ($auth_result === false) {
        guesty_api_log_event('AJAX Test Connection: Authentication failed.');
        wp_send_json_error(['message' => 'Authentication failed. Check credentials and logs.']);
    }
    guesty_api_log_event('AJAX Test Connection: Success.');
    wp_send_json_success(['message' => 'Connection successful!']);
});

// AJAX handler for populating properties
add_action('wp_ajax_guesty_populate_properties', function() {
    guesty_api_log_event('AJAX Populate Properties: Call initiated.');
    check_ajax_referer('guesty_api_nonce');
    require_once plugin_dir_path(__FILE__) . 'includes/class-guesty-api.php';
    $api = new Guesty_API();
    $result = $api->get_properties(['limit' => 5]);
    if (!empty($result['error'])) {
        guesty_api_log_event('AJAX Populate Properties: API error - ' . $result['error']);
        wp_send_json_error(['message' => 'API error: ' . $result['error']]);
    }
    if (!empty($result['results'])) {
        $api->insert_listings_into_property_post_type($result['results']);
    }
    guesty_api_log_event('AJAX Populate Properties: Success.');
    wp_send_json_success(['message' => 'Successfully populated properties.']);
});

// Add AJAX handler for fetching calendar data
add_action('wp_ajax_guesty_fetch_calendar_data', 'guesty_fetch_calendar_data');
add_action('wp_ajax_nopriv_guesty_fetch_calendar_data', 'guesty_fetch_calendar_data');

function guesty_fetch_calendar_data() {
    guesty_api_log_event('AJAX Fetch Calendar Data: Call initiated.');
    check_ajax_referer('guesty_api_nonce');
    global $post;

    $post_id = isset($_POST['post_id']) ? sanitize_text_field($_POST['post_id']) : '';
    $start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : date('Y-m-01');
    $end_date = isset($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : date('Y-m-t');

    $listing_id = (string) get_post_meta($post_id, 'guesty_id', true);

    if (empty($listing_id)) {
        guesty_api_log_event('Error: Listing ID is required for post ID ' . $post_id);
        wp_send_json_error(['message' => __('Listing ID is required', 'guesty-api')]);
        return;
    }

    require_once plugin_dir_path(__FILE__) . 'includes/class-guesty-api.php';
    $api = new Guesty_API();
    $availability = $api->get_availability($listing_id, $start_date, $end_date);

    if (is_wp_error($availability)) {
        guesty_api_log_event('Error fetching availability: ' . $availability->get_error_message());
        wp_send_json_error(['message' => $availability->get_error_message()]);
        return;
    }

    if (empty($availability) || !is_array($availability)) {
        guesty_api_log_event('No availability data found for listing ID ' . $listing_id);
        wp_send_json_error(['message' => __('No availability data found.', 'guesty-api')]);
        return;
    }

    guesty_api_log_event('Successfully fetched availability data for listing ID ' . $listing_id);
    wp_send_json_success(['data' => $availability]);
}

// Ensure the calendar is injected into the single property view
//add_action('the_content', function($content) {
//    if (is_singular('property')) {
//        $calendarContainer = '<div class="property-calendar"></div>';
//        $content .= $calendarContainer;
//    }
//    return $content;
//});

// AJAX handler for Graphina disabled widgets
add_action('wp_ajax_graphina_get_disabled_widgets', function() {
    // Verify nonce
    check_ajax_referer('guesty_api_nonce');

    // Check user permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(['message' => 'Unauthorized access.']);
        return;
    }

    // Example response (replace with actual logic to fetch disabled widgets)
    $disabled_widgets = [
        'widget_1' => 'Widget 1 is disabled',
        'widget_2' => 'Widget 2 is disabled'
    ];

    wp_send_json_success(['disabled_widgets' => $disabled_widgets]);
});


// Add AJAX handler for making a reservation
add_action('wp_ajax_guesty_create_reservation', 'guesty_create_reservation');
add_action('wp_ajax_nopriv_guesty_create_reservation', 'guesty_create_reservation');

function guesty_create_reservation() {
//    guesty_api_log_event('AJAX Fetch Calendar Data: Call initiated.');
    check_ajax_referer('guesty_api_nonce');

    $post_id = isset($_POST['post_id']) ? sanitize_text_field($_POST['post_id']) : '';
    $reservation_data = isset($_POST['reservation_data']) ? $_POST['reservation_data'] : [];

    $listing_id = (string) get_post_meta($post_id, 'guesty_id', true);

    if (empty($listing_id)) {
//        guesty_api_log_event('Error: Listing ID is required for post ID ' . $post_id);
        wp_send_json_error(['message' => __('Listing ID is required', 'guesty-api')]);
        return;
    }

    require_once plugin_dir_path(__FILE__) . 'includes/class-guesty-api.php';
    $api = new Guesty_API();
    $response = $api->create_reservation($listing_id, $reservation_data);

//    guesty_api_log_event('AJAX Create Reservation: Call initiated for listing ID ' . $response);
    if (is_wp_error($response)) {
//        guesty_api_log_event('Error fetching availability: ' . $response->get_error_message());
        wp_send_json_error(['message' => $response->get_error_message()]);
        return;
    }

    if (empty($response)) {
        guesty_api_log_event('No reservation made for property with listing ID ' . $listing_id);
        wp_send_json_error(['message' => __('No reservation made.', 'guesty-api')]);
        return;
    }

    guesty_api_log_event('Successfully reserved property with listing ID ' . $listing_id);
    wp_send_json_success(['data' => $response]);
}

// Add AJAX handler for sending reservation email
add_action('wp_ajax_guesty_send_reservation_email', 'handle_guesty_send_reservation_email');
add_action('wp_ajax_nopriv_guesty_send_reservation_email', 'handle_guesty_send_reservation_email');

function handle_guesty_send_reservation_email() {
    check_ajax_referer('guesty_api_nonce');

    $email_data = isset($_POST['email_data']) ? $_POST['email_data'] : [];
    $to_email = sanitize_email($email_data['contactInfo']);
    $subject = sanitize_text_field($email_data['subject']);
    $site_logo_id = get_theme_mod( 'custom_logo' );
    $site_logo_url = $site_logo_id ? wp_get_attachment_image_src( $site_logo_id, 'full' )[0] : '';

    $to_name = substr($to_email, 0, strpos($to_email, '@'));

    $body = '<html><head><style>' .
        'body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; display: block}' .
        '.email-container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }' .
        '.email-header { background-color: #f4f4f4; padding: 20px; text-align: center; }' .
        '.email-header img { max-width: 150px; margin-bottom: 10px; }' .
        '.email-body { margin-top: 20px; }' .
        '.email-footer { margin-top: 20px; font-size: 12px; color: #888; text-align: center; }' .
        'h2 { color: #005a87; }' . // A shade of blue as primary color
        '.reservation-details { background-color: #f9f9f9; padding: 15px; border-radius: 5px; }' .
        '.reservation-details p { margin: 5px 0; }' .
        '</style></head><body>' .
        '<div class="email-container">' .
        '<div class="email-header">';

    if ( $site_logo_url ) {
        $body .= '<img src="' . esc_url( $site_logo_url ) . '" alt="' . get_bloginfo( 'name' ) . ' Logo">';
    }

    $body .= '<h2 style="color: #005a87; font-size: 24px; margin-bottom: 10px;">Reservation Confirmation</h2>' .
            '</div>' .
            '<div class="email-body" style="margin-top: 20px; font-family: Arial, sans-serif; line-height: 1.6; color: #333;">' .
            '<p style="margin: 0 0 10px;">Dear ' . sanitize_text_field( $to_name ) . ',</p>' .
            '<p style="margin: 0 0 10px;">Thank you for your reservation. Here are your booking details:</p>' .
            '<div class="reservation-details" style="background-color: #f9f9f9; padding: 15px; border-radius: 5px;">' .
            '<p style="margin: 5px 0;">Property: <strong>' . sanitize_text_field( $email_data['postTitle'] ) . '</strong></p>' .
            '<p style="margin: 5px 0;">Check-in: <strong>' . sanitize_text_field( $email_data['checkIn'] ) . '</strong></p>' .
            '<p style="margin: 5px 0;">Check-out: <strong>' . sanitize_text_field( $email_data['checkOut'] ) . '</strong></p>' .
            '<p style="margin: 5px 0;">Guests: <strong>' . sanitize_text_field( $email_data['guests'] ) . '</strong></p>' .
            '</div>' .
            '<p style="margin: 20px 0 0;">We look forward to welcoming you.</p>' .
            '</div>' .
            '<div class="email-footer" style="margin-top: 20px; font-size: 12px; color: #888; text-align: center;">' .
            '<p style="margin: 0;">&copy; ' . date('Y') . ' ' . get_bloginfo( 'name' ) . '. All rights reserved.</p>' .
            '</div>' .
            '</div>' .
            '</body></html>';

    $smtp_settings = get_option('guesty_api_integration_settings');
    $smtp_host = $smtp_settings['guesty_api_integration_smtp_host'] ?? '';
    $smtp_port = $smtp_settings['guesty_api_integration_smtp_port'] ?? '';
    $smtp_username = $smtp_settings['guesty_api_integration_smtp_username'] ?? '';
    $smtp_password = $smtp_settings['guesty_api_integration_smtp_password'] ?? '';

    $from_email = 'no-reply@example.com';
    $from_name = 'Coveted Hospitality Team';

    $headers = "From: $from_name <$from_email>\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";

    require_once plugin_dir_path(__FILE__) . 'includes/class-guesty-email-service.php';

    // Initialize EmailService
    $emailService = new EmailService($smtp_host, $smtp_port, $smtp_username, $smtp_password, $from_email, $from_name);

    // Send email
    $response = $emailService->sendReservationEmail($to_email, $subject, $body);

    guesty_api_log_event('AJAX Reservation Confirmation Email: Call initiated for listing ID ' . $response);
    if (is_wp_error($response)) {
        guesty_api_log_event('Error fetching availability: ' . $response->get_error_message());
        wp_send_json_error(['message' => $response->get_error_message()]);
        return;
    }

    if (empty($response)) {
        guesty_api_log_event('No email sent for property with listing ID ');
        wp_send_json_error(['message' => __('No email sent.', 'guesty-api')]);
        return;
    }


    guesty_api_log_event('Successfully reserved property with listing ID ' . $to_email);
    wp_send_json_success(['message' => 'Email sent successfully']);
}

