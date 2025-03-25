<?php
require 'lib/functions.php';


if (isset($_SESSION['username'], $_POST['invited_by'])) {
    if (is_array($_POST['invited_by'])) {
        $Message = "❌ שגיאה: אינך יכול להכניס מערך.";
        logAction($pdo, "Invited_by username array try", "error");
    } elseif (!empty($_POST['invited_by']) && strlen($_POST['invited_by']) > 30) {
        $Message = "❌ שגיאה: שם המשתמש של חברך לא יכול להיות ארוך מ-30 תווים.";
        logAction($pdo, "Invited_by username too long: {$_POST['invited_by']}", "error");
    } else {
        $updateStmt = $pdo->prepare("UPDATE users SET invited_by = ? WHERE username = ?");
        $updateStmt->execute([htmlspecialchars($_POST['invited_by']), $_SESSION['username']]);
    }
}

?>

<?php include 'views/login.view.php'; ?>
