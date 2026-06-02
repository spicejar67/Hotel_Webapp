<?php
/*
Plugin Name: Hotel Cart & Checkout UI
Description: Styled cart, checkout restrictions, auto-logout after 5 min
*/

// Set auth cookie to expire in 5 minutes (300 seconds)
add_filter("auth_cookie_expiration", function() { return 300; });

// Auto-logout check on every page load
add_action("init", function() {
    if (!is_user_logged_in()) return;
    $last = get_user_meta(get_current_user_id(), "last_activity", true);
    $now = time();
    if ($last && ($now - $last) > 300) {
        wp_clear_auth_cookie();
        wp_logout();
        wp_safe_redirect(home_url("/my-account/?timeout=1"));
        exit;
    }
    update_user_meta(get_current_user_id(), "last_activity", $now);
});

// JS heartbeat: checks inactivity every 30s, forces logout page if idle
add_action("wp_footer", function() {
    if (!is_user_logged_in()) return;
    ?>
    <script>
    var idleTimer;
    function resetTimer() {
        clearTimeout(idleTimer);
        idleTimer = setTimeout(function() {
            window.location.href = "/wp-login.php?action=logout&redirect_to=<?php echo urlencode(home_url("/my-account/?timeout=1")); ?>&_wpnonce=<?php echo wp_create_nonce("log-out"); ?>";
        }, 300000); // 5 min
    }
    ["mousemove","keydown","scroll","click","touchstart"].forEach(function(e) {
        document.addEventListener(e, resetTimer, false);
    });
    resetTimer();
    </script>
    <?php
});

// Timeout message
add_action("woocommerce_before_customer_login_form", function() {
    if (isset($_GET["timeout"])) {
        wc_add_notice("Logged out after 5 minutes of inactivity for security.", "notice");
    }
});

// Restrict checkout to logged-in
add_action("template_redirect", function() {
    if (is_checkout() && !is_user_logged_in()) {
        wc_add_notice("Please log in or create an account to checkout.", "notice");
        wp_safe_redirect(wc_get_page_permalink("myaccount"));
        exit;
    }
});

// Login prompt on cart
add_action("woocommerce_before_cart", function() {
    if (!is_user_logged_in()) {
        echo "<div style=\"background:#fff3cd;padding:15px 20px;border-radius:8px;margin-bottom:20px;border:1px solid #ffc107;\">";
        echo "<strong style=\"color:#856404;\">Pro tip:</strong> ";
        echo "<a href=\"" . wc_get_page_permalink("myaccount") . "\" style=\"color:#1565C0;\">Create an account</a> for faster checkout!";
        echo "</div>";
    }
});

// Cart CSS
add_action("wp_enqueue_scripts", function() {
    if (is_cart() || is_checkout()) {
        wp_enqueue_style("hotel-cart-ui", "/wp-content/hotel-cart-ui.css", [], "1.0");
    }
});
