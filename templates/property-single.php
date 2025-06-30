<?php
if ( !empty($property['id']) ) {
    echo '<div class="guesty-property-single">';
    echo '<h2>' . esc_html( $property['title'] ?? $property['id'] ) . '</h2>';
    if ( !empty($property['description']) ) {
        echo '<p>' . esc_html( $property['description'] ) . '</p>';
    }
    // Add more property details as needed
    echo do_shortcode('[guesty_booking id="' . esc_attr($property['id']) . '"]');
    echo '</div>';
} else {
    echo '<p>' . esc_html__( 'Property not found.', 'guesty-api-integration' ) . '</p>';
}

