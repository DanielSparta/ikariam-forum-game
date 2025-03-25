<?php
require  'functions.php';

// Initialize game stage
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['stage'] = 'welcome_page';
}
if ($isAuthenticated)
    $_SESSION['show_popup'] = '';

#Feature for upcoming hangman event
if (isset($_GET['gethangmandebug']) && $user['is_admin'])
    $_SESSION['HangmanEventAvailable'] = '';

include 'index.view.php';

?>