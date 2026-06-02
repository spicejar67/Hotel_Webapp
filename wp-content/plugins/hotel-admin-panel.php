<?php
/*
Plugin Name: Hotel Admin Panel
Description: Admin UI for users, rooms, prices, images, deletion
*/
add_shortcode("hotel_admin_panel", function() {
    if (!is_user_logged_in() || get_current_user_id() != 1) return "<p>Access denied.</p>";
    $nonce = wp_create_nonce("ha_nonce"); ob_start();

    if (isset($_GET["ha"]) && $_GET["ha"]==="block" && wp_verify_nonce($_GET["_n"],"ha_nonce")) {
        $u=new WP_User(intval($_GET["uid"])); $u->set_role("blocked"); echo '<div style="background:#4caf50;color:#fff;padding:10px;border-radius:4px;margin-bottom:15px;">User blocked.</div>';
    }
    if (isset($_GET["ha"]) && $_GET["ha"]==="unblock" && wp_verify_nonce($_GET["_n"],"ha_nonce")) {
        $u=new WP_User(intval($_GET["uid"])); $u->set_role("customer"); echo '<div style="background:#4caf50;color:#fff;padding:10px;border-radius:4px;margin-bottom:15px;">User unblocked.</div>';
    }
    if (isset($_GET["ha"]) && $_GET["ha"]==="delete_room" && wp_verify_nonce($_GET["_n"],"ha_nonce")) {
        $n=get_the_title(intval($_GET["pid"])); wp_delete_post(intval($_GET["pid"]),true); echo '<div style="background:#ff9800;color:#fff;padding:10px;border-radius:4px;margin-bottom:15px;">Room deleted: '.$n.'</div>';
    }
    if (isset($_POST["ha_action"]) && $_POST["ha_action"]==="update_price" && wp_verify_nonce($_POST["_n"],"ha_nonce")) {
        update_post_meta(intval($_POST["pid"]),"_price",floatval($_POST["price"])); update_post_meta(intval($_POST["pid"]),"_regular_price",floatval($_POST["price"])); echo '<div style="background:#4caf50;color:#fff;padding:10px;border-radius:4px;margin-bottom:15px;">Price updated.</div>';
    }
    if (isset($_POST["ha_action"]) && $_POST["ha_action"]==="add_room" && wp_verify_nonce($_POST["_n"],"ha_nonce")) {
        $n=sanitize_text_field($_POST["room_name"]); $pr=floatval($_POST["room_price"]);
        $pid=wp_insert_post(["post_title"=>$n,"post_type"=>"product","post_status"=>"publish","post_content"=>sanitize_textarea_field($_POST["room_desc"]),"post_excerpt"=>sanitize_text_field($_POST["room_short"])." per month."]);
        if($pid&&!is_wp_error($pid)){wp_set_object_terms($pid,"simple","product_type");update_post_meta($pid,"_price",$pr);update_post_meta($pid,"_regular_price",$pr);update_post_meta($pid,"_virtual","yes");update_post_meta($pid,"_visibility","visible");
            if(!empty($_FILES["room_image"]["name"])&&$_FILES["room_image"]["error"]===0){$f=$_FILES["room_image"];$ud=wp_upload_dir();$hd=$ud["basedir"]."/hotel-pics/";if(!is_dir($hd))mkdir($hd,0755,true);$fp=$hd.sanitize_title($n).".".pathinfo($f["name"],PATHINFO_EXTENSION);if(move_uploaded_file($f["tmp_name"],$fp)){require_once(ABSPATH."wp-admin/includes/image.php");$ai=wp_insert_attachment(["post_mime_type"=>wp_check_filetype(basename($fp))["type"],"post_title"=>$n,"post_status"=>"inherit"],$fp,$pid);if(!is_wp_error($ai)){wp_update_attachment_metadata($ai,wp_generate_attachment_metadata($ai,$fp));set_post_thumbnail($pid,$ai);}}}
            echo '<div style="background:#4caf50;color:#fff;padding:10px;border-radius:4px;margin-bottom:15px;">Room added: '.$n.'</div>';
        }
    }

    $tab=isset($_GET["ha_tab"])?$_GET["ha_tab"]:"users";
    echo '<style>.ha-panel{max-width:900px;margin:0 auto}.ha-tabs{display:flex;gap:5px;margin-bottom:20px}.ha-tabs a{padding:10px 20px;background:#333;color:#fff;text-decoration:none;border-radius:4px 4px 0 0}.ha-tabs a.active{background:#1565C0}.ha-table{width:100%;border-collapse:collapse;background:#fff;border-radius:8px;overflow:hidden;box-shadow:0 2px 10px rgba(0,0,0,0.06)}.ha-table th{background:#1565C0;color:#fff;padding:12px;text-align:left;font-size:13px}.ha-table td{padding:10px 12px;border-bottom:1px solid #eee;font-size:13px}.ha-table tr:hover td{background:#f5f8ff}.ha-btn{padding:6px 14px;border-radius:4px;border:none;cursor:pointer;font-size:12px;font-weight:600;text-decoration:none;display:inline-block}.ha-btn-block{background:#f44336;color:#fff}.ha-btn-unblock{background:#4caf50;color:#fff}.ha-btn-save{background:#1565C0;color:#fff}.ha-btn-delete{background:#d32f2f;color:#fff}.ha-btn-add{background:#1565C0;color:#fff;padding:10px 24px;font-size:14px}.ha-price-input{width:80px;padding:6px;border:1px solid #ddd;border-radius:4px;text-align:right}.ha-form-group{margin-bottom:15px}.ha-form-group label{display:block;font-weight:600;margin-bottom:4px;color:#333}.ha-form-group input,.ha-form-group textarea{width:100%;padding:10px;border:1px solid #ddd;border-radius:4px}.ha-badge{display:inline-block;padding:2px 8px;border-radius:3px;font-size:11px;color:#fff}.ha-badge-blocked{background:#f44336}.ha-badge-customer{background:#4caf50}.ha-badge-admin{background:#ff9800}</style>';
    echo '<div class="ha-panel"><h2 style="color:#1565C0;margin-bottom:15px;">Hotel Admin Panel</h2><div class="ha-tabs"><a href="?ha_tab=users" class="'.($tab=="users"?"active":"").'">Users</a> <a href="?ha_tab=rooms" class="'.($tab=="rooms"?"active":"").'">Rooms & Prices</a> <a href="?ha_tab=add" class="'.($tab=="add"?"active":"").'">Add Room</a></div>';

    if($tab==="users"){echo '<table class="ha-table"><tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Action</th></tr>';
        foreach(get_users() as $u){$r=implode(", ",$u->roles);$b=in_array("blocked",$u->roles)?"blocked":(in_array("administrator",$u->roles)?"admin":"customer");
            echo "<tr><td>{$u->ID}</td><td>{$u->user_login}</td><td>{$u->user_email}</td><td><span class=\"ha-badge ha-badge-$b\">$r</span></td><td>";
            if($u->ID!=1){echo in_array("blocked",$u->roles)?"<a href=\"?ha=unblock&uid={$u->ID}&_n=$nonce\" class=\"ha-btn ha-btn-unblock\">Unblock</a>":"<a href=\"?ha=block&uid={$u->ID}&_n=$nonce\" class=\"ha-btn ha-btn-block\">Block</a>";}
            echo "</td></tr>";
        }echo '</table>';}

    if($tab==="rooms"){echo '<table class="ha-table"><tr><th>ID</th><th>Name</th><th>Price</th><th>New Price</th><th></th><th></th></tr>';
        foreach(wc_get_products(["limit"=>50,"orderby"=>"title","order"=>"ASC"]) as $p){$id=$p->get_id();$n=$p->get_name();$pr=$p->get_price();
            echo "<tr><td>$id</td><td><a href=\"".get_permalink($id)."\">$n</a></td><td>$".number_format($pr,2)."/month</td>
            <td><form method=\"post\" style=\"display:inline;\"><input type=\"hidden\" name=\"ha_action\" value=\"update_price\"><input type=\"hidden\" name=\"pid\" value=\"$id\"><input type=\"hidden\" name=\"_n\" value=\"$nonce\">$<input type=\"number\" name=\"price\" step=\"0.01\" min=\"0\" class=\"ha-price-input\" value=\"$pr\"> /month</td>
            <td><button type=\"submit\" class=\"ha-btn ha-btn-save\">Update</button></form></td>
            <td><a href=\"?ha=delete_room&pid=$id&_n=$nonce\" class=\"ha-btn ha-btn-delete\" onclick=\"return confirm('Delete $n?')\">Delete</a></td></tr>";
        }echo '</table>';}

    if($tab==="add"){echo '
    <form method="post" enctype="multipart/form-data" style="background:#fff;padding:25px;border-radius:8px;box-shadow:0 2px 10px rgba(0,0,0,0.06);">
    <input type="hidden" name="ha_action" value="add_room"><input type="hidden" name="_n" value="'.$nonce.'">
    <div class="ha-form-group"><label>Room Image (optional)</label><input type="file" name="room_image" accept="image/*"></div>
    <div class="ha-form-group"><label>Room Name</label><input type="text" name="room_name" required></div>
    <div class="ha-form-group"><label>Price per month ($)</label><input type="number" name="room_price" step="0.01" min="0" required></div>
    <div class="ha-form-group"><label>Short Description</label><input type="text" name="room_short" required></div>
    <div class="ha-form-group"><label>Full Description</label><textarea name="room_desc" rows="4"></textarea></div>
    <button type="submit" class="ha-btn ha-btn-add">Add Room</button></form>';}

    echo '</div>'; return ob_get_clean();
});

add_filter("wp_nav_menu_items",function($i,$a){if(is_user_logged_in()&&get_current_user_id()==1)$i.="<li><a href=\"/hotel-admin/\">Admin Panel</a></li>";return $i;},10,2);
add_action("init",function(){if(!get_role("blocked"))add_role("blocked","Blocked",["read"=>false]);});
add_filter("authenticate",function($u,$un,$pw){if($u instanceof WP_User&&in_array("blocked",$u->roles))return new WP_Error("blocked","Account blocked.");return $u;},99,3);
