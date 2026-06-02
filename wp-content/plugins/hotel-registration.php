<?php
/*
Plugin Name: Hotel Registration
Description: Sign-up with email verification via direct SMTP
*/

/**
 * Send an email via direct SMTP (Gmail) using PHPMailer.
 *
 * @param string $to      Recipient email address.
 * @param string $subject Email subject line.
 * @param string $body    Plain-text email body.
 * @return bool True on success, false on failure.
 */
function hotel_send_email($to, $subject, $body) {
    require_once ABSPATH . WPINC . "/PHPMailer/PHPMailer.php";
    require_once ABSPATH . WPINC . "/PHPMailer/SMTP.php";
    require_once ABSPATH . WPINC . "/PHPMailer/Exception.php";
    
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    try {
        // Configure Gmail SMTP
        $mail->isSMTP();
        $mail->Host = "smtp.gmail.com";
        $mail->Port = 587;
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->SMTPAuth = true;
        $mail->Username = "temp9541810@gmail.com";
        $mail->Password = "yghd tirw yfko kaxj";
        $mail->setFrom("temp9541810@gmail.com", "Hotel Metrodata");
        $mail->addAddress($to);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("SMTP error: " . $e->getMessage());
        return false;
    }
}

/**
 * Render the custom sign-up form on the WooCommerce login page.
 * Collects email and password, then sends a verification code.
 *
 * @action woocommerce_before_customer_login_form
 */
add_action("woocommerce_before_customer_login_form", function() {
    if (isset($_GET["action"]) && $_GET["action"] === "verify") return;
    ?>
    <div class="hotel-signup-section" style="margin-top:40px;padding:30px;background:#f8f9fa;border-radius:8px;">
        <h2 style="margin-bottom:20px;color:#1565C0;">Create an Account</h2>
        <form method="post">
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
                    Send Verification Code
                </button>
            </p>
        </form>
    </div>
    <?php
});

/**
 * Process the sign-up form: validate input, generate a 6-digit
 * verification code, store it in a custom DB table, send the code
 * via email, then redirect to the verification form.
 *
 * @action init
 */
add_action("init", function() {
    if (!isset($_POST["hotel_signup"]) || !isset($_POST["signup_nonce"])) return;
    if (!wp_verify_nonce($_POST["signup_nonce"], "hotel_signup")) return;

    $email = sanitize_email($_POST["signup_email"]);
    $password = $_POST["signup_password"];

    if (!is_email($email)) { wc_add_notice("Invalid email.", "error"); return; }
    if (strlen($password) < 8) { wc_add_notice("Password must be at least 8 characters.", "error"); return; }
    if (email_exists($email)) { wc_add_notice("Account already exists. Please log in.", "error"); return; }

    // Ensure the signup codes table exists
    global $wpdb; $table = $wpdb->prefix . "signup_codes";
    $wpdb->query("CREATE TABLE IF NOT EXISTS $table (
        id INT AUTO_INCREMENT PRIMARY KEY, email VARCHAR(255) NOT NULL,
        code VARCHAR(6) NOT NULL, expires_at DATETIME NOT NULL,
        verified TINYINT DEFAULT 0, created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    $wpdb->delete($table, array("email" => $email));

    $code = str_pad(rand(0, 999999), 6, "0", STR_PAD_LEFT);
    $wpdb->insert($table, array("email" => $email, "code" => $code,
        "expires_at" => gmdate("Y-m-d H:i:s", strtotime("+10 minutes"))));
    // Store password temporarily so it survives the redirect
    set_transient("signup_pw_" . md5($email), $password, 600);

    $sent = hotel_send_email($email, "Hotel Metrodata - Verification Code",
        "Welcome to Hotel Metrodata!\n\nYour verification code is: $code\n\nExpires in 10 minutes.");

    if ($sent) {
        wc_add_notice("Verification code sent to $email!", "success");
    } else {
        wc_add_notice("Your code is: <strong>$code</strong> (email failed).", "success");
    }

    wp_safe_redirect(add_query_arg(array("action" => "verify", "email" => urlencode($email)),
        wc_get_page_permalink("myaccount")));
    exit;
});

add_action("woocommerce_before_customer_login_form", function() {
    if (!isset($_GET["action"]) || $_GET["action"] !== "verify") return;
    $email = sanitize_email($_GET["email"]);
    ?>
    <div class="hotel-verify-section" style="margin-top:40px;padding:30px;background:#f8f9fa;border-radius:8px;">
        <h2 style="margin-bottom:10px;color:#1565C0;">Verify Your Email</h2>
        <p style="color:#666;margin-bottom:20px;">Enter the 6-digit code sent to <strong><?php echo esc_html($email); ?></strong></p>
        <form method="post">
            <?php wp_nonce_field("hotel_verify", "verify_nonce"); ?>
            <input type="hidden" name="verify_email" value="<?php echo esc_attr($email); ?>" />
            <p>
                <input type="text" name="verify_code" id="verify_code" required maxlength="6" pattern="[0-9]{6}"
                       style="width:100%;padding:12px;border:1px solid #ddd;border-radius:4px;font-size:20px;text-align:center;letter-spacing:8px;" 
                       placeholder="000000" inputmode="numeric" />
            </p>
            <p>
                <button type="submit" name="hotel_verify" class="woocommerce-Button button" 
                        style="background:linear-gradient(135deg,#1565C0,#1976D2);color:#fff;padding:12px 24px;border-radius:6px;border:none;width:100%;font-size:15px;">
                    Verify & Create Account
                </button>
            </p>
            <p style="text-align:center;margin-top:15px;">
                <a href="<?php echo esc_url(wc_get_page_permalink("myaccount")); ?>">Back to login</a>
            </p>
        </form>
    </div>
    <?php
});

/**
 * Process the verification code submission: check the code against
 * the database, retrieve the password from the transient, create
 * the user account, and auto-login.
 *
 * @action init
 */
add_action("init", function() {
    if (!isset($_POST["hotel_verify"]) || !isset($_POST["verify_nonce"])) return;
    if (!wp_verify_nonce($_POST["verify_nonce"], "hotel_verify")) return;

    $email = sanitize_email($_POST["verify_email"]);
    $code = sanitize_text_field($_POST["verify_code"]);

    global $wpdb; $table = $wpdb->prefix . "signup_codes";
    $row = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table WHERE email = %s AND code = %s AND verified = 0 AND expires_at > UTC_TIMESTAMP()",
        $email, $code
    ));
    if (!$row) { wc_add_notice("Invalid or expired code.", "error"); return; }

    $password = get_transient("signup_pw_" . md5($email));
    if (!$password) { wc_add_notice("Session expired. Start over.", "error"); return; }

    $username = sanitize_user(current(explode("@", $email)), true);
    if (username_exists($username)) $username .= rand(100, 999); // Ensure uniqueness

    $uid = wp_insert_user(array("user_login" => $username, "user_email" => $email,
        "user_pass" => $password, "role" => "customer"));
    if (is_wp_error($uid)) { wc_add_notice("Error: " . $uid->get_error_message(), "error"); return; }

    $wpdb->update($table, array("verified" => 1), array("id" => $row->id));
    delete_transient("signup_pw_" . md5($email)); // Clean up password transient

    // Log the user in and redirect to My Account
    wp_set_current_user($uid); wp_set_auth_cookie($uid);
    wc_add_notice("Account created! Welcome to Hotel Metrodata.", "success");
    wp_safe_redirect(wc_get_page_permalink("myaccount"));
    exit;
});

/**
 * Hide the default WooCommerce registration form on the account page
 * since this plugin provides its own custom sign-up and verification UI.
 *
 * @action wp_head
 */
add_action("wp_head", function() {
    if (is_account_page()) echo "<style>.woocommerce-form-register{display:none!important}</style>";
});
