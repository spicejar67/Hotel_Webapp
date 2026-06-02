<?php
// Add tetris link to footer widgets
add_action("storefront_footer", function() {
    echo "<div class=\"block footer-widget col-1\">";
    echo "<h2 style=\"color:#333;font-size:14px;margin-bottom:5px;\">Fun</h2>";
    echo "<ul style=\"list-style:none;padding:0;\">";
    echo "<li><a href=\"/tetris/\" style=\"color:#727272;font-size:13px;\">tetris!</a></li>";
    echo "</ul></div>";
}, 50);
