<?php
/**
 * Append a "Fun" widget block to the Storefront footer area
 * containing a link to the Tetris game page.
 *
 * @action storefront_footer
 */
add_action("storefront_footer", function() {
    echo "<div class=\"block footer-widget col-1\">";
    echo "<h2 style=\"color:#333;font-size:14px;margin-bottom:5px;\">Fun</h2>";
    echo "<ul style=\"list-style:none;padding:0;\">";
    echo "<li><a href=\"/tetris/\" style=\"color:#727272;font-size:13px;\">tetris!</a></li>";
    echo "</ul></div>";
}, 50);
