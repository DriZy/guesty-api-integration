<?php
class Guesty_Shortcodes {
    public static function search_form( $atts ) {
        ob_start();
        include plugin_dir_path( __FILE__ ) . '../templates/search-form.php';
        return ob_get_clean();
    }

    private static function log_plugin_event($message) {
        $log_file = plugin_dir_path(__FILE__) . '../logs/plugin_events.log';
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
    }

    public static function properties_list( $atts ) {
        $atts = shortcode_atts([
            'view' => 'list',
        ], $atts);
        ob_start();
        $api = new Guesty_API();
        $properties = $api->get_properties();
        $view = $atts['view'] === 'grid' ? 'grid' : 'list';
        include plugin_dir_path( __FILE__ ) . "../templates/properties-{$view}.php";
        return ob_get_clean();
    }
    public static function single_property( $atts ) {
        $atts = shortcode_atts([
            'id' => ''
        ], $atts);
        if ( empty( $atts['id'] ) ) return '';
        ob_start();
        $api = new Guesty_API();
        $property = $api->get_property( $atts['id'] );
        include plugin_dir_path( __FILE__ ) . '../templates/property-single.php';
        return ob_get_clean();
    }
    public static function booking_form( $atts ) {
        $atts = shortcode_atts([
            'id' => ''
        ], $atts);
        if ( empty( $atts['id'] ) ) return '';
        ob_start();
        include plugin_dir_path( __FILE__ ) . '../templates/booking-form.php';
        return ob_get_clean();
    }
    public static function availability_calendar_shortcode($atts) {
        $atts = shortcode_atts([
            'listing_id' => '',
            'months' => 3, // Show 3 months by default
            'show_pricing' => true
        ], $atts, 'guesty_calendar');

        // Ensure listing_id is a string to avoid null issues
        $atts['listing_id'] = (string) $atts['listing_id'];

        if (empty($atts['listing_id'])) {
            // Try to get listing ID from global post if available
            global $post;
            $atts['listing_id'] = (string) get_post_meta($post->ID, 'guesty_listing_id', true);

            if (empty($atts['listing_id'])) {
                return '<div class="guesty-error">' . esc_html__('Listing ID is required', 'guesty-api') . '</div>';
            }
        }

        // Set start date to the first day of the current month
        $start_date = date('Y-m-01');
        $end_date = date('Y-m-d', strtotime("+{$atts['months']} months"));

        // Log function call with attributes
        $api = new Guesty_API();
        $availability = $api->get_availability($atts['listing_id'], $start_date, $end_date);

        // Log the API call
        if (is_wp_error($availability)) {
            return '<div class="guesty-error">' . esc_html($availability->get_error_message()) . '</div>';
        } else {
            $api->log_plugin_event('Fetching availability for listing ID: ' . $atts['listing_id'] . ' succeeded. Response: ' . json_encode($availability));
        }

        // Ensure availability data is properly sanitized
        if (empty($availability) || !is_array($availability)) {
            return '<div class="guesty-error">' . esc_html__('No availability data found.', 'guesty-api') . '</div>';
        }

        return '<pre>' . esc_html(json_encode($availability, JSON_PRETTY_PRINT)) . '</pre>';
    }
}
