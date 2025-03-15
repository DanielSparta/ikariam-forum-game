<?php
session_start();
require 'db.php'; // Database connection

global $pdo; // Ensure PDO is globally accessible

// CSRF Token Generation and Validation
function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Generate a new CSRF token
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(string $csrfToken): bool {
    return isset($_SESSION['csrf_token']) && $_SESSION['csrf_token'] === $csrfToken;
}

$csrf_error = ""; // Initialize the $csrf_error variable

// Ensure required tables exist
function ensureTablesExist(PDO $pdo): void {
    // Create users table if not exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(255) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        token VARCHAR(64) NOT NULL,
        score INT DEFAULT 0,
        answered_questions TEXT DEFAULT '',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Create questions table if not exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS questions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        question TEXT NOT NULL,
        answer VARCHAR(255) NOT NULL
    )");

    // Create log table if not exists
    $pdo->exec("CREATE TABLE IF NOT EXISTS logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        error_message TEXT NULL,
        error_type VARCHAR(50) NULL,
        user_ip VARCHAR(255) NOT NULL,
        user_agent TEXT NOT NULL,
        user_id INT NULL,
        username VARCHAR(255) NULL,
        timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
}

// Log actions, errors, warnings, or any other information into the 'logs' table
function logAction(PDO $pdo, string $error_message, string $error_type, ?int $user_id = null, ?string $username = null): void {
    $user_ip = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    
    $stmt = $pdo->prepare("INSERT INTO logs (error_message, error_type, user_ip, user_agent, user_id, username) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$error_message, $error_type, $user_ip, $user_agent, $user_id, $username]);
}

// Custom error handler
function customErrorHandler($error_level, $error_message, $error_file, $error_line) {
    global $pdo;
    $error_type = '';
    
    // Determine error type
    switch ($error_level) {
        case E_ERROR:
            $error_type = 'Fatal Error';
            break;
        case E_WARNING:
            $error_type = 'Warning';
            break;
        case E_NOTICE:
            $error_type = 'Notice';
            break;
        default:
            $error_type = 'Unknown Error';
    }

    // Log the error
    logAction($pdo, $error_message, $error_type, $_SESSION['user_id'] ?? null, $_SESSION['username'] ?? null);
}

// Set the custom error handler
set_error_handler('customErrorHandler');

// Fatal error handler
function handleFatalError() {
    global $pdo;
    $error = error_get_last();
    
    // If a fatal error occurred
    if ($error !== NULL && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
        logAction($pdo, $error['message'], 'Fatal Error', $_SESSION['user_id'] ?? null, $_SESSION['username'] ?? null);
    }
}

// Register the shutdown function to handle fatal errors
register_shutdown_function('handleFatalError');

// Log SQL queries (for debugging purposes)
function logQuery(PDO $pdo, $query, $params = []) {
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $query_log = $stmt->queryString;
    
    // Log the query execution
    logAction($pdo, $query_log, 'SQL Query', $_SESSION['user_id'] ?? null, $_SESSION['username'] ?? null);
    return $stmt;
}

// Log exceptions
function logException(PDO $pdo, Exception $e) {
    logAction($pdo, $e->getMessage(), 'Exception', $_SESSION['user_id'] ?? null, $_SESSION['username'] ?? null);
}

// Fetch scoreboard data
function fetchScoreboard(PDO $pdo): array {
    $stmt = logQuery($pdo, "SELECT username, score FROM users ORDER BY score DESC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

// Retrieve authenticated user data
function getAuthenticatedUser(PDO $pdo, ?string $authToken): ?array {
    if (!$authToken) return null;

    $stmt = logQuery($pdo, "SELECT id, username, score, answered_questions FROM users WHERE token = ?", [$authToken]);
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

// Fetch a random unanswered question
function fetchRandomQuestion(PDO $pdo, array $answeredQuestions): ?array {
    $query = "SELECT id, question, answer FROM questions";
    
    if (!empty($answeredQuestions)) {
        $placeholders = implode(',', array_fill(0, count($answeredQuestions), '?'));
        $query .= " WHERE id NOT IN ($placeholders)";
    }

    $query .= " ORDER BY RAND() LIMIT 1";
    
    $stmt = logQuery($pdo, $query, $answeredQuestions);
    
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    session_destroy();
    session_start();
    $_SESSION['stage'] = 'welcome_page';
    $_SESSION['score'] = 0;
}

$authToken = $_COOKIE['auth_token'] ?? null;
$user = getAuthenticatedUser($pdo, $authToken);
$isAuthenticated = (bool) $user;
$_SESSION['score'] = $user['score'] ?? 0;

$scoreboardArray = fetchScoreboard($pdo);

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Token Verification
    if (!isset($_POST['csrf_token']) || !is_string($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
        $csrf_error = "❌ שגיאה: csrf token לא תואם, אנא נסה שוב. ❌";
    }

    if (empty($csrf_error)) {
        // Continue with the rest of your logic if no CSRF error

        if (!$isAuthenticated) {
            header('Location: login.php');
            exit;
        }
        
        $_SESSION['stage'] = $_SESSION['stage'] ?? 'welcome_page';
        $Message = "";
        switch ($_SESSION['stage']) {
            case 'start':
                $_SESSION['stage'] = 'question';
                $answeredQuestions = json_decode($user['answered_questions'] ?? '[]', true) ?: [];
                $_SESSION['question'] = fetchRandomQuestion($pdo, $answeredQuestions);
            
                // If no more questions are available, move to the final stage
                if (!$_SESSION['question']) {
                    $_SESSION['stage'] = 'final';
                }
                break;
            
            case 'question':
                if (isset($_POST['answer']) && isset($_SESSION['question'])) {
                    $currentQuestion = $_SESSION['question'];
                    $isCorrect = strtolower(trim($_POST['answer'])) === strtolower(trim($currentQuestion['answer']));

                    if ($isCorrect) {
                        $_SESSION['score'] += 10;
                        $answeredQuestions = json_decode($user['answered_questions'] ?? '[]', true) ?: [];

                        if (!in_array($currentQuestion['id'], $answeredQuestions)) {
                            $answeredQuestions[] = $currentQuestion['id'];

                            // Update the database
                            $stmt = logQuery($pdo, "UPDATE users SET score = score + 10, answered_questions = ? WHERE token = ?", [json_encode($answeredQuestions), $authToken]);
                        }

                        // Log action
                        logAction($pdo, "Correct answer to question {$currentQuestion['id']}", 'info', $user['id'], $user['username']);

                        $Message = "✅ תשובה נכונה";

                        // Fetch next question only if the answer was correct
                        $_SESSION['question'] = fetchRandomQuestion($pdo, $answeredQuestions);

                        // If no more questions are available, move to the final stage
                        if (!$_SESSION['question']) {
                            $_SESSION['stage'] = 'final';
                        }
                    } else {
                        $Message = "❌ תשובה שגויה"; // Do not change the question
                    }
                }

                // If user presses "replace question" button
                if (isset($_POST['replace_question'])) {
                    $answeredQuestions = json_decode($user['answered_questions'] ?? '[]', true) ?: [];
                    $_SESSION['question'] = fetchRandomQuestion($pdo, $answeredQuestions);

                    // If no more questions are available, move to the final stage
                    if (!$_SESSION['question']) {
                        $_SESSION['stage'] = 'final';
                    }
                }
                break;

            case 'final':
                if (isset($_POST['player'])) {
                    $stmt = logQuery($pdo, "UPDATE users SET score = score + ? WHERE username = ?", [$_SESSION['score'], htmlspecialchars($_POST['player'])]);
                }
                $_SESSION['stage'] = 'finish';
                break;
            
            case 'finish':
                // Log final score
                logAction($pdo, "User completed the quiz with a score of {$_SESSION['score']}", 'info', $user['id'], $user['username']);
                break;
            
            default:
                break;
        }
    }
}
?>








<!DOCTYPE html>
<html lang="he">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>חדר בריחה - פורום איקרים ישראל</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Heebo:wght@300;700&display=swap" rel="stylesheet">
    <style>
        /* Reset Box-sizing and Margin/Padding */
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        /* General Body Styles */
        body {
            background: url('/background.png') no-repeat center center fixed;
            background-size: cover;
            font-family: 'Heebo', sans-serif;
            text-align: center;
            color: #fff;
            margin: 0;
            padding: 0;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
        }

        /* Container for Centering Content */
        .container {
            position: relative;
            width: 100%;
            max-width: 600px;
            background: rgba(0, 0, 0, 0.85);
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(10px);
            margin: 15px;
        }

        /* Title Styling */
        h1 {
            font-size: 2.5rem;
            margin-bottom: 20px;
            font-weight: 700;
        }

        /* Question Box Styling */
        .question-box {
            background: rgba(255, 255, 255, 0.1);
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: bold;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.7);
        }

        /* Input and Button Styling */
        input, button {
            width: 100%;
            padding: 12px;
            margin: 8px 0;
            border-radius: 6px;
            border: none;
            font-size: 1rem;
        }

        input {
            background: #fff;
            color: #000;
            text-align: center;
            font-weight: bold;
        }

        button {
            background: linear-gradient(45deg, #ff9800, #ff5722);
            color: #fff;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease-in-out;
            box-shadow: 0 4px 10px rgba(255, 152, 0, 0.5);
        }

        button:hover {
            transform: scale(1.05);
            background: linear-gradient(45deg, #e68900, #e64a19);
        }

        /* Scoreboard Styling */
        .scoreboard {
            margin-top: 20px;
            background: rgba(255, 255, 255, 0.1);
            padding: 15px;
            border-radius: 10px;
        }

        .scoreboard h2 {
            font-size: 1.25rem;
            color: #f4a100;
            font-weight: bold;
        }

        .scoreboard ul {
            list-style: none;
            padding: 0;
        }

        .player {
            font-size: 1rem;
            font-weight: bold;
        }

        /* Error and Correct Messages Styling */
        .error {
            color: #ff4d4d;
            font-size: 1.25rem;
            font-weight: bold;
        }

        .correct {
            color: #27ae60;
            font-size: 1.25rem;
            font-weight: bold;
        }

        /* Image Styling for Mobile */
        img {
            width: 100%;
            max-width: 90%;
            height: auto;
            display: block;
            margin: 0 auto 20px;
        }

        /* Mobile Responsive Styles */
        @media (max-width: 768px) {
            h1 {
                font-size: 5vw;
                margin-bottom: 15px;
            }

            .question-box {
                font-size: 5vw;
                padding: 15px;
                margin-bottom: 15px;
            }

            .scoreboard h2 {
                font-size: 5vw;
            }

            .player {
                font-size: 4vw;
            }

            input, button {
                font-size: 1rem;
            }

            .container {
                padding: 20px;
                margin: 15px;
            }
        }

        @media (max-width: 480px) {
            h1 {
                font-size: 7vw;
                margin-bottom: 10px;
            }

            .question-box {
                font-size: 6vw;
                padding: 12px;
                margin-bottom: 12px;
            }

            .scoreboard h2 {
                font-size: 6vw;
            }

            .player {
                font-size: 5vw;
            }

            button {
                font-size: 1rem;
                padding: 12px;
            }

            input {
                font-size: 1rem;
                padding: 12px;
            }
        }
    </style>
</head>

<body>
    <div dir="rtl" class="container">
        <img src="https://i.gyazo.com/2d655af08821f93ca232d3e338cae1c0.png" alt="חדר בריחה - פורום איקרים ישראל">

        <?php if ($_SESSION['stage'] === 'welcome_page'): ?>
            <h1>אתגר חדר הבריחה</h1>
            <p> ברוכים הבאים לחדר הבריחה של פורום איקרים! כאן תמצאו חידות ושאלות, חלקן קשורות למשחק, וחלקן לא. החידות לא בהכרח מצריכות ידע קודם במשחק! המטרה שלכם היא לענות על כמה שיותר חידות ושאלות, ובכך להשיג כמות ניקוד גבוהה יותר משל שאר המשתתפים! מי יתגלה כפותר החידות הטוב ביותר?</p>
            <p><b>🏆 3 השחקנים המובילים יזכו בקופוני אמברוסיה שווים! 🏆</b></p>
            <br>
            <hr>
            <?php if ($isAuthenticated): ?>
                <?php $_SESSION['stage'] = "start"; ?>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                    <button name="login" type="submit">🔓 התחל</button>
                    <hr>
                </form>
            <?php else: ?>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                    <button type="submit">🔑 הרשם / התחבר</button>
                    <hr>
                    <br><p>על מנת להשתתף, עלייך להצטרף ללוח המשתתפים תחילה.<br>לחץ על הכפתור "הרשם/התחבר" והתחל לעלות בניקוד!</p>
                </form>
            <?php endif; ?>
        <?php elseif ($_SESSION['stage'] === 'question' && isset($_SESSION['question'])): ?>
            <h1>💡 חידה 💡</h1>
            <div class="question-box">
                <?= htmlspecialchars($_SESSION['question']['question']) ?>
            </div>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                <input type="text" name="answer" required>
                <button type="submit">📩 בדוק</button>
            </form>

            <!-- Button for replacing the question -->
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                <button type="submit" name="replace_question">🔄 החלף שאלה</button>
            </form>

            <?php if (!empty($Message)): ?>
                <p class="<?= str_starts_with($Message, '✅') ? 'correct' : 'error' ?>"> <?= $Message ?> </p>
            <?php endif; ?>

        <?php elseif ($_SESSION['stage'] === 'final'): ?>
            <?php $_SESSION['stage'] = "welcome_page"; ?>
            <h1>🎉 הודעת מערכת</h1>
            <p>כל הכבוד! ענית על כל השאלות הקיימות במאגר. המשך להתאמן, כי שאלות חדשות יתווספו בהמשך!</p>
            <p>💎 ניקודך: <strong><?= $_SESSION['score'] ?></strong></p>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                <button type="submit">🔄 חזור למסך הבית</button>
            </form>
        <?php endif; ?>

        <?php if (!empty($csrf_error)): ?>
            <p class="error"><?= $csrf_error ?></p>
            <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                </form>
        <?php endif; ?>

        <?php if (!empty($scoreboardArray)): ?> 
            <div class="scoreboard">
                <h2>🏆 לוח משתתפים 🏆</h2>
                <ul>
                    <?php foreach ($scoreboardArray as $player): ?>
                        <li class="player">👑 <?= htmlspecialchars($player['username']) ?> - <strong><?= htmlspecialchars($player['score']) ?></strong></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</body>


</html>
