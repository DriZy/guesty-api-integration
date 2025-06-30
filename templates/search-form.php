<?php
// Simple search form for Guesty properties
echo '<form class="guesty-search-form" method="get">';
echo '<input type="text" name="guesty_search" placeholder="' . esc_attr__( 'Search properties...', 'guesty-api-integration' ) . '">';
echo '<button type="submit">' . esc_html__( 'Search', 'guesty-api-integration' ) . '</button>';
echo '</form>';

