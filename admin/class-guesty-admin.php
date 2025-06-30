<?php
class Guesty_Admin {
    public static function register_menu() {
        add_menu_page(
            __( 'Guesty API', 'guesty-api-integration' ),
            __( 'Guesty API', 'guesty-api-integration' ),
            'manage_options',
            'guesty-api',
            [ __CLASS__, 'settings_page' ],
            'dashicons-admin-generic',
            80
        );
    }

    public static function settings_page() {
        $active_tab = $_GET['tab'] ?? 'api';
        // Display connection test notice if present
        if ( isset( $_GET['guesty_api_test'] ) ) {
            if ( $_GET['guesty_api_test'] === 'success' ) {
                echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__( 'Connection successful!', 'guesty-api-integration' ) . '</p></div>';
            } elseif ( $_GET['guesty_api_test'] === 'fail' && !empty($_GET['guesty_api_error']) ) {
                echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Connection failed: ', 'guesty-api-integration' ) . esc_html( sanitize_text_field( $_GET['guesty_api_error'] ) ) . '</p></div>';
            } elseif ( $_GET['guesty_api_test'] === 'fail' ) {
                echo '<div class="notice notice-error is-dismissible"><p>' . esc_html__( 'Connection failed.', 'guesty-api-integration' ) . '</p></div>';
            }
        }
        echo '<div class="wrap"><h1>' . esc_html__( 'Guesty API Settings', 'guesty-api-integration' ) . '</h1>';
        echo '<nav class="nav-tab-wrapper">';
        $tabs = [
            'api' => __( 'API Credentials', 'guesty-api-integration' ),
            'caching' => __( 'Caching', 'guesty-api-integration' ),
            'webhooks' => __( 'Webhooks', 'guesty-api-integration' ),
            'shortcodes' => __( 'Shortcodes', 'guesty-api-integration' ),
        ];
        foreach ( $tabs as $tab => $label ) {
            $class = ( $active_tab === $tab ) ? ' nav-tab-active' : '';
            echo '<a href="?page=guesty-api&tab=' . esc_attr( $tab ) . '" class="nav-tab' . $class . '">' . esc_html( $label ) . '</a>';
        }
        echo '</nav>';
        if ( $active_tab === 'shortcodes' ) {
            self::shortcodes_tab();
        } else {
            echo '<form method="post" action="options.php">';
            settings_fields( 'guesty_api_' . $active_tab );
            do_settings_sections( 'guesty_api_' . $active_tab );
            if ( $active_tab === 'api' ) {
                echo '<button type="button" id="guesty-test-connection" class="button">' . esc_html__( 'Test Connection', 'guesty-api-integration' ) . '</button>';
                echo '<div id="guesty-test-connection-result"></div>';

                echo '<button type="button" id="fetch-properties-button" class="button">' . esc_html__( 'Fetch and Populate Properties', 'guesty-api-integration' ) . '</button>';
                echo '<div id="guesty-properties-spinner"></div>';
            }
            submit_button();
            echo '</form>';
        }
        echo '</div>';
    }
    public static function shortcodes_tab() {
        echo '<h2>' . esc_html__( 'Available Shortcodes', 'guesty-api-integration' ) . '</h2>';
        echo '<ul style="margin-top:1em">';
        echo '<li><code>[guesty_search]</code> - ' . esc_html__( 'Displays a property search form.', 'guesty-api-integration' ) . '</li>';
        echo '<li><code>[guesty_properties view="list|grid"]</code> - ' . esc_html__( 'Displays a list or grid of properties.', 'guesty-api-integration' ) . '</li>';
        echo '<li><code>[guesty_property id="PROPERTY_ID"]</code> - ' . esc_html__( 'Displays a single property view.', 'guesty-api-integration' ) . '</li>';
        echo '<li><code>[guesty_booking id="PROPERTY_ID"]</code> - ' . esc_html__( 'Displays a booking form for a property.', 'guesty-api-integration' ) . '</li>';
        echo '</ul>';
    }
}

add_action( 'admin_init', function() {
    // API Credentials
    register_setting( 'guesty_api_api', 'guesty_api_client_id' );
    register_setting( 'guesty_api_api', 'guesty_api_client_secret' );
    add_settings_section( 'guesty_api_api_section', '', null, 'guesty_api_api' );
    add_settings_field( 'guesty_api_client_id', __( 'Client ID', 'guesty-api-integration' ), function() {
        echo '<input type="text" name="guesty_api_client_id" value="' . esc_attr( get_option( 'guesty_api_client_id' ) ) . '" class="regular-text">';
    }, 'guesty_api_api', 'guesty_api_api_section' );
    add_settings_field( 'guesty_api_client_secret', __( 'Client Secret', 'guesty-api-integration' ), function() {
        echo '<input type="password" name="guesty_api_client_secret" value="' . esc_attr( get_option( 'guesty_api_client_secret' ) ) . '" class="regular-text">';
    }, 'guesty_api_api', 'guesty_api_api_section' );
    // Caching
    register_setting( 'guesty_api_caching', 'guesty_api_cache_ttl' );
    add_settings_section( 'guesty_api_caching_section', '', null, 'guesty_api_caching' );
    add_settings_field( 'guesty_api_cache_ttl', __( 'Cache TTL (seconds)', 'guesty-api-integration' ), function() {
        echo '<input type="number" name="guesty_api_cache_ttl" value="' . esc_attr( get_option( 'guesty_api_cache_ttl', 300 ) ) . '" class="small-text">';
    }, 'guesty_api_caching', 'guesty_api_caching_section' );
    // Webhooks
    register_setting( 'guesty_api_webhooks', 'guesty_api_webhook_url' );
    add_settings_section( 'guesty_api_webhooks_section', '', null, 'guesty_api_webhooks' );
    add_settings_field( 'guesty_api_webhook_url', __( 'Webhook URL', 'guesty-api-integration' ), function() {
        echo '<input type="url" name="guesty_api_webhook_url" value="' . esc_attr( get_option( 'guesty_api_webhook_url' ) ) . '" class="regular-text">';
    }, 'guesty_api_webhooks', 'guesty_api_webhooks_section' );
});

