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
            answered_questions TEXT DEFAULT '',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )",
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
    $stmt = $pdo->prepare("SELECT id, username, score, answered_questions FROM users WHERE token = ?");
    $stmt->execute([$authToken]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    return $user ?: null; // Ensure `false` is converted to `null`
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

$authToken = $_COOKIE['auth_token'] ?? null;
$user = getAuthenticatedUser($pdo, $authToken);
$isAuthenticated = (bool) $user;
$_SESSION['score'] = $user['score'] ?? 0;
$scoreboardArray = fetchScoreboard($pdo);

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

        if(isset($_POST['settings']))
            $_SESSION['stage'] = "settings";
        if(isset($_POST['set_homepage']))
            header("Location: index.php");
            if (isset($_POST['usertext'])) {
                $userNote = trim($_POST['usertext']); // Remove leading/trailing spaces
                
                // Enforce max length of 25 characters
                if (mb_strlen($userNote) > 73) {
                    $Message = "❌ הערה ארוכה מדי (מקסימום 25 תווים)";
                } 
                // If valid, update the database
                else {
                    $stmt = $pdo->prepare("UPDATE users SET user_note = ? WHERE username = ?");
                    $stmt->execute([htmlspecialchars($userNote), $user['username']]);
                    $Message = "✅ ההערה עודכנה בהצלחה";
                }
            }            

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

                if (isset($_POST['replace_question'])) {
                    $_SESSION['question'] = fetchRandomQuestion($pdo, $answeredQuestions);
                    $_SESSION['stage'] = $_SESSION['question'] ? 'question' : 'final';
                }
                break;

            case 'final':
                if (isset($_POST['player'])) {
                    $stmt = $pdo->prepare("UPDATE users SET score = score + ? WHERE username = ?");
                    $stmt->execute([$_SESSION['score'], htmlspecialchars($_POST['player'])]);
                }
                $_SESSION['stage'] = 'finish';
                break;

            case 'finish':
                logAction($pdo, "User completed the quiz with a score of {$_SESSION['score']}", 'info', $user['id'], $user['username']);
                break;

            case 'settings':
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

        /* Fade-in Animation */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Body Styling */
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
            animation: fadeIn 1s ease-in-out;
        }

        /* Container Styling */
        .container {
            width: 100%;
            max-width: 600px;
            background: rgba(0, 0, 0, 0.75);
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.6);
            backdrop-filter: blur(15px);
            margin: 15px;
            animation: fadeIn 1s ease-in-out;
        }

        /* Title Styling */
        h1 {
            font-size: 2.5rem;
            margin-bottom: 20px;
            font-weight: 700;
            text-shadow: 2px 2px 5px rgba(255, 255, 255, 0.2);
        }

        .loggedIn {
            color: #27ae60;
            font-size: 30px;
            font-weight: bold;
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
            margin: 10px 0;
            border-radius: 8px;
            border: none;
            font-size: 1rem;
            transition: 0.3s ease-in-out;
        }

        input {
            background: #fff;
            color: #000;
            text-align: center;
            font-weight: bold;
            outline: none;
        }

        input:focus {
            box-shadow: 0 0 10px rgba(255, 152, 0, 0.7);
        }

        button {
            background: linear-gradient(45deg, #ff9800, #ff5722);
            color: #fff;
            font-size: 1.1rem;
            font-weight: bold;
            cursor: pointer;
            box-shadow: 0 4px 10px rgba(255, 152, 0, 0.5);
            transition: 0.3s ease-in-out;
            position: relative;
            overflow: hidden;
        }

        button::before {
            content: "";
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: rgba(255, 255, 255, 0.2);
            transition: 0.3s ease-in-out;
        }

        button:hover {
            transform: scale(1.05);
            background: linear-gradient(45deg, #e68900, #e64a19);
        }

        button:hover::before {
            left: 100%;
        }

        /* Scoreboard Styling */
        .scoreboard {
            margin-top: 20px;
            background: rgba(255, 255, 255, 0.1);
            padding: 15px;
            border-radius: 10px;
            animation: fadeIn 1.2s ease-in-out;
        }

        .scoreboard h2 {
            font-size: 1.5rem;
            color: #f4a100;
            font-weight: bold;
            text-shadow: 0 0 10px rgba(255, 165, 0, 0.7);
        }

        .scoreboard ul {
            list-style: none;
            padding: 0;
        }

        .player {
            font-size: 1rem;
            font-weight: bold;
            transition: transform 0.3s ease-in-out;
        }

        .player:hover {
            transform: scale(1.1);
            text-shadow: 0 0 10px rgba(255, 255, 255, 0.7);
        }

        /* Error and Correct Messages Styling */
        .error {
            color: #ff4d4d;
            font-size: 1.25rem;
            font-weight: bold;
            animation: fadeIn 1s ease-in-out;
        }

        .correct {
            color: #27ae60;
            font-size: 1.25rem;
            font-weight: bold;
            animation: fadeIn 1s ease-in-out;
        }

        /* Mobile Responsive Styles */
        @media (max-width: 768px) {
            h1 {
                font-size: 5vw;
            }

            input, button {
                font-size: 1rem;
            }

            .container {
                padding: 20px;
            }
        }

        @media (max-width: 480px) {
            h1 {
                font-size: 7vw;
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
    <img src="https://i.gyazo.com/2d655af08821f93ca232d3e338cae1c0.png" style="max-width: 90%; height: auto;">
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
                </form>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                    <button name="settings" type="submit">⚙️ הגדרות</button>
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

        <?php if ($_SESSION['stage'] === 'settings'): ?>
            <h1>מסך ההגדרות</h1>
            <hr>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                <input type="text" name="usertext" placeholder="רשום פתק משתמש שיישמר ליד שמכם בלוח המשתתפים">
                <button type="submit">שמור פתק משתמש</button>
            </form>
            <hr>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                <button name="set_homepage" type="submit">🔄 חזור למסך הבית</button>
            </form>
            <hr>
            <?php if (!empty($Message)): ?>
                <p class="<?= str_starts_with($Message, '✅') ? 'correct' : 'error' ?>"> <?= $Message ?> </p>
                <?php endif; ?>
            <?php endif; ?>

        <?php if (!empty($csrf_error)): ?>
            <p class="error"><?= $csrf_error ?></p>
            <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                </form>
        <?php endif; ?>

        <?php if (!empty($scoreboardArray)): ?> 
            <style>
                /* Gold Shine Animation */
                @keyframes shine {
                    0% { color: #ffd700; text-shadow: 0 0 5px #ffcc00, 0 0 10px #ffcc00; }
                    50% { color: #fff4b2; text-shadow: 0 0 10px #ffcc00, 0 0 20px #ffcc00; }
                    100% { color: #ffd700; text-shadow: 0 0 5px #ffcc00, 0 0 10px #ffcc00; }
                }

                /* Sliding Comment Animation */
                @keyframes slideIn {
                    from { opacity: 0; transform: translateY(5px); }
                    to { opacity: 1; transform: translateY(0); }
                }

                .gold-shine {
                    color: #ffd700 !important;
                    font-weight: bold;
                    text-shadow: 0 0 5px #ffcc00, 0 0 10px #ffcc00;
                    animation: shine 1.5s infinite alternate;
                }

                /* Style for each player entry */
                .player {
                    margin-bottom: 6px; /* Added spacing between users */
                }

                /* Comment Style - Small Space & Fades in */
                .user-note {
                    font-style: italic;
                    color: #888;
                    font-size: 0.85em;
                    margin-top: -3px; /* Super small space */
                    opacity: 0.9;
                    animation: slideIn 0.5s ease-in-out;
                }
            </style>

            <?php 
                $currentUsername = isset($user['username']) ? $user['username'] : null; 
            ?>

            <div class="scoreboard">
                <h2>🏆 לוח משתתפים 🏆</h2>
                <ul style="list-style: none; padding: 0;">
                    <?php foreach ($scoreboardArray as $index => $player): ?>
                        <?php 
                            $isCurrentUser = (isset($currentUsername) && trim($player['username']) === trim($currentUsername));
                        ?>
                        <li class="player">
                            <!-- Username & Score -->
                            <span class="nickname <?= $isCurrentUser ? 'gold-shine' : '' ?>">
                                <?= htmlspecialchars($player['username']) ?> - <?= (int)$player['score'] ?>
                            </span>

                            <!-- User Comment (Minimal Space, Styled Like a Comment) -->
                            <?php if (!empty($player['user_note'])): ?>
                                <div class="user-note">
                                    <?= htmlspecialchars($player['user_note']) ?>
                                </div>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>






    </div>
</body>
</html>
