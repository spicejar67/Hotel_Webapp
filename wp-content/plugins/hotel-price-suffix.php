<?php
/*
Plugin Name: Hotel Price Suffix
Description: Adds /month to product prices
*/
add_filter("woocommerce_get_price_html", function($price, $product) {
    return $price . " <span class=\"price-suffix\">/month</span>";
}, 10, 2);
