<?php
/*
Plugin Name: Hotel Payment History
Description: Adds payment/order history tab to My Account
*/

// Rename Orders to Payment History
add_filter("woocommerce_account_menu_items", function($items) {
    $items["orders"] = "Payment History";
    // Remove downloads (not needed for hotel)
    unset($items["downloads"]);
    return $items;
});

// Add payment status badges
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

// Add total paid summary at top of payment history
add_action("woocommerce_before_account_orders", function($has_orders) {
    if (!$has_orders) return;
    $customer_orders = wc_get_orders(array("customer_id" => get_current_user_id(), "limit" => -1));
    $total = 0; $count = 0;
    foreach ($customer_orders as $o) {
        if (in_array($o->get_status(), ["completed", "processing"])) {
            $total += $o->get_total();
            $count++;
        }
    }
    echo "<div style=\"background:#e8f5e9;padding:15px 20px;border-radius:8px;margin-bottom:20px;\">";
    echo "<strong style=\"color:#2e7d32;\">$count orders paid</strong> &mdash; Total: <strong>\$" . number_format($total, 2) . "</strong>";
    echo "</div>";
});
