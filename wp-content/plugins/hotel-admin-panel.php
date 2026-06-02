<?php
/*
Plugin Name: Hotel Admin Panel
Description: Admin UI for managing users, rooms, prices, images, and deletion
*/

// Exclude Hotel Admin page (ID 51) from wp_list_pages
add_filter("wp_list_pages_excludes", function($exclude) {
    $exclude[] = 51;
    return $exclude;
});

// Register blocked role
add_action("init", function() {
    if (!get_role("blocked")) {
        add_role("blocked", "Blocked", array("read" => false));
    }
});

// Blocked users cannot log in
add_filter("authenticate", function($user, $username, $password) {
    if ($user instanceof WP_User && in_array("blocked", $user->roles)) {
        return new WP_Error("blocked", "Your account has been blocked.");
    }
    return $user;
}, 99, 3);

// Auto-approve all product reviews
add_filter("pre_comment_approved", function($approved, $commentdata) {
    if (isset($commentdata["comment_type"]) && $commentdata["comment_type"] === "review") {
        return 1;
    }
    return $approved;
}, 99, 2);

// Show rooms available count on product cards
add_action("woocommerce_after_shop_loop_item_title", function() {
    $available = get_post_meta(get_the_ID(), "_rooms_available", true);
    if ($available) {
        echo '<div class="rooms-available" style="font-size:13px;color:#2e7d32;margin-top:4px;font-weight:600;">' . esc_html($available) . ' rooms available</div>';
    }
}, 105);



// Enqueue jQuery UI datepicker for calendar
add_action("wp_enqueue_scripts", function() {
    if (is_product()) {
        wp_enqueue_style("jquery-ui-css", "https://code.jquery.com/ui/1.14.1/themes/smoothness/jquery-ui.css", array(), "1.14.1");
        wp_enqueue_script("jquery-ui-datepicker");
    }
}, 40);

// Hidden date inputs inside add-to-cart form (synced from picker)
add_action("woocommerce_before_add_to_cart_button", function() {
    ?>
    <input type="hidden" id="booking-checkin-hidden" name="booking_checkin" value="">
    <input type="hidden" id="booking-checkout-hidden" name="booking_checkout" value="">
    <?php
}, 1);

// Date picker on single product page (left column, below map)
add_action("woocommerce_product_thumbnails", function() {
    ?>
    <div style="margin:12px 0 200px 0;padding:12px;background:#f8f9fa;border-radius:8px;border:1px solid #e0e0e0;clear:both;">
        <label style="font-weight:600;font-size:14px;display:block;margin-bottom:10px;">Select your stay dates</label>
        <div style="display:flex;gap:12px;flex-wrap:wrap;">
            <div style="flex:1;min-width:140px;">
                <label style="font-size:12px;color:#666;display:block;margin-bottom:4px;">Check-in</label>
                <input type="text" id="booking-checkin" name="booking_checkin" readonly
                    style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;font-size:13px;background:#fff;cursor:pointer;">
            </div>
            <div style="flex:1;min-width:140px;">
                <label style="font-size:12px;color:#666;display:block;margin-bottom:4px;">Check-out</label>
                <input type="text" id="booking-checkout" name="booking_checkout" readonly
                    style="width:100%;padding:8px;border:1px solid #ddd;border-radius:4px;font-size:13px;background:#fff;cursor:pointer;">
            </div>
        </div>
    </div>
    <script>
    jQuery(function($) {
        // Sync date picker values to hidden form inputs
        function syncDates() {
            $("#booking-checkin-hidden").val($("#booking-checkin").val());
            $("#booking-checkout-hidden").val($("#booking-checkout").val());
        }
        // Override add-to-cart to sync before submit
        $("form.cart").on("submit", function() { syncDates(); });
        
        $("<style>.ui-datepicker { font-size: 12px; } #ui-datepicker-div { position: absolute !important; }<\/style>").appendTo("head");
        var today = new Date();
        var checkin = $("#booking-checkin").datepicker({
            dateFormat: "yy-mm-dd",
            minDate: 0,
            changeMonth: true,
            changeYear: true,
            numberOfMonths: 1,
            onSelect: function(selectedDate) { syncDates();
                var nextDay = new Date(selectedDate);
                nextDay.setDate(nextDay.getDate() + 1);
                $("#booking-checkout").datepicker("option", "minDate", nextDay);
                if ($("#booking-checkout").val() && $("#booking-checkout").datepicker("getDate") <= new Date(selectedDate)) {
                    $("#booking-checkout").val("");
                }
            }
        });
        $("#booking-checkout").datepicker({
            dateFormat: "yy-mm-dd",
            minDate: 1,
            changeMonth: true,
            changeYear: true,
            numberOfMonths: 1,
        });
    });
    </script>
    <?php
}, 105);

// Store dates in cart item data
add_filter("woocommerce_add_cart_item_data", function($cart_item_data, $product_id) {
    if (isset($_POST["booking_checkin"]) && isset($_POST["booking_checkout"])) {
        $cart_item_data["booking_checkin"] = sanitize_text_field($_POST["booking_checkin"]);
        $cart_item_data["booking_checkout"] = sanitize_text_field($_POST["booking_checkout"]);
        $cart_item_data["unique_key"] = md5(microtime() . rand());
    }
    return $cart_item_data;
}, 10, 2);

// Persist dates in cart session
add_filter("woocommerce_get_cart_item_from_session", function($cart_item, $values) {
    if (isset($values["booking_checkin"])) $cart_item["booking_checkin"] = $values["booking_checkin"];
    if (isset($values["booking_checkout"])) $cart_item["booking_checkout"] = $values["booking_checkout"];
    return $cart_item;
}, 10, 2);

// Show dates on cart and checkout
add_filter("woocommerce_get_item_data", function($item_data, $cart_item) {
    if (isset($cart_item["booking_checkin"])) {
        $item_data[] = array(
            "name" => "Check-in",
            "value" => date("M j, Y", strtotime($cart_item["booking_checkin"])),
        );
    }
    if (isset($cart_item["booking_checkout"])) {
        $item_data[] = array(
            "name" => "Check-out",
            "value" => date("M j, Y", strtotime($cart_item["booking_checkout"])),
        );
    }
    return $item_data;
}, 10, 2);

// Save dates to order items on checkout
add_action("woocommerce_checkout_create_order_line_item", function($item, $cart_item_key, $values, $order) {
    if (isset($values["booking_checkin"])) {
        $item->add_meta_data("Check-in", date("M j, Y", strtotime($values["booking_checkin"])), true);
    }
    if (isset($values["booking_checkout"])) {
        $item->add_meta_data("Check-out", date("M j, Y", strtotime($values["booking_checkout"])), true);
    }
}, 10, 4);

// Leaflet minimap on single product pages
add_action("wp_enqueue_scripts", function() {
    if (is_product()) {
        wp_enqueue_style("leaflet-css", "https://unpkg.com/leaflet@1.9.4/dist/leaflet.css", array(), "1.9.4");
        wp_enqueue_script("leaflet-js", "https://unpkg.com/leaflet@1.9.4/dist/leaflet.js", array(), "1.9.4", true);
    }
}, 30);

add_action("woocommerce_product_thumbnails", function() {
    $lat = get_post_meta(get_the_ID(), "_room_lat", true);
    $lng = get_post_meta(get_the_ID(), "_room_lng", true);
    if (!$lat || !$lng) return;
    ?>
    <div id="room-minimap" style="height:125px;width:100%;border-radius:8px;margin:12px 0;border:1px solid #ddd;"></div>
    <style>#room-minimap .leaflet-control-attribution { font-size:9px; }</style>
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        var map = L.map("room-minimap", { zoomControl: true, attributionControl: false }).setView([<?php echo floatval($lat); ?>, <?php echo floatval($lng); ?>], 13);
        L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", { maxZoom: 18 }).addTo(map);
        L.marker([<?php echo floatval($lat); ?>, <?php echo floatval($lng); ?>]).addTo(map);
    });
    </script>
    <?php
}, 100);

// Show rooms available count on single product pages
add_action("woocommerce_single_product_summary", function() {
    $available = get_post_meta(get_the_ID(), "_rooms_available", true);
    if ($available) {
        echo '<div class="rooms-available" style="font-size:15px;color:#2e7d32;margin:8px 0;font-weight:600;padding:6px 12px;background:#e8f5e9;border-radius:4px;display:inline-block;">' . esc_html($available) . ' rooms available</div>';
    }
}, 25);

// Main admin panel shortcode [hotel_admin_panel]
add_shortcode("hotel_admin_panel", "hotel_admin_panel_shortcode");
function hotel_admin_panel_shortcode() {
    if (!is_user_logged_in() || get_current_user_id() != 1) {
        return "<p>Access denied. Admin only.</p>";
    }

    $nonce = wp_create_nonce("ha_nonce");
    ob_start();

    // Handle block user
    if (isset($_GET["ha"]) && $_GET["ha"] === "block" && wp_verify_nonce($_GET["_n"], "ha_nonce")) {
        $uid = intval($_GET["uid"]);
        if ($uid != 1) {
            $u = new WP_User($uid);
            $u->set_role("blocked");
            echo '<div class="ha-notice ha-notice-success">User blocked.</div>';
        }
    }

    // Handle unblock user
    if (isset($_GET["ha"]) && $_GET["ha"] === "unblock" && wp_verify_nonce($_GET["_n"], "ha_nonce")) {
        $uid = intval($_GET["uid"]);
        $u = new WP_User($uid);
        $u->set_role("customer");
        echo '<div class="ha-notice ha-notice-success">User unblocked.</div>';
    }

    // Handle delete room
    if (isset($_GET["ha"]) && $_GET["ha"] === "delete_room" && wp_verify_nonce($_GET["_n"], "ha_nonce")) {
        $pid = intval($_GET["pid"]);
        $pname = get_the_title($pid);
        wp_delete_post($pid, true);
        echo '<div class="ha-notice ha-notice-warning">Room deleted: ' . esc_html($pname) . '</div>';
    }

    // Handle update price
    if (isset($_POST["ha_action"]) && $_POST["ha_action"] === "update_price" && wp_verify_nonce($_POST["_n"], "ha_nonce")) {
        $pid = intval($_POST["pid"]);
        $price = floatval($_POST["price"]);
        update_post_meta($pid, "_price", $price);
        update_post_meta($pid, "_regular_price", $price);
        wc_delete_product_transients($pid);
        echo '<div class="ha-notice ha-notice-success">Price updated to $' . number_format($price, 2) . '</div>';
    }

    // Handle edit room (title, content, excerpt, price, thumbnail)
    if (isset($_POST["ha_action"]) && $_POST["ha_action"] === "edit_room" && wp_verify_nonce($_POST["_n"], "ha_nonce")) {
        $pid = intval($_POST["pid"]);
        $name = sanitize_text_field($_POST["room_name"]);
        $price = floatval($_POST["room_price"]);
        $desc = sanitize_textarea_field($_POST["room_desc"]);
        $short = sanitize_text_field($_POST["room_short"]);

        wp_update_post(array(
            "ID" => $pid,
            "post_title" => $name,
            "post_content" => $desc,
            "post_excerpt" => $short,
        ));
        update_post_meta($pid, "_price", $price);
        update_post_meta($pid, "_regular_price", $price);

        if (!empty($_FILES["room_image"]["name"]) && $_FILES["room_image"]["error"] === 0) {
            $file = $_FILES["room_image"];
            $upload_dir = wp_upload_dir();
            $hotel_dir = $upload_dir["basedir"] . "/hotel-pics/";
            if (!is_dir($hotel_dir)) {
                mkdir($hotel_dir, 0755, true);
            }
            $ext = pathinfo($file["name"], PATHINFO_EXTENSION);
            $filename = sanitize_title($name) . "." . $ext;
            $filepath = $hotel_dir . $filename;
            if (move_uploaded_file($file["tmp_name"], $filepath)) {
                $wp_filetype = wp_check_filetype($filename);
                $attach_id = wp_insert_attachment(array(
                    "post_mime_type" => $wp_filetype["type"],
                    "post_title" => $name,
                    "post_status" => "inherit",
                ), $filepath, $pid);
                if (!is_wp_error($attach_id)) {
                    require_once(ABSPATH . "wp-admin/includes/image.php");
                    $attach_data = wp_generate_attachment_metadata($attach_id, $filepath);
                    wp_update_attachment_metadata($attach_id, $attach_data);
                    set_post_thumbnail($pid, $attach_id);
                }
            }
        }

        wc_delete_product_transients($pid);
        echo '<div class="ha-notice ha-notice-success">Room updated: ' . esc_html($name) . '</div>';
    }

    // Handle add room
    if (isset($_POST["ha_action"]) && $_POST["ha_action"] === "add_room" && wp_verify_nonce($_POST["_n"], "ha_nonce")) {
        $name = sanitize_text_field($_POST["room_name"]);
        $price = floatval($_POST["room_price"]);
        $desc = sanitize_textarea_field($_POST["room_desc"]);
        $short = sanitize_text_field($_POST["room_short"]);

        $pid = wp_insert_post(array(
            "post_title" => $name,
            "post_type" => "product",
            "post_status" => "publish",
            "post_content" => $desc,
            "post_excerpt" => $short . " per month.",
        ));

        if ($pid && !is_wp_error($pid)) {
            wp_set_object_terms($pid, "simple", "product_type");
            update_post_meta($pid, "_price", $price);
            update_post_meta($pid, "_regular_price", $price);
            update_post_meta($pid, "_virtual", "yes");
            update_post_meta($pid, "_visibility", "visible");

            if (!empty($_FILES["room_image"]["name"]) && $_FILES["room_image"]["error"] === 0) {
                $file = $_FILES["room_image"];
                $upload_dir = wp_upload_dir();
                $hotel_dir = $upload_dir["basedir"] . "/hotel-pics/";
                if (!is_dir($hotel_dir)) {
                    mkdir($hotel_dir, 0755, true);
                }
                $ext = pathinfo($file["name"], PATHINFO_EXTENSION);
                $filename = sanitize_title($name) . "." . $ext;
                $filepath = $hotel_dir . $filename;
                if (move_uploaded_file($file["tmp_name"], $filepath)) {
                    $wp_filetype = wp_check_filetype($filename);
                    $attach_id = wp_insert_attachment(array(
                        "post_mime_type" => $wp_filetype["type"],
                        "post_title" => $name,
                        "post_status" => "inherit",
                    ), $filepath, $pid);
                    if (!is_wp_error($attach_id)) {
                        require_once(ABSPATH . "wp-admin/includes/image.php");
                        $attach_data = wp_generate_attachment_metadata($attach_id, $filepath);
                        wp_update_attachment_metadata($attach_id, $attach_data);
                        set_post_thumbnail($pid, $attach_id);
                    }
                }
            }

            echo '<div class="ha-notice ha-notice-success">Room added: ' . esc_html($name) . ' ($' . number_format($price, 2) . '/month) <a href="' . esc_url(get_permalink($pid)) . '" style="color:#fff;text-decoration:underline;">View</a></div>';
        }
    }

    $tab = isset($_GET["ha_tab"]) ? sanitize_key($_GET["ha_tab"]) : "users";
    if (!in_array($tab, array("users", "rooms", "add", "bookings"), true)) {
        $tab = "users";
    }
    ?>
    <style>
    .ha-panel { max-width: 900px; margin: 0 auto; }
    .ha-tabs { display: flex; gap: 5px; margin-bottom: 20px; }
    .ha-tabs a { padding: 10px 20px; background: #333; color: #fff; text-decoration: none; border-radius: 4px 4px 0 0; }
    .ha-tabs a.active { background: #1565C0; }
    .ha-table { width: 100%; border-collapse: collapse; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.06); }
    .ha-table th { background: #1565C0; color: #fff; padding: 12px; text-align: left; font-size: 13px; }
    .ha-table td { padding: 10px 12px; border-bottom: 1px solid #eee; font-size: 13px; }
    .ha-table tr:hover td { background: #f5f8ff; }
    .ha-btn { padding: 6px 14px; border-radius: 4px; border: none; cursor: pointer; font-size: 12px; font-weight: 600; text-decoration: none; display: inline-block; }
    .ha-btn-block { background: #f44336; color: #fff; }
    .ha-btn-unblock { background: #4caf50; color: #fff; }
    .ha-btn-save { background: #1565C0; color: #fff; }
    .ha-btn-delete { background: #d32f2f; color: #fff; }
    .ha-btn-add { background: #1565C0; color: #fff; padding: 10px 24px; font-size: 14px; }
    .ha-form-group { margin-bottom: 15px; }
    .ha-form-group label { display: block; font-weight: 600; margin-bottom: 4px; color: #333; }
    .ha-form-group input, .ha-form-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
    .ha-form-group input[type=file] { padding: 8px; border: 2px dashed #ddd; background: #fafafa; }
    .ha-badge { display: inline-block; padding: 2px 8px; border-radius: 3px; font-size: 11px; color: #fff; }
    .ha-badge-blocked { background: #f44336; }
    .ha-badge-customer { background: #4caf50; }
    .ha-badge-admin { background: #ff9800; }
    .ha-notice { padding: 10px; border-radius: 4px; margin-bottom: 15px; }
    .ha-notice-success { background: #4caf50; color: #fff; }
    .ha-notice-warning { background: #ff9800; color: #fff; }
    .ha-edit-form { display: none; background: #f9f9fb; padding: 20px; border-radius: 8px; margin: 10px 0; }
    .ha-edit-form.active { display: block; }
    .ha-edit-form .ha-form-row { display: flex; gap: 12px; margin-bottom: 12px; flex-wrap: wrap; }
    .ha-edit-form .ha-form-row label { font-weight: 600; font-size: 12px; display: block; margin-bottom: 4px; color: #333; }
    .ha-edit-form .ha-form-row input, .ha-edit-form .ha-form-row textarea { padding: 8px 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 13px; width: 100%; box-sizing: border-box; }
    .ha-edit-form .ha-form-col { flex: 1; min-width: 180px; }
    .ha-edit-form .ha-form-col-full { flex: 100%; }
    </style>

    <div class="ha-panel">
    <h2 style="color:#1565C0;margin-bottom:15px;">Hotel Admin Panel</h2>
    <div class="ha-tabs">
        <a href="?ha_tab=users" class="<?php echo $tab === "users" ? "active" : ""; ?>">Users</a>
        <a href="?ha_tab=rooms" class="<?php echo $tab === "rooms" ? "active" : ""; ?>">Rooms &amp; Prices</a>
        <a href="?ha_tab=add" class="<?php echo $tab === "add" ? "active" : ""; ?>">Add Room</a>
        <a href="?ha_tab=bookings" class="<?php echo $tab === "bookings" ? "active" : ""; ?>">Bookings</a>
    </div>
    <?php

    // --- Users Tab ---
    if ($tab === "users"):
        ?>
        <table class="ha-table">
            <tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Action</th></tr>
            <?php foreach (get_users() as $u): ?>
                <?php
                $roles = implode(", ", $u->roles);
                $badge = in_array("blocked", $u->roles) ? "blocked" : (in_array("administrator", $u->roles) ? "admin" : "customer");
                $block_url = add_query_arg(array("ha" => "block", "uid" => $u->ID, "_n" => $nonce));
                $unblock_url = add_query_arg(array("ha" => "unblock", "uid" => $u->ID, "_n" => $nonce));
                ?>
                <tr>
                    <td><?php echo esc_html($u->ID); ?></td>
                    <td><?php echo esc_html($u->user_login); ?></td>
                    <td><?php echo esc_html($u->user_email); ?></td>
                    <td><span class="ha-badge ha-badge-<?php echo esc_attr($badge); ?>"><?php echo esc_html($roles); ?></span></td>
                    <td>
                        <?php if ($u->ID != 1): ?>
                            <?php if (in_array("blocked", $u->roles)): ?>
                                <a href="<?php echo esc_url($unblock_url); ?>" class="ha-btn ha-btn-unblock">Unblock</a>
                            <?php else: ?>
                                <a href="<?php echo esc_url($block_url); ?>" class="ha-btn ha-btn-block">Block</a>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        <?php

    // --- Rooms Tab ---
    elseif ($tab === "rooms"):
        $edit_id = isset($_GET["edit_room"]) ? intval($_GET["edit_room"]) : 0;
        ?>
        <table class="ha-table">
            <tr><th>ID</th><th>Image</th><th>Name</th><th>Price</th><th>Actions</th></tr>
            <?php foreach (wc_get_products(array("limit" => 50, "orderby" => "title", "order" => "ASC")) as $p): ?>
                <?php
                $id = $p->get_id();
                $pname = $p->get_name();
                $price = $p->get_price();
                $thumb = get_the_post_thumbnail_url($id, "thumbnail") ?: wc_placeholder_img_src("thumbnail");
                $delete_url = add_query_arg(array("ha" => "delete_room", "pid" => $id, "_n" => $nonce));
                $edit_url = add_query_arg(array("ha_tab" => "rooms", "edit_room" => $id));
                ?>
                <tr>
                    <td><?php echo esc_html($id); ?></td>
                    <td><?php echo '<img src="' . esc_url($thumb) . '" alt="' . esc_attr($pname) . '" style="width:50px;height:50px;object-fit:cover;border-radius:4px;">'; ?></td>
                    <td><a href="<?php echo esc_url(get_permalink($id)); ?>"><?php echo esc_html($pname); ?></a></td>
                    <td><?php echo '$' . number_format($price, 2) . '/month'; ?></td>
                    <td>
                        <a href="<?php echo esc_url($edit_url); ?>" class="ha-btn ha-btn-save">Edit</a>
                        <a href="<?php echo esc_url($delete_url); ?>" class="ha-btn ha-btn-delete" onclick="return confirm('<?php echo esc_js(sprintf("Delete %s?", $pname)); ?>')">Delete</a>
                    </td>
                </tr>
                <?php if ($edit_id === $id): ?>
                <tr><td colspan="5">
                <div class="ha-edit-form active">
                    <form method="post" enctype="multipart/form-data">
                        <input type="hidden" name="ha_action" value="edit_room">
                        <input type="hidden" name="pid" value="<?php echo esc_attr($id); ?>">
                        <input type="hidden" name="_n" value="<?php echo esc_attr($nonce); ?>">
                        <div class="ha-form-row">
                            <div class="ha-form-col">
                                <label>Room Name</label>
                                <input type="text" name="room_name" value="<?php echo esc_attr($pname); ?>" required>
                            </div>
                            <div class="ha-form-col">
                                <label>Price per month ($)</label>
                                <input type="number" name="room_price" step="0.01" min="0" value="<?php echo esc_attr($price); ?>" required>
                            </div>
                        </div>
                        <div class="ha-form-row">
                            <div class="ha-form-col">
                                <label>Short Description</label>
                                <input type="text" name="room_short" value="<?php echo esc_attr($p->get_short_description()); ?>">
                            </div>
                        </div>
                        <div class="ha-form-row">
                            <div class="ha-form-col-full">
                                <label>Full Description</label>
                                <textarea name="room_desc" rows="3"><?php echo esc_textarea($p->get_description()); ?></textarea>
                            </div>
                        </div>
                        <div class="ha-form-row">
                            <div class="ha-form-col">
                                <label>Replace Image (optional)</label>
                                <input type="file" name="room_image" accept="image/*">
                            </div>
                        </div>
                        <button type="submit" class="ha-btn ha-btn-save" style="padding:10px 20px;font-size:14px;">Save Changes</button>
                        <a href="?ha_tab=rooms" class="ha-btn" style="background:#999;color:#fff;text-decoration:none;padding:8px 16px;border-radius:4px;">Cancel</a>
                    </form>
                </div>
                </td></tr>
                <?php endif; ?>
            <?php endforeach; ?>
        </table>
        <?php


    // --- Bookings Calendar Tab ---
    elseif ($tab === "bookings"):
        $orders = wc_get_orders(array("limit" => 100, "status" => array("processing", "completed")));
        $booking_ranges = array(); // array of {room, checkin_ts, checkout_ts, color}
        $room_colors = array();
        $all_rooms = wc_get_products(array("limit" => 50, "orderby" => "title", "order" => "ASC"));
        $color_palette = array("#1565C0","#2e7d32","#c62828","#6a1b9a","#e65100","#00695c","#283593","#4a148c","#b71c1c","#1b5e20","#0d47a1","#880e4f");
        $ci2 = 0;
        foreach ($all_rooms as $r2) {
            $room_colors[$r2->get_name()] = $color_palette[$ci2 % count($color_palette)];
            $ci2++;
        }

        foreach ($orders as $order2) {
            foreach ($order2->get_items() as $item2) {
                $checkin = $item2->get_meta("Check-in");
                $checkout = $item2->get_meta("Check-out");
                if ($checkin && $checkout) {
                    $booking_ranges[] = array(
                        "room" => $item2->get_name(),
                        "checkin" => strtotime($checkin),
                        "checkout" => strtotime($checkout),
                        "color" => isset($room_colors[$item2->get_name()]) ? $room_colors[$item2->get_name()] : "#999",
                    );
                }
            }
        }

        $bmonth = isset($_GET["ha_month"]) ? intval($_GET["ha_month"]) : intval(date("m"));
        $byear = isset($_GET["ha_year"]) ? intval($_GET["ha_year"]) : intval(date("Y"));
        if ($bmonth < 1) { $bmonth = 12; $byear--; }
        if ($bmonth > 12) { $bmonth = 1; $byear++; }
        $first_day = strtotime("$byear-$bmonth-01");
        $days_in_month = intval(date("t", $first_day));
        $start_dow = intval(date("N", $first_day)) - 1;
        $prev_month = $bmonth - 1; $prev_year = $byear;
        if ($prev_month < 1) { $prev_month = 12; $prev_year--; }
        $next_month = $bmonth + 1; $next_year = $byear;
        if ($next_month > 12) { $next_month = 1; $next_year++; }

        // Build day-by-day data: for each day, list rooms and whether it's start/middle/end
        $month_start_ts = strtotime("$byear-$bmonth-01");
        $month_end_ts = strtotime("$byear-$bmonth-$days_in_month 23:59:59");
        $day_data = array(); // day_num => array of room entries
        for ($d = 1; $d <= $days_in_month; $d++) {
            $day_data[$d] = array();
        }

        foreach ($booking_ranges as $br) {
            $range_first_day = max(1, intval(date("j", max($br["checkin"], $month_start_ts))));
            $range_last_day = min($days_in_month, intval(date("j", min($br["checkout"], $month_end_ts))));
            
            if ($range_first_day > $range_last_day) continue;
            if ($br["checkin"] > $month_end_ts || $br["checkout"] <= $month_start_ts) continue;

            for ($d = $range_first_day; $d <= $range_last_day; $d++) {
                $pos = "mid";
                if ($br["checkin"] >= $month_start_ts && $d === $range_first_day) $pos = "start";
                if ($br["checkout"] - 1 <= $month_end_ts && $d === $range_last_day) $pos = "end";
                if ($range_first_day === $range_last_day) $pos = "single";
                // Handle edge: if checkin is before month start but first day of range, treat as "mid" (or "start" if we want rounded)
                if ($br["checkin"] < $month_start_ts && $d === $range_first_day) $pos = "mid";
                if ($br["checkout"] - 1 > $month_end_ts && $d === $range_last_day) $pos = "mid";
                
                $day_data[$d][] = array("room" => $br["room"], "color" => $br["color"], "pos" => $pos);
            }
        }

        ?>
        <style>
        .bookings-calendar { display:grid; grid-template-columns:repeat(7,1fr); gap:2px; background:#fff; padding:10px; border-radius:8px; }
        .cal-day-header { text-align:center; font-weight:700; font-size:12px; padding:8px; color:#666; background:#f5f5f5; }
        .cal-day { min-height:80px; padding:4px 4px 8px 4px; border:1px solid #eee; font-size:11px; position:relative; background:#fff; display:flex; flex-direction:column; }
        .cal-day.other-month { opacity:0.35; }
        .cal-day.today { border:2px solid #7f54b3; }
        .cal-day-num { font-weight:700; color:#333; margin-bottom:2px; flex-shrink:0; }
        .cal-booking-bar { font-size:9px; color:#fff; padding:2px 4px; margin:1px 0; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; position:relative; }
        .cal-booking-bar.start { border-radius:4px 0 0 4px; margin-left:2px; }
        .cal-booking-bar.end { border-radius:0 4px 4px 0; margin-right:2px; }
        .cal-booking-bar.single { border-radius:4px; margin-left:2px; margin-right:2px; }
        .cal-booking-bar.mid { border-radius:0; margin-left:-4px; margin-right:-4px; padding-left:8px; padding-right:8px; }
        .cal-nav { display:flex; align-items:center; gap:12px; margin-bottom:16px; }
        .cal-nav h3 { margin:0; color:#333; min-width:180px; }
        .cal-nav button { padding:6px 14px; border:1px solid #ddd; background:#fff; border-radius:4px; cursor:pointer; font-size:13px; }
        .cal-legend { display:flex; flex-wrap:wrap; gap:8px; margin-top:12px; padding:8px 0; }
        .cal-legend-item { display:flex; align-items:center; gap:4px; font-size:11px; color:#555; }
        .cal-legend-color { width:12px; height:12px; border-radius:2px; flex-shrink:0; }
        </style>
        <div class="cal-nav">
            <a href="?ha_tab=bookings&amp;ha_month=<?php echo $prev_month; ?>&amp;ha_year=<?php echo $prev_year; ?>" style="text-decoration:none;"><button>&larr; Prev</button></a>
            <h3><?php echo date("F Y", $first_day); ?></h3>
            <a href="?ha_tab=bookings&amp;ha_month=<?php echo $next_month; ?>&amp;ha_year=<?php echo $next_year; ?>" style="text-decoration:none;"><button>Next &rarr;</button></a>
            <a href="?ha_tab=bookings" style="text-decoration:none;margin-left:auto;"><button>Today</button></a>
        </div>
        <div class="bookings-calendar">
            <?php foreach (array("Mon","Tue","Wed","Thu","Fri","Sat","Sun") as $dn): ?>
                <div class="cal-day-header"><?php echo $dn; ?></div>
            <?php endforeach; ?>
            <?php for ($i = 0; $i < $start_dow; $i++): ?>
                <div class="cal-day other-month"></div>
            <?php endfor; ?>
            <?php for ($d = 1; $d <= $days_in_month; $d++): ?>
                <?php $date_key = sprintf("%04d-%02d-%02d", $byear, $bmonth, $d); ?>
                <div class="cal-day <?php echo ($date_key === date("Y-m-d")) ? "today" : ""; ?>">
                    <div class="cal-day-num"><?php echo $d; ?></div>
                    <?php if (isset($day_data[$d])): ?>
                        <?php foreach ($day_data[$d] as $entry): ?>
                            <div class="cal-booking-bar <?php echo $entry["pos"]; ?>" style="background:<?php echo $entry["color"]; ?>;" title="<?php echo esc_attr($entry["room"]); ?>"><?php echo esc_html($entry["room"]); ?></div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php endfor; ?>
        </div>
        <div class="cal-legend">
            <?php foreach ($room_colors as $room_name => $color): ?>
                <div class="cal-legend-item"><div class="cal-legend-color" style="background:<?php echo $color; ?>;"></div><?php echo esc_html($room_name); ?></div>
            <?php endforeach; ?>
        </div>
        <?php

    // --- Add Room Tab ---
    else:
        ?>
        <form method="post" enctype="multipart/form-data" style="background:#fff;padding:25px;border-radius:8px;box-shadow:0 2px 10px rgba(0,0,0,0.06);">
            <input type="hidden" name="ha_action" value="add_room">
            <input type="hidden" name="_n" value="<?php echo esc_attr($nonce); ?>">
            <div class="ha-form-group">
                <label>Room Image (optional)</label>
                <input type="file" name="room_image" accept="image/*">
            </div>
            <div class="ha-form-group">
                <label>Room Name</label>
                <input type="text" name="room_name" required placeholder="e.g. Ocean View Suite">
            </div>
            <div class="ha-form-group">
                <label>Price per month ($)</label>
                <input type="number" name="room_price" step="0.01" min="0" required placeholder="299">
            </div>
            <div class="ha-form-group">
                <label>Short Description</label>
                <input type="text" name="room_short" placeholder="Brief summary shown on listing">
            </div>
            <div class="ha-form-group">
                <label>Full Description</label>
                <textarea name="room_desc" rows="4" placeholder="Detailed room description"></textarea>
            </div>
            <button type="submit" class="ha-btn ha-btn-add">Add Room</button>
        </form>
        <?php
    endif;
    ?>
    </div>
    <?php
    return ob_get_clean();
}

// Room listings shortcode [room_listings] with sort by price/name/rating, toggle ASC/DESC
add_shortcode("room_listings", "room_listings_shortcode");
function room_listings_shortcode($atts) {
    $sort = isset($_GET["sort"]) ? sanitize_text_field($_GET["sort"]) : "price";
    $order = isset($_GET["order"]) ? sanitize_text_field($_GET["order"]) : "DESC";

    $valid_sorts = array("price", "title", "rating");
    if (!in_array($sort, $valid_sorts, true)) $sort = "price";
    if (!in_array($order, array("ASC", "DESC"), true)) $order = "DESC";

    // Collect active filters from URL
    $active_filters = array();
    $filter_keys = array("property_type","breakfast","pool","parking","wifi","spa","hottub","private_bath","ac","balcony","kitchen","free_cancel","no_prepay","pets","fitness","indoor_pool","rating_tier");
    foreach ($filter_keys as $fk) {
        if (!empty($_GET[$fk])) {
            $active_filters[$fk] = sanitize_text_field($_GET[$fk]);
        }
    }

    // Handle max_price separately (not from room meta)
    $max_price = isset($_GET["max_price"]) ? floatval($_GET["max_price"]) : 0;
    $global_max_price = 0;
    foreach ($all_products as $prod) {
        $p = floatval(get_post_meta($prod->ID, "_price", true));
        if ($p > $global_max_price) $global_max_price = $p;
    }
    if ($global_max_price < 1) $global_max_price = 1000;

    // Define filter groups for sidebar
    $filter_groups = array(
        "Property Type" => array("property_type" => array("Hotel","Resort","Motel","Apartment")),
        "Star Rating" => array("rating_tier" => array("s5" => "5 stars","s4" => "4 stars","s3" => "3 stars","s2" => "2 stars","s1" => "1 star")),
        "Facilities" => array("pool" => array("yes" => "Swimming pool"),"parking" => array("yes" => "Parking"),"wifi" => array("yes" => "Free WiFi"),"spa" => array("yes" => "Spa & wellness"),"hottub" => array("yes" => "Hot tub"),"fitness" => array("yes" => "Fitness centre"),"indoor_pool" => array("yes" => "Indoor pool")),
        "Room Features" => array("private_bath" => array("yes" => "Private bathroom"),"ac" => array("yes" => "Air conditioning"),"balcony" => array("yes" => "Balcony"),"kitchen" => array("yes" => "Kitchen/kitchenette")),
        "Meals" => array("breakfast" => array("yes" => "Breakfast included")),
        "Policies" => array("free_cancel" => array("yes" => "Free cancellation"),"no_prepay" => array("yes" => "No prepayment"),"pets" => array("yes" => "Pets allowed")),
    );

    // Query all products first (we filter in PHP for flexibility)
    $args = array(
        "post_type" => "product",
        "post_status" => "publish",
        "posts_per_page" => 50,
    );
    $all_products = get_posts($args);

    // Filter by selected criteria (check _room_filters meta)
    $filtered = array();
    $filter_counts = array(); // Count how many match each filter value

    foreach ($all_products as $prod) {
        $filters_json = get_post_meta($prod->ID, "_room_filters", true);
        if (!$filters_json) continue;
        $room_filters = json_decode($filters_json, true);
        if (!$room_filters) continue;

        $match = true;
        foreach ($active_filters as $fk => $fv) {
            if ($fk === "rating_tier") {
                // Star rating: check floor of average rating
                $avg = floatval(get_post_meta($prod->ID, "_wc_average_rating", true));
                $stars = max(1, floor($avg)); // A rating of 0 still counts as 1 star
                if ("s" . $stars !== $fv) { $match = false; break; }
            } elseif (isset($room_filters[$fk]) && $room_filters[$fk] === $fv) {
                // meta match
            } else {
                $match = false; break;
            }
        }
        if ($max_price > 0) {
            $prod_price = floatval(get_post_meta($prod->ID, "_price", true));
            if ($prod_price > $max_price) $match = false;
        }
        if ($match) {
            $filtered[] = $prod;
        }

        // Count filter values for sidebar display
        foreach ($filter_keys as $fk) {
            if ($fk === "rating_tier") {
                $avg = floatval(get_post_meta($prod->ID, "_wc_average_rating", true));
                $stars = "s" . max(1, floor($avg));
                if (!isset($filter_counts[$fk])) $filter_counts[$fk] = array();
                if (!isset($filter_counts[$fk][$stars])) $filter_counts[$fk][$stars] = 0;
                $filter_counts[$fk][$stars]++;
            } elseif (isset($room_filters[$fk])) {
                $val = $room_filters[$fk];
                if (!isset($filter_counts[$fk])) $filter_counts[$fk] = array();
                if (!isset($filter_counts[$fk][$val])) $filter_counts[$fk][$val] = 0;
                $filter_counts[$fk][$val]++;
            }
        }
    }

    // Sort filtered results
    if ($sort === "price") {
        usort($filtered, function($a, $b) use ($order) {
            $pa = floatval(get_post_meta($a->ID, "_price", true));
            $pb = floatval(get_post_meta($b->ID, "_price", true));
            return $order === "ASC" ? $pa - $pb : $pb - $pa;
        });
    } elseif ($sort === "title") {
        usort($filtered, function($a, $b) use ($order) {
            return $order === "ASC" ? strcasecmp($a->post_title, $b->post_title) : strcasecmp($b->post_title, $a->post_title);
        });
    } elseif ($sort === "rating") {
        usort($filtered, function($a, $b) use ($order) {
            $ra = floatval(get_post_meta($a->ID, "_wc_average_rating", true));
            $rb = floatval(get_post_meta($b->ID, "_wc_average_rating", true));
            return $order === "ASC" ? $ra - $rb : $rb - $ra;
        });
    }

    $base_url = get_permalink();
    ob_start();
    ?>
    <style>
    .room-listing-wrap { display: flex; gap: 24px; }
    .room-filters-sidebar { width: 240px; flex-shrink: 0; }
    .room-filters-sidebar h4 { font-size: 14px; font-weight: 700; color: #131315; margin: 0 0 6px 0; }
    .filter-group { margin-bottom: 18px; border-bottom: 1px solid #eee; padding-bottom: 12px; }
    .filter-group:last-child { border-bottom: none; }
    .filter-option { display: flex; align-items: center; gap: 8px; padding: 3px 0; font-size: 13px; color: #43454b; }
    .filter-option input[type=checkbox] { margin: 0; }
    .filter-option label { cursor: pointer; flex: 1; }
    .filter-option .count { color: #999; font-size: 11px; }
    .filter-option a { color: #43454b; text-decoration: none; display: flex; align-items: center; gap: 6px; width: 100%; }
    .filter-option a:hover { color: #7f54b3; }
    .filter-option a.active-filter { color: #7f54b3; font-weight: 600; }
    .filter-clear { display: inline-block; margin-top: 8px; font-size: 12px; color: #d32f2f; text-decoration: none; }
    .room-main-content { flex: 1; min-width: 0; }
    .room-sort-bar { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 24px; align-items: center; }
    .room-sort-bar span.sort-label { font-weight: 600; color: #131315; margin-right: 4px; font-size: 13px; }
    .room-sort-btn { padding: 6px 14px; border: 2px solid #ddd; background: #fff; border-radius: 6px; cursor: pointer; font-size: 13px; font-weight: 600; color: #43454b; transition: all 0.15s; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; }
    .room-sort-btn:hover { border-color: #7f54b3; color: #7f54b3; }
    .room-sort-btn.active { background: #7f54b3; border-color: #7f54b3; color: #fff; }
    .room-sort-btn .arrow { font-size: 11px; opacity: 0.6; }
    .room-sort-btn.active .arrow { opacity: 1; }
    .results-count { font-size: 13px; color: #666; margin-bottom: 12px; }
    @media (max-width: 768px) { .room-listing-wrap { flex-direction: column; } .room-filters-sidebar { width: 100%; } }
    </style>
    <div class="room-listing-wrap">
        <div class="room-filters-sidebar">
            <h4>Filters</h4>
            <?php if (!empty($active_filters)): ?>
                <a href="<?php echo esc_url(esc_url($base_url)); ?>" class="filter-clear">Clear all filters</a>
            <?php endif; ?>
            <?php foreach ($filter_groups as $group_name => $filters): ?>
                <div class="filter-group">
                    <h4><?php echo esc_html($group_name); ?></h4>
                    <?php foreach ($filters as $key => $options): ?>
                        <?php foreach ($options as $val => $label):
                            if (is_int($val)) { $val = $label; } // numeric keys
                            $is_active = isset($active_filters[$key]) && $active_filters[$key] === $val;
                            $count = isset($filter_counts[$key][$val]) ? $filter_counts[$key][$val] : 0;
                            if ($is_active) {
                                $url = remove_query_arg($key, add_query_arg($_GET));
                                if (empty($_GET) || (count($_GET) === 1 && isset($_GET[$key]))) $url = $base_url;
                            } else {
                                $url = add_query_arg(array_merge($_GET, array($key => $val)), $base_url);
                            }
                            $url = esc_url($url);
                        ?>
                            <div class="filter-option">
                                <a href="<?php echo $url; ?>" class="<?php echo $is_active ? 'active-filter' : ''; ?>">
                                    <?php if ($is_active): ?>&#10003;<?php else: ?>&#9675;<?php endif; ?>
                                    <?php echo esc_html($label); ?>
                                    <span class="count">(<?php echo $count; ?>)</span>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <div class="room-main-content">
            <div class="price-slider-bar" style="background:#f5f5f5;padding:14px 18px;border-radius:8px;margin-bottom:16px;">
                <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
                    <span style="font-weight:600;font-size:13px;color:#131315;">Max price: <span id="price-slider-val" style="color:#7f54b3;">$<?php echo $max_price > 0 ? number_format($max_price) : number_format($global_max_price); ?></span>/month</span>
                    <input type="range" id="price-slider" min="0" max="<?php echo ceil($global_max_price); ?>" value="<?php echo $max_price > 0 ? $max_price : $global_max_price; ?>" step="10" style="flex:1;min-width:150px;accent-color:#7f54b3;" oninput="document.getElementById('price-slider-val').textContent='$'+Number(this.value).toLocaleString();">
                    <a id="price-slider-link" href="<?php echo esc_url(add_query_arg(array_merge($_GET, array('max_price' => $max_price > 0 ? $max_price : $global_max_price)), $base_url)); ?>" class="room-sort-btn active" style="white-space:nowrap;">Apply</a>
                    <?php if ($max_price > 0): ?>
                        <a href="<?php echo esc_url(remove_query_arg('max_price', add_query_arg($_GET))); ?>" style="font-size:12px;color:#d32f2f;text-decoration:none;white-space:nowrap;">Reset price</a>
                    <?php endif; ?>
                </div>
                <script>
                (function(){
                    var slider = document.getElementById("price-slider");
                    var link = document.getElementById("price-slider-link");
                    if (slider && link) {
                        slider.addEventListener("input", function(){
                            var url = new URL(link.href);
                            url.searchParams.set("max_price", this.value);
                            link.href = url.toString();
                        });
                    }
                })();
                </script>
            </div>
            <div class="room-sort-bar">
                <span class="sort-label">Sort by:</span>
                <?php
                $buttons = array(
                    "price" => array("label" => "Price", "asc" => "&#8593;", "desc" => "&#8595;"),
                    "title" => array("label" => "Name", "asc" => "A-Z", "desc" => "Z-A"),
                    "rating" => array("label" => "Rating", "asc" => "&#8593;", "desc" => "&#8595;"),
                );
                foreach ($buttons as $key => $b):
                    $is_active_sort = ($sort === $key);
                    $next_order = ($is_active_sort && $order === "DESC") ? "ASC" : "DESC";
                    $sort_url = esc_url(add_query_arg(array_merge($_GET, array("sort" => $key, "order" => $next_order)), $base_url));
                ?>
                    <a href="<?php echo $sort_url; ?>" class="room-sort-btn <?php echo $is_active_sort ? "active" : ""; ?>">
                        <?php echo esc_html($b["label"]); ?>
                        <span class="arrow"><?php echo $is_active_sort ? ($order === "ASC" ? $b["asc"] : $b["desc"]) : ""; ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
            <div class="results-count"><?php echo count($filtered); ?> rooms found</div>
            <ul class="products columns-3">
            <?php
            if (!empty($filtered)) {
                foreach ($filtered as $prod) {
                    global $post;
                    $post = $prod;
                    setup_postdata($post);
                    wc_get_template_part("content", "product");
                }
                wp_reset_postdata();
            } else {
                echo '<p>No rooms match your filters. <a href="' . esc_url($base_url) . '">Clear filters</a></p>';
            }
            ?>
            </ul>
        </div>
    </div>
    <?php
    return ob_get_clean();
}

// Room search autocomplete AJAX endpoint
add_action("wp_ajax_room_search", "room_search_ajax");
add_action("wp_ajax_nopriv_room_search", "room_search_ajax");
function room_search_ajax() {
    if (!isset($_GET["q"])) {
        wp_send_json(array());
    }
    $search = sanitize_text_field($_GET["q"]);
    if (strlen($search) < 1) {
        wp_send_json(array());
    }

    $args = array(
        "post_type" => "product",
        "post_status" => "publish",
        "posts_per_page" => 10,
        "s" => $search,
        "orderby" => "title",
        "order" => "ASC",
    );
    $query = new WP_Query($args);
    $results = array();
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $product = wc_get_product(get_the_ID());
            $results[] = array(
                "title" => get_the_title(),
                "url" => get_permalink(),
                "price" => $product ? wp_strip_all_tags(wc_price($product->get_price())) : "",
            );
        }
        wp_reset_postdata();
    }
    wp_send_json($results);
}

// Add Admin Panel to nav menu (visible only for user ID 1)
add_filter("wp_nav_menu_items", function($items, $args) {
    if (is_user_logged_in() && get_current_user_id() == 1) {
        $items .= '<li class="menu-item"><a href="' . esc_url(home_url("/hotel-admin/")) . '">Admin Panel</a></li>';
    }
    return $items;
}, 10, 2);

// Add Admin Panel to page list (visible only for user ID 1)
add_filter("wp_list_pages", function($output) {
    if (is_user_logged_in() && get_current_user_id() == 1) {
        $output .= '<li class="page_item"><a href="' . esc_url(home_url("/hotel-admin/")) . '">Admin Panel</a></li>';
    }
    return $output;
});

// Enqueue autocomplete CSS + JS at priority 20
add_action("wp_enqueue_scripts", function() {
    wp_enqueue_script("jquery");

    wp_localize_script("jquery", "RoomSearch", array(
        "ajaxurl" => admin_url("admin-ajax.php"),
    ));

    $js = <<<'JS'
jQuery(document).ready(function($) {
    var $search = $(".site-search input.search-field");
    if (!$search.length) return;
    $search.attr("autocomplete", "off");

    var $dd = $("<div class=\"room-search-dropdown\"></div>").insertAfter($search);
    var timer, selectedIndex = -1;

    function hideDropdown() { $dd.hide(); selectedIndex = -1; }
    function escapeRegex(str) { return str.replace(/[.*+?^${}()|[\]\\]/g, "\\$&"); }

    $search.on("keyup", function(e) {
        if (e.key === "Escape" || e.key === "ArrowDown" || e.key === "ArrowUp" || e.key === "Enter") return;
        clearTimeout(timer);
        var q = $.trim(this.value);
        if (q.length < 1) { hideDropdown(); return; }
        timer = setTimeout(function() {
            $.getJSON(RoomSearch.ajaxurl, { action: "room_search", q: q }, function(data) {
                if (!data || !data.length) { hideDropdown(); return; }
                selectedIndex = -1;
                var html = "";
                $.each(data, function(i, item) {
                    var title = $("<span>").text(item.title).html();
                    var regex = new RegExp("(" + escapeRegex(q) + ")", "gi");
                    title = title.replace(regex, '<strong class="room-search-match">$1</strong>');
                    html += '<a href="' + item.url + '" class="room-search-item">';
                    html += '<span class="room-search-title">' + title + '</span>';
                    html += '<span class="room-search-price">' + item.price + '</span>';
                    html += '</a>';
                });
                $dd.html(html).show();
            });
        }, 180);
    });

    $search.on("keydown", function(e) {
        var $items = $dd.find(".room-search-item");
        if (e.key === "Escape") { hideDropdown(); return; }
        if (e.key === "ArrowDown") {
            e.preventDefault();
            selectedIndex = Math.min(selectedIndex + 1, $items.length - 1);
            $items.removeClass("active").eq(selectedIndex).addClass("active");
            return;
        }
        if (e.key === "ArrowUp") {
            e.preventDefault();
            selectedIndex = Math.max(selectedIndex - 1, -1);
            $items.removeClass("active").eq(selectedIndex).addClass("active");
            return;
        }
        if (e.key === "Enter" && selectedIndex >= 0) {
            e.preventDefault();
            window.location = $items.eq(selectedIndex).attr("href");
        }
    });

    $(document).on("click", function(e) {
        if (!$(e.target).closest(".site-search").length) hideDropdown();
    });
});
JS;

    wp_add_inline_script("jquery", $js);

    wp_add_inline_style("storefront-style", '/* Compact header layout */
#masthead.site-header {
    padding: 0 !important;
    border-bottom: 1px solid #eee !important;
    display: flex !important;
    align-items: center !important;
    flex-wrap: nowrap !important;
    height: 110px !important;
}
#masthead.site-header > .col-full:first-of-type { display: contents !important; }
.storefront-primary-navigation { margin: 50px 0 0 0 !important; padding: 0 !important; clear: none !important; float: none !important; width: auto !important; order: 1 !important; }
.storefront-primary-navigation > .col-full { display: contents !important; }
.main-navigation { clear: none !important; float: none !important; width: auto !important; margin: 0 !important; }
#site-navigation.main-navigation { width: auto !important; margin: 0 !important; padding: 0 !important; }
#menu-main-navigation { display: flex !important; gap: 2px !important; margin: 0 !important; }
.main-navigation ul.menu > li > a { padding: 6px 12px !important; font-size: 1.35em !important; line-height: 1.2 !important; }
.site-header .custom-logo-link img, .site-header .site-logo-anchor img, .site-header .site-logo-link img { max-height: 100px !important; width: auto !important; max-width: none !important; display: block !important; }
.site-header .custom-logo-link { display: flex !important; align-items: center !important; height: 100px !important; margin: 0 !important; padding: 0 !important; }
.site-header .site-branding { margin: 0 !important; padding: 0 !important; flex-shrink: 0 !important; width: auto !important; order: 0 !important; }
.site-header .site-branding .site-title { font-size: 0 !important; }
.site-header .site-search { margin: 0 0 0 10px !important; width: 540px !important; flex-shrink: 0 !important; order: 2 !important; }
.widget_product_search form:before { left: auto !important; right: 12px !important; }
.site-header .site-search input.search-field { padding: 6px 36px 6px 10px !important; font-size: 1em !important; height: 44px !important; }
.cart-button-below-search { margin: 0 0 0 8px !important; font-size: 0.85em !important; white-space: nowrap !important; flex-shrink: 0 !important; order: 3 !important; margin-left: auto !important; }
#site-header-cart { display: none !important; }
.skip-link { display: none !important; }
@media (max-width: 767px) {
    #masthead.site-header { height: auto !important; flex-wrap: wrap !important; }
    .site-header .site-search { width: 100% !important; margin-left: 0 !important; }
    .cart-button-below-search { margin: 8px 0 !important; }
}

.site-search { position: relative; }
.room-search-dropdown {
    position: absolute; top: 100%; left: 0; right: 0;
    background: #fff; border: 1px solid #ddd; border-top: none;
    border-radius: 0 0 6px 6px; z-index: 1000; display: none;
    box-shadow: 0 6px 20px rgba(0,0,0,0.15); max-height: 320px; overflow-y: auto;
}
.room-search-item {
    display: flex; justify-content: space-between; align-items: center;
    padding: 12px 16px; color: #43454b; text-decoration: none;
    border-bottom: 1px solid #f3f3f3; font-size: 14px; transition: background 0.15s;
}
.room-search-item:last-child { border-bottom: none; }
.room-search-item:hover, .room-search-item.active { background: #e8f0fe; }
.room-search-match { color: #1565C0; font-weight: 600; }
.room-search-price { color: #888; font-size: 13px; white-space: nowrap; }
');
}, 20);


// Add retro games to footer fun section
add_action("storefront_footer", function() {
    echo '<div style="text-align:center;padding:10px;font-size:14px;color:#fff;">';
    echo '<a href="/tetris/" style="color:#0ff;text-decoration:none;margin:0 10px;font-weight:bold;">&#127918; Tetris</a>';
    echo '<a href="/cart-game/" style="color:#ff6b6b;text-decoration:none;margin:0 10px;font-weight:bold;">&#128722; Cart Catcher</a>';
    echo '</div>';
}, 30);


// Auto logout after 15 minutes of inactivity
add_action("wp_footer", function() {
    if (!is_user_logged_in()) return;
    $logout_url = wp_logout_url(home_url());
    $timeout = 900;
    $warn_at = 840;
    ?>
    <div id="inactivity-modal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.6);z-index:99999;align-items:center;justify-content:center;">
        <div style="background:#fff;padding:30px 40px;border-radius:8px;text-align:center;max-width:400px;box-shadow:0 10px 40px rgba(0,0,0,0.3);">
            <h3 style="margin-top:0;color:#333;">Session Expiring</h3>
            <p style="color:#666;margin:12px 0;">You will be logged out in <strong id="inactivity-countdown">60</strong> seconds due to inactivity.</p>
            <button id="inactivity-stay" style="background:#1565C0;color:#fff;border:none;padding:10px 24px;border-radius:4px;font-size:14px;cursor:pointer;font-weight:600;">Stay Logged In</button>
        </div>
    </div>
    <script>
    (function() {
        var timeout = <?php echo $timeout; ?>;
        var warnAt = <?php echo $warn_at; ?>;
        var logoutUrl = <?php echo json_encode($logout_url); ?>;
        var timer, warnTimer, countdownInterval;
        var modal = document.getElementById("inactivity-modal");
        var countdownEl = document.getElementById("inactivity-countdown");

        function resetTimer() {
            clearTimeout(timer);
            clearTimeout(warnTimer);
            clearInterval(countdownInterval);
            if (modal) modal.style.display = "none";
            timer = setTimeout(doLogout, timeout * 1000);
            warnTimer = setTimeout(showWarning, warnAt * 1000);
        }

        function showWarning() {
            if (!modal) return;
            modal.style.display = "flex";
            var remaining = timeout - warnAt;
            if (countdownEl) countdownEl.textContent = remaining;
            countdownInterval = setInterval(function() {
                remaining--;
                if (remaining <= 0) { clearInterval(countdownInterval); return; }
                if (countdownEl) countdownEl.textContent = remaining;
            }, 1000);
            document.getElementById("inactivity-stay").onclick = function() {
                resetTimer();
            };
        }

        function doLogout() {
            window.location.href = logoutUrl;
        }

        document.addEventListener("mousemove", resetTimer);
        document.addEventListener("keypress", resetTimer);
        document.addEventListener("click", resetTimer);
        document.addEventListener("scroll", resetTimer);
        document.addEventListener("touchstart", resetTimer);

        resetTimer();
    })();
    </script>
    <?php
});


// Gemini AI Chatbot
add_action("wp_ajax_aus_chat", "aus_chat_ajax");
add_action("wp_ajax_nopriv_aus_chat", "aus_chat_ajax");
function aus_chat_ajax() {
    $message = sanitize_text_field($_POST["message"]);
    if (empty($message)) wp_send_json_error("Empty message");

    $api_key = get_option("aus_groq_api_key", "");
    if (empty($api_key)) wp_send_json_error("API key not configured");

    $system_prompt = "You are a helpful assistant for AUS shop, an e-commerce website. The site has the following pages: Home, Rooms, Cart, My Account. Products can be filtered by a variety of filters like price, ratings, name, etc. The checkout has 3 steps: Rooms, cart, payment. If users ask about anything unrelated to the shop, politely redirect them. Keep responses short and friendly, under 3 sentences.";

    $context = "You help customers on AUS, a hotel booking site (" . home_url() . ").\n";
    $context .= "Pages: Home, Rooms (" . home_url("/rooms/") . "), Cart, Checkout, My Account.\n";
    $context .= "Coupon: 'allfree' = 100% off.\n";
    $context .= "Rooms page has filters: property type, star rating, price slider, facilities, room features, meals, policies.\n";
    $context .= "Checkout: pick room -> cart -> payment.\n";
    $context .= "Redirect off-topic questions. Keep answers under 3 sentences.\n\n";

    $products = wc_get_products(array("limit" => 11, "orderby" => "title", "order" => "ASC"));
    foreach ($products as $p) {
        $context .= $p->get_name() . " - $" . $p->get_price() . "/mo\n";
    }

    $body = json_encode(array(
        "model" => "llama-3.1-8b-instant",
        "messages" => array(
            array("role" => "system", "content" => $system_prompt),
            array("role" => "user", "content" => $context . "\n\nCustomer: " . $message),
        ),
        "temperature" => 0.7,
        "max_tokens" => 200,
    ));

    $response = wp_remote_post(
        "https://api.groq.com/openai/v1/chat/completions",
        array(
            "headers" => array(
                "Content-Type" => "application/json",
                "Authorization" => "Bearer " . $api_key,
            ),
            "body" => $body,
            "timeout" => 30,
        )
    );

    if (is_wp_error($response)) {
        wp_send_json_error("Connection error. Please try again.");
    }

    $http_code = wp_remote_retrieve_response_code($response);
    $resp_body = wp_remote_retrieve_body($response);

    if ($http_code !== 200) {
        $err_data = json_decode($resp_body, true);
        $err_msg = isset($err_data["error"]["message"]) ? $err_data["error"]["message"] : "HTTP " . $http_code;
        wp_send_json_error("API error: " . $err_msg);
    }

    $data = json_decode($resp_body, true);
    $text = isset($data["choices"][0]["message"]["content"])
        ? trim($data["choices"][0]["message"]["content"])
        : "Sorry, I couldn't process that. Try again!";

    wp_send_json_success(array("reply" => $text));
}

// Chat widget on homepage
add_action("wp_footer", function() {
    if (!is_front_page()) return;
    ?>
    <div id="aus-chat-widget" style="position:fixed;bottom:20px;right:20px;z-index:9999;font-family:inherit;">
        <button id="aus-chat-toggle" style="background:#7f54b3;color:#fff;border:none;border-radius:50px;padding:12px 20px;font-size:14px;font-weight:600;cursor:pointer;box-shadow:0 4px 15px rgba(0,0,0,0.2);display:flex;align-items:center;gap:8px;">
            <span style="font-size:18px;">&#128172;</span> Need help?
        </button>
        <div id="aus-chat-box" style="display:none;position:absolute;bottom:60px;right:0;width:340px;background:#fff;border-radius:12px;box-shadow:0 8px 30px rgba(0,0,0,0.15);overflow:hidden;">
            <div style="background:#7f54b3;color:#fff;padding:14px 16px;font-weight:600;font-size:14px;display:flex;justify-content:space-between;align-items:center;">
                AUS Assistant
                <button id="aus-chat-close" style="background:none;border:none;color:#fff;font-size:18px;cursor:pointer;line-height:1;">&times;</button>
            </div>
            <div id="aus-chat-messages" style="height:300px;overflow-y:auto;padding:12px;display:flex;flex-direction:column;gap:8px;">
                <div style="background:#f0e6f6;padding:10px 14px;border-radius:12px 12px 12px 0;font-size:13px;max-width:85%;align-self:flex-start;">Hi! I'm the AUS assistant. How can I help you today?</div>
            </div>
            <div style="display:flex;padding:10px;border-top:1px solid #eee;gap:8px;">
                <input id="aus-chat-input" type="text" placeholder="Type your message..." style="flex:1;padding:10px 12px;border:1px solid #ddd;border-radius:20px;font-size:13px;outline:none;">
                <button id="aus-chat-send" style="background:#7f54b3;color:#fff;border:none;border-radius:50%;width:38px;height:38px;cursor:pointer;font-size:16px;flex-shrink:0;">&#10148;</button>
            </div>
        </div>
    </div>
    <script>
    (function() {
        var box = document.getElementById("aus-chat-box");
        var toggle = document.getElementById("aus-chat-toggle");
        var close = document.getElementById("aus-chat-close");
        var input = document.getElementById("aus-chat-input");
        var send = document.getElementById("aus-chat-send");
        var messages = document.getElementById("aus-chat-messages");

        toggle.onclick = function() { box.style.display = "block"; toggle.style.display = "none"; input.focus(); };
        close.onclick = function() { box.style.display = "none"; toggle.style.display = "flex"; };

        function addMessage(text, isUser) {
            var div = document.createElement("div");
            div.style.cssText = "padding:10px 14px;border-radius:12px;font-size:13px;max-width:85%;word-wrap:break-word;";
            if (isUser) {
                div.style.cssText += "background:#7f54b3;color:#fff;align-self:flex-end;border-radius:12px 12px 0 12px;";
            } else {
                div.style.cssText += "background:#f0e6f6;color:#333;align-self:flex-start;border-radius:12px 12px 12px 0;";
            }
            div.textContent = text;
            messages.appendChild(div);
            messages.scrollTop = messages.scrollHeight;
        }

        function sendMessage() {
            var text = input.value.trim();
            if (!text) return;
            addMessage(text, true);
            input.value = "";
            var loading = document.createElement("div");
            loading.style.cssText = "align-self:flex-start;padding:8px;font-size:12px;color:#999;";
            loading.textContent = "Typing...";
            messages.appendChild(loading);
            messages.scrollTop = messages.scrollHeight;

            var xhr = new XMLHttpRequest();
            xhr.open("POST", "<?php echo admin_url("admin-ajax.php"); ?>", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.onload = function() {
                loading.remove();
                try {
                    var resp = JSON.parse(xhr.responseText);
                    if (resp.success) {
                        addMessage(resp.data.reply, false);
                    } else {
                        addMessage("Oops, something went wrong. Try again!", false);
                    }
                } catch(e) {
                    addMessage("Error connecting. Please try later.", false);
                }
            };
            xhr.onerror = function() { loading.remove(); addMessage("Network error. Check your connection.", false); };
            xhr.send("action=aus_chat&message=" + encodeURIComponent(text));
        }

        send.onclick = sendMessage;
        input.onkeydown = function(e) { if (e.key === "Enter") sendMessage(); };
    })();
    </script>
    <?php
});
