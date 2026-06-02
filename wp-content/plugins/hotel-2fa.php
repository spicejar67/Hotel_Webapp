<?php
/*
Plugin Name: Hotel Registration
Description: Simple sign-up with immediate account creation
*/

/**
 * Output a custom sign-up form inline on the WooCommerce login page.
 * Replaces the default registration flow with immediate account creation
 * (no email verification step).
 *
 * @action woocommerce_before_customer_login_form
 */
add_action("woocommerce_before_customer_login_form", function() {
    ?>
    <div class="hotel-signup-section" style="margin-top:40px;padding:30px;background:#f8f9fa;border-radius:8px;">
        <h2 style="margin-bottom:20px;color:#1565C0;">Create an Account</h2>
        <form method="post" class="hotel-signup-form">
            <?php wp_nonce_field("hotel_signup", "signup_nonce"); ?>
            <p class="woocommerce-form-row">
                <label for="signup_email" style="font-weight:600;">Email address <span class="required">*</span></label>
                <input type="email" class="woocommerce-Input" name="signup_email" id="signup_email" required 
                       style="width:100%;padding:10px;border:1px solid #ddd;border-radius:4px;" />
            </p>
            <p class="woocommerce-form-row">
                <label for="signup_password" style="font-weight:600;">Password <span class="required">*</span></label>
                <input type="password" class="woocommerce-Input" name="signup_password" id="signup_password" required 
                       style="width:100%;padding:10px;border:1px solid #ddd;border-radius:4px;" minlength="8" />
            </p>
            <p>
                <button type="submit" name="hotel_signup" class="woocommerce-Button button" 
                        style="background:linear-gradient(135deg,#1565C0,#1976D2);color:#fff;padding:12px 24px;border-radius:6px;border:none;width:100%;font-size:15px;">
                    Create Account
                </button>
            </p>
        </form>
    </div>
    <?php
});

/**
 * Process the custom sign-up form submission.
 * Validates input, creates a WordPress user account immediately,
 * logs them in, and redirects to My Account.
 *
 * @action init
 */
add_action("init", function() {
    if (!isset($_POST["hotel_signup"]) || !isset($_POST["signup_nonce"])) return;
    if (!wp_verify_nonce($_POST["signup_nonce"], "hotel_signup")) return;

    $email = sanitize_email($_POST["signup_email"]);
    $password = $_POST["signup_password"];

    // Validate email and password
    if (!is_email($email)) { wc_add_notice("Invalid email.", "error"); return; }
    if (strlen($password) < 8) { wc_add_notice("Password must be at least 8 characters.", "error"); return; }
    if (email_exists($email)) { wc_add_notice("An account with this email already exists. Please log in.", "error"); return; }

    // Generate username from email prefix, append random digits if taken
    $username = sanitize_user(current(explode("@", $email)), true);
    if (username_exists($username)) $username .= rand(100, 999);

    $user_id = wp_insert_user(array(
        "user_login" => $username,
        "user_email" => $email,
        "user_pass" => $password,
        "role" => "customer",
    ));

    if (is_wp_error($user_id)) {
        wc_add_notice("Error: " . $user_id->get_error_message(), "error");
        return;
    }

    // Auto-login the new user
    wp_set_current_user($user_id);
    wp_set_auth_cookie($user_id);
    wc_add_notice("Account created! Welcome to Hotel Metrodata.", "success");
    wp_safe_redirect(wc_get_page_permalink("myaccount"));
    exit;
});

/**
 * Hide the default WooCommerce registration form on the My Account page
 * since this plugin provides its own custom sign-up UI.
 *
 * @action wp_head
 */
add_action("wp_head", function() {
    if (is_account_page()) echo "<style>.woocommerce-form-register{display:none!important}</style>";
});
