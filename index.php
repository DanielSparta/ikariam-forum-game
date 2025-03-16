<?php
session_start();
require 'db.php'; // Database connection

// CSRF Token Generation and Validation
function generateCsrfToken(): string {
    return $_SESSION['csrf_token'] ??= bin2hex(random_bytes(32));
}

function verifyCsrfToken(string $csrfToken): bool {
    return ($_SESSION['csrf_token'] ?? '') === $csrfToken;
}

// Ensure required tables exist
function ensureTablesExist(PDO $pdo): void {
    $queries = [
        "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    user_note VARCHAR(255) NOT NULL,
    token VARCHAR(64) NOT NULL,
    score INT DEFAULT 0,
    answered_questions TEXT, 
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_admin TINYINT(1) DEFAULT 0 
);",
        "CREATE TABLE IF NOT EXISTS questions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            question TEXT NOT NULL,
            answer VARCHAR(255) NOT NULL
        )",
        "CREATE TABLE IF NOT EXISTS logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                error_message TEXT NULL,
                error_type VARCHAR(50) NULL,
                user_ip VARCHAR(255) NOT NULL,
                user_agent TEXT NOT NULL,
                user_id INT NULL,
                username VARCHAR(255) NULL,
                timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )"
    ];

    foreach ($queries as $query) {
        $pdo->exec($query);
    }

    // Check if the 'DanielSparta' user exists, if not, create it
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $stmt->execute(['DanielSparta']);
    $userCount = $stmt->fetchColumn();

    if ($userCount == 0) {
        // Insert the default admin user 'DanielSparta'
        $adminPassword = '$2y$10$CCdYXEDGO2SFuT7OGe6j9uF8.VuAzJU2CCd1nJoAQOqt89Sj5BmA2'; // The hashed password
        $adminToken = bin2hex(random_bytes(32)); // Generate a unique token for the user
        $stmt = $pdo->prepare("INSERT INTO users (username, password, token, is_admin, user_note) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(['DanielSparta', $adminPassword, $adminToken, 1, 'Admin account created']);
    }
}
ensureTablesExist($pdo);

// Log actions and errors
function logAction(PDO $pdo, string $message, string $type, ?int $user_id = null, ?string $username = null): void {
    $stmt = $pdo->prepare("INSERT INTO logs (error_message, error_type, user_ip, user_agent, user_id, username) 
                           VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $message, 
        $type, 
        $_SERVER['REMOTE_ADDR'], 
        $_SERVER['HTTP_USER_AGENT'], 
        $user_id, 
        $username
    ]);
}

// Custom error handler
function customErrorHandler($level, $message, $file, $line) {
    global $pdo, $user;
    $user = $user ?? ['id' => null, 'username' => null];  // Default empty user data
    $errorType = match ($level) {
        E_ERROR => 'Fatal Error',
        E_WARNING => 'Warning',
        E_NOTICE => 'Notice',
        default => 'Unknown Error',
    };
    logAction($pdo, $message, $errorType, $user['id'] ?? null, $user['username'] ?? null);
}
set_error_handler('customErrorHandler');

// Fatal error handler
function handleFatalError() {
    global $pdo, $user;
    if ($error = error_get_last()) {
        if (in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
            logAction($pdo, $error['message'], 'Fatal Error', $user['id'] ?? null, $user['username'] ?? null);
        }
    }
}
register_shutdown_function('handleFatalError');

// Fetch scoreboard data
function fetchScoreboard(PDO $pdo): array {
    $stmt = $pdo->query("SELECT username, score, user_note FROM users ORDER BY score DESC");
    return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
}

// Retrieve authenticated user data
function getAuthenticatedUser(PDO $pdo, ?string $authToken): ?array {
    if (!$authToken) return null;
    $stmt = $pdo->prepare("SELECT id, username, score, answered_questions, is_admin FROM users WHERE token = ?");
    $stmt->execute([$authToken]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    return $user ?: null;
}

// Fetch a random unanswered question
function fetchRandomQuestion(PDO $pdo, array $answeredQuestions): ?array {
    if (empty($answeredQuestions)) {
        $query = "SELECT id, question, answer FROM questions ORDER BY RAND() LIMIT 1";
        $stmt = $pdo->query($query);
    } else {
        $placeholders = implode(',', array_fill(0, count($answeredQuestions), '?'));
        $query = "SELECT id, question, answer FROM questions WHERE id NOT IN ($placeholders) ORDER BY RAND() LIMIT 1";
        $stmt = $pdo->prepare($query);
        $stmt->execute($answeredQuestions);
    }
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

// Initialize game stage
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['stage'] = 'welcome_page';
    $_SESSION['score'] = 0;
}

// Add CSRF token to session if not set
$csrf_token = generateCsrfToken();

// Ensure user is authenticated
$authToken = $_COOKIE['auth_token'] ?? null;
$user = getAuthenticatedUser($pdo, $authToken);
$isAuthenticated = (bool) $user;
$_SESSION['score'] = $user['score'] ?? 0;
$_SESSION['username'] = $user['username'];
$scoreboardArray = fetchScoreboard($pdo);

// CSRF Token Error
$csrf_error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        $csrf_error = "❌ שגיאה: csrf token לא תואם, אנא נסה שוב. ❌";
    }

    if (empty($csrf_error)) {
        if (!$isAuthenticated) {
            header('Location: login.php');
            exit;
        }

        $_SESSION['stage'] = $_SESSION['stage'] ?? 'welcome_page';
        $answeredQuestions = json_decode($user['answered_questions'] ?? '[]', true) ?: [];
        $Message = "";

        if (isset($_POST['admin_panel']) && $user['is_admin']) {
            $_SESSION['stage'] = 'admin_panel';
        }

        if (isset($_POST['settings']))
            $_SESSION['stage'] = "settings";
        if (isset($_POST['set_homepage']))
            header("Location: index.php");

        if (isset($_POST['usertext'])) {
            $userNote = trim($_POST['usertext']);
            if (strlen($userNote) > 73) {
                $Message = "❌ הערה ארוכה מדי (מקסימום 25 תווים)";
            } else {
                $stmt = $pdo->prepare("UPDATE users SET user_note = ? WHERE username = ?");
                // Ensure $userNote is never null
                $stmt->execute([htmlspecialchars($userNote ?: ''), $user['username']]);
                $Message = "✅ ההערה עודכנה בהצלחה";
                logAction($pdo, "User note updated", 'info', $user['id'], $user['username']);
                
            }

        }
        if(isset($_POST['replace_question']))
            $_SESSION['stage'] = "start";

        switch ($_SESSION['stage']) {
            case 'start':
                $_SESSION['stage'] = 'question';
                $_SESSION['question'] = fetchRandomQuestion($pdo, $answeredQuestions);
                $_SESSION['stage'] = $_SESSION['question'] ? 'question' : 'final';
                break;

            case 'question':
                if (isset($_POST['answer'], $_SESSION['question'])) {
                    $currentQuestion = $_SESSION['question'];
                    $isCorrect = stripos(trim($_POST['answer']), trim($currentQuestion['answer'])) !== false;

                    if ($isCorrect) {
                        $_SESSION['score'] += 10;

                        if (!in_array($currentQuestion['id'], $answeredQuestions)) {
                            $answeredQuestions[] = $currentQuestion['id'];
                            $stmt = $pdo->prepare("UPDATE users SET score = score + 10, answered_questions = ? WHERE token = ?");
                            $stmt->execute([json_encode($answeredQuestions), $authToken]);
                        }

                        logAction($pdo, "Correct answer to question {$currentQuestion['id']}", 'info', $user['id'], $user['username']);
                        $Message = "✅ תשובה נכונה";
                        $_SESSION['question'] = fetchRandomQuestion($pdo, $answeredQuestions);
                        $_SESSION['stage'] = $_SESSION['question'] ? 'question' : 'final';
                    } else {
                        $Message = "❌ תשובה שגויה";
                    }
                }
                break;

            case 'admin_panel':
                logAction($pdo, "Admin panel enter", 'info', $user['id'], $user['username']);
                $stmt = $pdo->query("SELECT id, question, answer FROM questions");
                $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Delete question
                if (isset($_POST['delete_question'], $_POST['question_id'])) {
                    $questionId = (int)$_POST['question_id'];
                    $stmt = $pdo->prepare("DELETE FROM questions WHERE id = ?");
                    $stmt->execute([$questionId]);
                    $Message = "✅ השאלה נמחקה בהצלחה.";
                }

                // Add new question
                if (isset($_POST['add_question'], $_POST['new_question'], $_POST['new_answer'])) {
                    $newQuestion = trim($_POST['new_question']);
                    $newAnswer = trim($_POST['new_answer']);

                    if (strlen($newQuestion) > 0 && strlen($newAnswer) > 0) {
                        $stmt = $pdo->prepare("INSERT INTO questions (question, answer) VALUES (?, ?)");
                        $stmt->execute([$newQuestion, $newAnswer]);
                        $Message = "✅ השאלה נוספה בהצלחה.";
                    } else {
                        $Message = "❌ אנא ספק שאלה וגם תשובה.";
                    }
                }

                // Edit existing question
                if (isset($_POST['edit_question'], $_POST['question_id'], $_POST['updated_question'], $_POST['updated_answer'])) {
                    $questionId = (int)$_POST['question_id'];
                    $updatedQuestion = trim($_POST['updated_question']);
                    $updatedAnswer = trim($_POST['updated_answer']);

                    if (strlen($updatedQuestion) > 0 && strlen($updatedAnswer) > 0) {
                        $stmt = $pdo->prepare("UPDATE questions SET question = ?, answer = ? WHERE id = ?");
                        $stmt->execute([$updatedQuestion, $updatedAnswer, $questionId]);
                        $Message = "✅ Question updated successfully.";
                    } else {
                        $Message = "❌ Please provide both updated question and answer.";
                    }
                }

                // Fetch all users
                $stmt = $pdo->query("SELECT id, username, user_note, score, is_admin FROM users");
                $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Delete user
                if (isset($_POST['delete_user'], $_POST['user_id'])) {
                    $userId = (int)$_POST['user_id'];
                    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                    $stmt->execute([$userId]);
                    $Message = "✅ User deleted successfully.";
                }

                // Edit user details
                if (isset($_POST['edit_user'], $_POST['user_id'], $_POST['updated_username'], $_POST['updated_user_note'], $_POST['updated_score'], $_POST['updated_is_admin'])) {
                    $userId = (int)$_POST['user_id'];
                    $updatedUsername = trim($_POST['updated_username']);
                    $updatedUserNote = trim($_POST['updated_user_note']);
                    $updatedScore = (int)$_POST['updated_score'];
                    $updatedIsAdmin = isset($_POST['updated_is_admin']) ? 1 : 0;

                    if (strlen($updatedUsername) > 0) {
                        $stmt = $pdo->prepare("UPDATE users SET username = ?, user_note = ?, score = ?, is_admin = ? WHERE id = ?");
                        $stmt->execute([$updatedUsername, $updatedUserNote, $updatedScore, $updatedIsAdmin, $userId]);
                        $Message = "✅ User updated successfully.";
                    } else {
                        $Message = "❌ Invalid input. Please check the values.";
                    }
                }

                $logs_per_page = 500; // Number of logs per page
                $page = isset($_POST['page']) ? (int)$_POST['page'] : 1; // Get the current page (default to 1 if not set)
                $offset = ($page - 1) * $logs_per_page; // Calculate the offset

                // Fetch logs from the database
                $query = "SELECT * FROM logs ORDER BY timestamp DESC LIMIT :limit OFFSET :offset";
                $stmt = $pdo->prepare($query);
                $stmt->bindParam(':limit', $logs_per_page, PDO::PARAM_INT);
                $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
                $stmt->execute();
                $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Calculate total number of pages
                $stmt = $pdo->query("SELECT COUNT(*) FROM logs");
                $totalLogs = $stmt->fetchColumn();
                $totalPages = ceil($totalLogs / $logs_per_page);
                
                break;
        }
    }
}
?>

<?php include 'index.view.php'; ?>