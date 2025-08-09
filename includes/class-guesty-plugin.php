<?php
class Guesty_Plugin {
    private static $instance = null;
    public static function instance() {
        if ( self::$instance === null ) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    private function __construct() {
        $this->includes();
        add_action( 'admin_menu', [ $this, 'admin_menu' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'admin_assets' ] );
        add_action( 'wp_enqueue_scripts', [ $this, 'frontend_assets' ] );
        add_action( 'init', [ $this, 'register_shortcodes' ] );
    }
    private function includes() {
        require_once plugin_dir_path( __FILE__ ) . 'class-guesty-api.php';
        require_once plugin_dir_path( __FILE__ ) . '../admin/class-guesty-admin.php';
    }
    public function admin_menu() {
        Guesty_Admin::register_menu();
    }
    public function admin_assets() {
        wp_enqueue_style( 'guesty-admin', plugins_url( '../assets/css/admin.css', __FILE__ ) );
    }
    public function frontend_assets() {
        wp_enqueue_style( 'guesty-frontend', plugins_url( '../assets/css/frontend.css', __FILE__ ) );
        wp_enqueue_style( 'guesty-custom-calendar', plugins_url( '../assets/css/custom-calendar-styles.css', __FILE__ ) );
    }
    public function register_shortcodes() {
        add_shortcode( 'guesty_search', [ 'Guesty_Shortcodes', 'search_form' ] );
        add_shortcode( 'guesty_properties', [ 'Guesty_Shortcodes', 'properties_list' ] );
        add_shortcode( 'guesty_property', [ 'Guesty_Shortcodes', 'single_property' ] );
        add_shortcode( 'guesty_booking', [ 'Guesty_Shortcodes', 'booking_form' ] );
        add_shortcode('guesty_calendar', ['Guesty_Shortcodes', 'availability_calendar_shortcode']);
        add_shortcode('guesty_booking_calendar', ['Guesty_Shortcodes', 'booking_calendar_shortcode']);

    }
}

