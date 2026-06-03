<?php
/**
 * Enqueue site-wide styles and front-page carousel assets.
 *
 * @action wp_enqueue_scripts
 */
add_action("wp_enqueue_scripts", function() {
    wp_enqueue_style("hotel-style", "/wp-content/hotel-style.css", [], "1.4");
    if (is_front_page()) {
        wp_enqueue_style("hotel-carousel", "/wp-content/hotel-carousel.css", [], "1.0");
        wp_enqueue_script("hotel-carousel", "/wp-content/hotel-carousel.js", [], "1.0", true);
    }
}, 20);

/**
 * Hide the default header cart icon via inline CSS.
 *
 * @action wp_head
 */
add_action("wp_head", function() {
    echo "<style>#site-header-cart,.site-header-cart{display:none!important}</style>";
});

/**
 * Exclude Cart and Checkout pages from wp_list_pages output.
 *
 * @filter wp_list_pages_excludes
 */
add_filter("wp_list_pages_excludes", function($exclude) {
    $cart = get_page_by_path("cart");
    $checkout = get_page_by_path("checkout");
    if ($cart) $exclude[] = $cart->ID;
    if ($checkout) $exclude[] = $checkout->ID;
    return $exclude;
});

/**
 * Replace the default Storefront header cart with a custom
 * cart button showing item count and total, aligned right.
 *
 * @action storefront_header
 */
remove_action("storefront_header", "storefront_header_cart", 60);
add_action("storefront_header", function() {
    if (function_exists("WC") && WC()) {
        $count = WC()->cart->get_cart_contents_count();
        $total = WC()->cart->get_cart_total();
    } else {
        $count = 0;
        $total = "$0.00";
    }
    echo "<div class=\"cart-button-below-search\">";
    echo "<a href=\"/cart/\">&#128722; View Cart ";
    echo "<span class=\"cart-count\">$count items - $total</span>";
    echo "</a></div>";
}, 25);


