<?php
/*
Plugin Name: Hotel Payment History
Description: Adds payment/order history tab to My Account
*/

/**
 * Rename "Orders" tab to "Payment History" in My Account menu
 * and remove the Downloads tab (unused by hotel).
 *
 * @filter woocommerce_account_menu_items
 */
add_filter("woocommerce_account_menu_items", function($items) {
    $items["orders"] = "Payment History";
    unset($items["downloads"]); // Not needed for hotel rooms
    return $items;
});

/**
 * Display color-coded payment status badges in the order history table.
 *
 * @action woocommerce_my_account_my_orders_column_order-status
 */
add_action("woocommerce_my_account_my_orders_column_order-status", function($order) {
    $status = $order->get_status();
    $badges = [
        "completed" => ["#4caf50", "Paid"],
        "processing" => ["#2196f3", "Processing"],
        "pending" => ["#ff9800", "Pending"],
        "on-hold" => ["#9e9e9e", "On Hold"],
        "cancelled" => ["#f44336", "Cancelled"],
        "refunded" => ["#795548", "Refunded"],
    ];
    $b = $badges[$status] ?? ["#999", ucfirst($status)];
    echo "<span style=\"display:inline-block;padding:3px 10px;background:{$b[0]};color:#fff;border-radius:4px;font-size:11px;\">{$b[1]}</span>";
});

/**
 * Show a summary banner above the Payment History table with the
 * count and total dollar amount of completed/processing orders.
 *
 * @action woocommerce_before_account_orders
 */
add_action("woocommerce_before_account_orders", function($has_orders) {
    if (!$has_orders) return;
    $customer_orders = wc_get_orders(array("customer_id" => get_current_user_id(), "limit" => -1));
    $total = 0; $count = 0;
    foreach ($customer_orders as $o) {
        // Only count orders that have been paid or are being processed
        if (in_array($o->get_status(), ["completed", "processing"])) {
            $total += $o->get_total();
            $count++;
        }
    }
    echo "<div style=\"background:#e8f5e9;padding:15px 20px;border-radius:8px;margin-bottom:20px;\">";
    echo "<strong style=\"color:#2e7d32;\">$count orders paid</strong> &mdash; Total: <strong>\$" . number_format($total, 2) . "</strong>";
    echo "</div>";
});
