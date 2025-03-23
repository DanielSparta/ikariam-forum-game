<?php
session_start();
require 'db.php'; // Database connection

// CSRF Token Generation and Validation
function generateCsrfToken(): string {
    return $_SESSION['csrf_token'] ??= bin2hex(random_bytes(32));
}

function verifyCsrfToken(string|array $csrfToken): bool {
    if (is_array($csrfToken)) {
        error_log("CSRF token received as an array: " . json_encode($csrfToken));
        return false;
    }
    $OldCSRFToken = $_SESSION['csrf_token'];
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return ($OldCSRFToken) === $csrfToken;
}


// Ensure required tables exist
function ensureTablesExist(PDO $pdo): void {
    $tables = [
        "users" => [
            "id INT AUTO_INCREMENT PRIMARY KEY",
            "username VARCHAR(255) UNIQUE",
            "password VARCHAR(255)",
            "user_note VARCHAR(255)",
            "token VARCHAR(64)",
            "score INT DEFAULT 0",
            "answered_questions TEXT",
            "invited_by TEXT",
            "created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
            "is_admin TINYINT(1) DEFAULT 0"
        ],
        "questions" => [
            "id INT AUTO_INCREMENT PRIMARY KEY",
            "question TEXT",
            "answer VARCHAR(255)",
            "answers INT DEFAULT 0"
        ],
        "logs" => [
            "id INT AUTO_INCREMENT PRIMARY KEY",
            "error_message TEXT",
            "error_type VARCHAR(50)",
            "user_ip VARCHAR(255)",
            "user_agent TEXT",
            "referer TEXT",
            "user_id INT",
            "username VARCHAR(255)",
            "timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP"
        ],
        "broadcast_message" =>
        [
            "id INT AUTO_INCREMENT PRIMARY KEY",
            "message TEXT",
            "timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
        ]
    ];

    foreach ($tables as $table => $columns) {
        // Ensure the table exists
        $createTableQuery = "CREATE TABLE IF NOT EXISTS $table (" . implode(", ", $columns) . ");";
        try {
            $pdo->exec($createTableQuery);
        } catch (Exception $e) {
            logAction($pdo, "Database error (table creation): " . $e->getMessage(), 'Database Error');
        }

        // Check for missing columns and add them
        $existingColumns = [];
        try {
            $stmt = $pdo->query("SHOW COLUMNS FROM $table");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $existingColumns[] = $row['Field'];
            }
        } catch (Exception $e) {
            logAction($pdo, "Database error (fetch columns): " . $e->getMessage(), 'Database Error');
            continue;
        }

        foreach ($columns as $columnDefinition) {
            preg_match('/^(\w+)/', $columnDefinition, $matches);
            if (!in_array($matches[1], $existingColumns)) {
                try {
                    $pdo->exec("ALTER TABLE $table ADD COLUMN $columnDefinition;");
                } catch (Exception $e) {
                    logAction($pdo, "Database error (alter table $table): " . $e->getMessage(), 'Database Error');
                }
            }
        }
    }

    // Ensure the 'DanielSparta' user exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
    $stmt->execute(['DanielSparta']);
    $userCount = $stmt->fetchColumn();

    if ($userCount == 0) {
        $adminPassword = '$2y$10$CCdYXEDGO2SFuT7OGe6j9uF8.VuAzJU2CCd1nJoAQOqt89Sj5BmA2'; // Hashed password
        $adminToken = bin2hex(random_bytes(32)); // Unique token
        $stmt = $pdo->prepare("INSERT INTO users (username, password, token, is_admin, user_note) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(['DanielSparta', $adminPassword, $adminToken, 1, 'Admin account created']);
    }
}

ensureTablesExist($pdo);

/**
 * Rate Limiting for 10 seconds between requests
 */
function isRequestAllowed(): bool {
    $ip = $_SERVER['REMOTE_ADDR'];
    $currentTime = time();
    
    if (!isset($_SESSION['last_request_time']) || $currentTime - $_SESSION['last_request_time'] >= 20) {
        $_SESSION['last_request_time'] = $currentTime;
    } else {
        return false;
    }
    
    if (!isset($_SESSION['request_ips'][$ip]) || $currentTime - $_SESSION['request_ips'][$ip] >= 20) {
        $_SESSION['request_ips'][$ip] = $currentTime;
        return true;
    }
    
    return false;
}

// Log actions and errors
function logAction(PDO $pdo, string $message, string $type, ?int $user_id = null, ?string $username = null): void {
    $stmt = $pdo->prepare("INSERT INTO logs (error_message, error_type, user_ip, user_agent, referer, user_id, username) 
                           VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $message, 
        $type, 
        $_SERVER['REMOTE_ADDR'] ?? 'Unknown IP', 
        $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown User-Agent',
        $_SERVER['HTTP_REFERER'] ?? 'Unknown Referer', // Capture Referer header
        $user_id, 
        $username
    ]);
}

// Custom error handler
function customErrorHandler($level, $message, $file, $line) {
    global $pdo, $user;
    
    $errorType = match ($level) {
        E_ERROR, E_USER_ERROR => 'Fatal Error',
        E_WARNING, E_USER_WARNING => 'Warning',
        E_NOTICE, E_USER_NOTICE => 'Notice',
        E_DEPRECATED, E_USER_DEPRECATED => 'Deprecated',
        E_STRICT => 'Strict Standards',
        default => 'Unknown Error',
    };

    logAction($pdo, "$errorType: $message in $file on line $line", $errorType, $user['id'] ?? null, $user['username'] ?? null);
}

// Set custom error handler
set_error_handler('customErrorHandler');

// Fatal error handler
function handleFatalError() {
    global $pdo, $user;
    if ($error = error_get_last()) {
        if (in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
            logAction(
                $pdo, 
                "Fatal Error: {$error['message']} in {$error['file']} on line {$error['line']}", 
                'Fatal Error', 
                $user['id'] ?? null, 
                $_SESSION['username'] ?? 'not logged'
            );
        }
    }
}

// Register shutdown function to catch fatal errors
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

// Add CSRF token to session if not set
$csrf_token = generateCsrfToken();

// Ensure user is authenticated
$authToken = $_COOKIE['auth_token'] ?? null;
$user = getAuthenticatedUser($pdo, $authToken);
$isAuthenticated = (bool) $user;
$_SESSION['username'] = $user['username'] ?? 'not logged';
$_SESSION['user_id'] = $user['id'] ?? '0';
logAction($pdo, "User entered site", 'info', $_SESSION['user_id'], $_SESSION['username']);
$scoreboardArray = fetchScoreboard($pdo);

$csrf_error = "";
$Message = "";

foreach ($_REQUEST as $key => $value) {
    if (is_array($value)) {
        //Need to fix that this message is not going into user output
        $Message = "❌ אינך יכול להכניס מערך להערת משתמש";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $Message === "") {
    if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        $csrf_error = "csrf token לא תואם, אנא נסה שוב. <br>לחץ על הקישור הבא על מנת לסדר את הבעיה: <a href='https://ikaforum.servegame.com/index.php'>לחץ עליי</a>✅";
    }

    if (empty($csrf_error)) {
        $_SESSION['stage'] = $_SESSION['stage'] ?? 'welcome_page';
        $answeredQuestions = json_decode($user['answered_questions'] ?? '[]', true) ?: [];

        if (isset($_POST['admin_panel']) && $user['is_admin']) {
            $_SESSION['stage'] = 'admin_panel';
        }

        if (isset($_POST['settings']))
            $_SESSION['stage'] = "settings";

        if (isset($_POST['set_homepage']))
            header("Location: index.php");

        if (isset($_POST['usertext'])) {
            if(is_array($_POST['usertext']))
            {
                $Message = "❌ אינך יכול להכניס מערך להערת משתמש";
                logAction($pdo, "User note array try", 'info', $user['id'], $_SESSION['username']);
            }
            elseif (strlen($_POST['usertext']) > 73) {
                $Message = "❌ הערה ארוכה מדי (מקסימום 73 תווים)";
                logAction($pdo, "User note too big try", 'info', $user['id'], $_SESSION['username']);
            } else {
                $userNote = trim($_POST['usertext']);
                $stmt = $pdo->prepare("UPDATE users SET user_note = ? WHERE username = ?");
                // Ensure $userNote is never null
                $userNote = $userNote ?? '';
                $stmt->execute([htmlspecialchars($userNote), $_SESSION['username']]);
                $Message = "✅ ההערה עודכנה בהצלחה";
                logAction($pdo, "User note updated", 'info', $user['id'], $_SESSION['username']);
                
            }

        }

        if (isset($_POST['usrname'], $_POST['psswrd'])) {
            $username = $_POST['usrname'];
            $password = $_POST['psswrd'];

            if (!isRequestAllowed()) {
                $Message = "❌ שגיאה: יש להמתין 20 שניות בין כל ניסיון.";
                logAction($pdo, "Login Request rate limit exceeded for: {$username}", "info");
            } else {
                $stmt = $pdo->prepare("SELECT id, username, password FROM users WHERE username = ?");
                $stmt->execute([$username]);
                $user = $stmt->fetch();

                if ($user) {
                    //then login
                    if (password_verify($password, $user['password'])) {
                        $newToken = bin2hex(random_bytes(32));
                        $updateStmt = $pdo->prepare("UPDATE users SET token = ? WHERE id = ?");
                        $updateStmt->execute([$newToken, $user['id']]);

                        setcookie("auth_token", $newToken, [
                            "expires" => time() + (86400 * 30),
                            "path" => "/",
                            "secure" => true,
                            "httponly" => true,
                            "samesite" => "Strict"
                        ]);
                        
                        $_SESSION['is_registred'] = true;
                        $_SESSION['username'] = $user['username'];
                        logAction($pdo, "User logged in.", "info", $user['id'], $user['username']);
                        header("Location: login.php");
                        exit;
                    } else {
                        $Message = "❌ שגיאה: סיסמה שגויה.";
                        logAction($pdo, "Incorrect password attempt for {$username}.", "error");
                    }
                } else {
                    //then register
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $token = bin2hex(random_bytes(32));
                    $answeredQuestions = '[]';
                    $user_note = "";
                    $data = "none";
                    $insertStmt = $pdo->prepare("INSERT INTO users (username, password, user_note, token, answered_questions, invited_by) VALUES (?, ?, ?, ?, ?, ?)");
                    if ($insertStmt->execute([$username, $hashedPassword, $user_note, $token, $answeredQuestions, $data])) {
                        logAction($pdo, "New user registered: {$username}.", "info");
                        $_SESSION['is_registred'] = true;
                        $_SESSION['username'] = $username;
                        setcookie("auth_token", $token, [
                            "expires" => time() + (86400 * 30),
                            "path" => "/",
                            "secure" => true,
                            "httponly" => true,
                            "samesite" => "Strict"
                        ]);
                        $_SESSION['show_invited_by'] = "";
                        header("Location: login.php");
                        exit;
                    } else {
                        $Message = "❌ שגיאה: שם המשתמש כבר קיים.";
                        logAction($pdo, "Failed registration attempt: {$username}.", "error");
                    }
                }
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
                    if(is_array($_POST['answer']))
                    {
                        $Message = "אינך יכול להגיש תשובה כמערך";
                        logAction($pdo, "Question Request rate limit exceeded for: {$_SESSION['username']}", "info");
                    }
                    elseif (!isRequestAllowed()) {
                        $Message = "❌ שגיאה: יש להמתין 20 שניות בין כל ניסיון.";
                        logAction($pdo, "Question Request rate limit exceeded for: {$_SESSION['username']}", "info");
                    }
                    elseif (strlen($_POST['answer']) > 18) {
                        $Message = "❌ אינך יכול להכניס תשובה שגדולה מ18 תווים.";
                        logAction($pdo, "Question > 18 charecters answer try: {$_SESSION['username']}", "info");
                    }
                    else
                    {
                        $currentQuestion = $_SESSION['question'];
                        $isCorrect = stripos(trim($_POST['answer']), trim($currentQuestion['answer'])) !== false;

                        if ($isCorrect) {
                            if (!in_array($currentQuestion['id'], $answeredQuestions)) {
                                $answeredQuestions[] = $currentQuestion['id'];
                                $stmt = $pdo->prepare("UPDATE users SET score = score + 10, answered_questions = ? WHERE token = ?");
                                $stmt->execute([json_encode($answeredQuestions), $authToken]);
                                logAction($pdo, "Correct answer to question {$currentQuestion['id']}", 'info', $user['id'], $user['username']);
                                $Message = "✅ תשובה נכונה";
                                //new bonus points feature
                                $stmt = $pdo->query("SELECT answers FROM questions WHERE id=" . $currentQuestion['id']);
                                $answers = (int) $stmt->fetchColumn();

                                $positions = [
                                    0 => "✅ תשובה נכונה - אתה הראשון שפתר את השאלה הזאת! ולכן אתה מקבל בונוס נקודה אחת",
                                ];

                                if (isset($positions[$answers])) {
                                    $Message = $positions[$answers];
                                    $stmt = $pdo->prepare("UPDATE users SET score = score + 1, answered_questions = ? WHERE token = ?");
                                    $stmt->execute([json_encode($answeredQuestions), $authToken]);
                                }
                                //feature that shows the users how many users answers that question
                                $stmt = $pdo->query("UPDATE questions SET answers=answers+1 WHERE id=" . $currentQuestion['id']);
                                $_SESSION['question'] = fetchRandomQuestion($pdo, $answeredQuestions);
                                $_SESSION['stage'] = $_SESSION['question'] ? 'question' : 'final';
                                $scoreboardArray = fetchScoreboard($pdo);
                            }
                            else
                                $Message = "❌ כבר ענית על שאלה זו";
                        } else {
                            $Message = "❌ תשובה שגויה";
                        }
                    }
                }
                break;

            case 'admin_panel':
                if ($user['is_admin']) {
                    logAction($pdo, "Admin panel enter", 'info', $user['id'], $user['username']);
                    $stmt = $pdo->query("SELECT id, question, answer FROM questions");
                    $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    // Delete question
                    if (isset($_POST['delete_question'], $_POST['question_id'])) {
                        if(is_array($_POST['delete_question']) || is_array($_POST['question_id']))
                        {
                            $Message = "❌ אינך יכול להכניס מערך כשאלה";
                            logAction($pdo, "Question delete as ARRAY try" . $_POST['question_id'], 'info', $_SESSION['user_id'], $_SESSION['username']);
                        }
                        else {
                            $questionId = (int)$_POST['question_id'];
                            $stmt = $pdo->prepare("DELETE FROM questions WHERE id = ?");
                            $stmt->execute([$questionId]);
                            $Message = "✅ השאלה נמחקה בהצלחה.";
                            logAction($pdo, "Question ID " . $questionId . " Deleted", 'info', $_SESSION['user_id'], $_SESSION['username']);
                        }
                    }

                    // Add new question
                    if (isset($_POST['add_question'], $_POST['new_question'], $_POST['new_answer'])) {
                        if(is_array($_POST['add_question']) || is_array($_POST['new_question']) || is_array($_POST['new_answer']))
                        {
                            $Message = "❌ אינך יכול להכניס מערך כשאלה";
                            logAction($pdo, "Question add as ARRAY try", 'info', $_SESSION['user_id'], $_SESSION['username']);
                        }
                        else
                        {
                            $newQuestion = trim($_POST['new_question']);
                            $newAnswer = trim($_POST['new_answer']);

                            if (strlen($newQuestion) > 0 && strlen($newAnswer) > 0) {
                                $stmt = $pdo->prepare("INSERT INTO questions (question, answer, answers) VALUES (?, ?, ?)");
                                $stmt->execute([$newQuestion, $newAnswer, 0]);
                                logAction($pdo, "Question Added: " . $newQuestion, 'info', $_SESSION['user_id'], $_SESSION['username']);
                                $Message = "✅ השאלה נוספה בהצלחה.";
                            } else {
                                $Message = "❌ אנא ספק שאלה וגם תשובה.";
                            }
                        }
                    }

                    // Edit existing question
                    if (isset($_POST['edit_question'], $_POST['question_id'], $_POST['updated_question'], $_POST['updated_answer'])) {
                        if(is_array($_POST['edit_question']) || is_array($_POST['question_id']) || is_array($_POST['updated_question']) || is_array($_POST['updated_answer']))
                        {
                            $Message = "❌ אינך יכול להכניס מערך";
                            logAction($pdo, "Question edit as ARRAY try", 'info', $_SESSION['user_id'], $_SESSION['username']);
                        }
                        else
                        {
                            $questionId = (int)$_POST['question_id'];
                            $updatedQuestion = trim($_POST['updated_question']);
                            $updatedAnswer = trim($_POST['updated_answer']);
                            logAction($pdo, "Question ID " . $questionId . " Edited", 'info', $_SESSION['user_id'], $_SESSION['username']);

                            if (strlen($updatedQuestion) > 0 && strlen($updatedAnswer) > 0) {
                                $stmt = $pdo->prepare("UPDATE questions SET question = ?, answer = ? WHERE id = ?");
                                $stmt->execute([$updatedQuestion, $updatedAnswer, $questionId]);
                                $Message = "✅ השאלה עודכנה בהצלחה";
                            } else {
                                $Message = "❌ אנא רשום גם שאלה וגם תשובה על מנת לעדכן";
                            }
                        }
                    }

                    // Fetch all users
                    $stmt = $pdo->query("SELECT id, username, user_note, invited_by, score, is_admin FROM users");
                    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    // Delete user
                    if (isset($_POST['delete_user'], $_POST['user_id'])) {
                        if(is_array($_POST['delete_user']) || is_array($_POST['user_id']))
                        {
                            $Message = "❌ אינך יכול להכניס מערך ";
                            logAction($pdo, "Deleting user as ARRAY detected" . $_POST['user_id'], 'info', $_SESSION['user_id'], $_SESSION['username']);
                        }
                        else {
                            $userId = (int)$_POST['user_id'];
                            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                            $stmt->execute([$userId]);
                            $Message = "✅ המשתמש נמחק בהצלחה";
                            logAction($pdo, "User ID " . $userId . " Deleted", 'info', $_SESSION['user_id'], $_SESSION['username']);
                        }
                    }

                    // Edit user details
                    if (isset($_POST['edit_user'], $_POST['user_id'], $_POST['updated_username'], $_POST['updated_user_note'], $_POST['updated_score'], $_POST['updated_is_admin'])) {
                        if(is_array($_POST['edit_user']) || is_array($_POST['user_id']) || is_array($_POST['updated_username']) || is_array($_POST['updated_user_note']) || is_array($_POST['updated_score']) || is_array($_POST['updated_is_admin']))
                        {
                            $Message = "❌ אינך יכול להכניס מערך ";
                            logAction($pdo, "Edit user as ARRAY detected" . $_POST['user_id'], 'info', $_SESSION['user_id'], $_SESSION['username']);
                        }
                        else {
                            $userId = (int)$_POST['user_id'];
                            $updatedUsername = trim($_POST['updated_username']);
                            $updatedUserNote = trim($_POST['updated_user_note']);
                            $updatedScore = (int)$_POST['updated_score'];
                            $updatedIsAdmin = isset($_POST['updated_is_admin']) ? 1 : 0;

                            if (strlen($updatedUsername) > 0) {
                                $stmt = $pdo->prepare("UPDATE users SET username = ?, user_note = ?, score = ?, is_admin = ? WHERE id = ?");
                                $stmt->execute([$updatedUsername, $updatedUserNote, $updatedScore, $updatedIsAdmin, $userId]);
                                logAction($pdo, "User ID " . $userId . " updated", 'info', $_SESSION['user_id'], $_SESSION['username']);
                                $Message = "✅ המשתמש עודכן בהצלחה";
                            } else {
                                $Message = "❌ קלט שגוי, אנא בדוק שנית מה הכנסת";
                            }
                        }
                    }

                    $logs_per_page = 200; // Number of logs per page
                    $page = 1;
                    if (isset($_POST['page']) && is_array($_POST['page'])) {
                        logAction($pdo, "Load logs as ARRAY detected", 'info', $_SESSION['user_id'], $_SESSION['username']);
                    } else {
                        $page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
                    }

                    // Calculate the offse
                    $offset = ($page - 1) * $logs_per_page;
                    // Fetch logs from the database
                    $query = "SELECT error_type, error_message, user_ip, username, timestamp FROM logs ORDER BY id DESC LIMIT :limit OFFSET :offset";
                    $stmt = $pdo->prepare($query);
                    $stmt->bindParam(':limit', $logs_per_page, PDO::PARAM_INT);
                    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
                    $stmt->execute();
                    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    // Calculate total number of pages
                    $stmt = $pdo->query("SELECT COUNT(*) FROM logs");
                    $totalLogs = $stmt->fetchColumn();
                    $totalPages = ceil($totalLogs / $logs_per_page);

                    if(isset($_POST['delete_broadcast_message']))
                    {
                        $stmt = $pdo->prepare("DELETE FROM broadcast_message WHERE id = ?");
                        $stmt->execute([$_POST['broadcast_message_id']]);
                    }

                    if(isset($_POST['update_broadcast_message']))
                    {
                        $stmt = $pdo->prepare("UPDATE broadcast_message SET message = ? WHERE id = ?");
                        $stmt->execute([$_POST['updated_broadcast_message'], $_POST['broadcast_message_id']]);
                    }

                    if(isset($_POST['add_broadcast_message']))
                    {
                        $stmt = $pdo->prepare("INSERT INTO broadcast_message (message) VALUES (?)");
                        $stmt->execute([$_POST['new_broadcast_message']]);
                    }
                }
                else{
                    $Message = "❌ אין לך הרשאה לביצוע פעולה זו.";
                }
                break;
        }
    }
}


?>