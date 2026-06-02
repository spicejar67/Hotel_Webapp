<?php
/**
 * Enqueue carousel CSS and JS assets on the front page only.
 *
 * @action wp_enqueue_scripts
 */
add_action('wp_enqueue_scripts', function() {
    if (is_front_page()) {
        wp_enqueue_style('hotel-carousel', '/wp-content/hotel-carousel.css', [], '1.0');
        wp_enqueue_script('hotel-carousel', '/wp-content/hotel-carousel.js', [], '1.0', true);
    }
});
