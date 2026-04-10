<?php

function load_top() {
    global $page_css;   // bắt buộc phải có

    require('widget/top.php');

    if (!empty($page_css)) {
        $version = '?v=' . time(); // Force reload CSS
        echo '<link rel="stylesheet" href="assets/css/' . $page_css . $version . '">';
    }
    
    // Close head and open body
    echo '<link rel="preconnect" href="https://fonts.googleapis.com">';
    echo '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>';
    echo '<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">';
    echo '<link href="https://cdn.jsdelivr.net/npm/remixicon@4.0.0/fonts/remixicon.css" rel="stylesheet">';
    echo '</head>';
    echo '<body>';
}

function load_header() {
    require('widget/header.php');
}

function load_footer() {
    require('widget/footer.php');
    echo '</body>';
    echo '</html>';
}

?>
