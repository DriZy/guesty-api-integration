<?php
echo '<div class="guesty-properties-grid">';
if ( !empty($properties['results']) ) {
    foreach ( $properties['results'] as $property ) {
        echo '<div class="guesty-property-card">';
        echo '<h3>' . esc_html( $property['title'] ?? $property['id'] ) . '</h3>';
        echo '<a href="' . esc_url( add_query_arg( 'guesty_property', $property['id'] ) ) . '">' . esc_html__( 'View Details', 'guesty-api-integration' ) . '</a>';
        echo '</div>';
    }
} else {
    echo '<p>' . esc_html__( 'No properties found.', 'guesty-api-integration' ) . '</p>';
}
echo '</div>';

