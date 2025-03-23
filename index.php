<?php
require  'functions.php';

// Initialize game stage
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['stage'] = 'welcome_page';
}
if ($isAuthenticated)
    $_SESSION['show_popup'] = '';

include 'index.view.php';

?>