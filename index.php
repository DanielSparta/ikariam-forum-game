<?php 
session_start();
require 'db.php'; // Database connection

global $pdo; // Ensure PDO is globally accessible

// Ensure required tables exist
function ensureTablesExist(PDO $pdo): void {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(255) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            token VARCHAR(64) NOT NULL,
            score INT DEFAULT 0,
            answered_questions TEXT DEFAULT '',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS questions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            question TEXT NOT NULL,
            answer VARCHAR(255) NOT NULL
        )
    ");
}

ensureTablesExist($pdo);

// Fetch scoreboard data
function fetchScoreboard(PDO $pdo): array {
    $stmt = $pdo->query("SELECT username, score FROM users ORDER BY score DESC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

// Retrieve authenticated user data
function getAuthenticatedUser(PDO $pdo, ?string $authToken): ?array {
    if (!$authToken) return null;

    $stmt = $pdo->prepare("SELECT id, score, answered_questions FROM users WHERE token = ?");
    $stmt->execute([$authToken]);
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

    $stmt = $pdo->prepare($query);
    $stmt->execute($answeredQuestions);
    
    return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
}

// Reset session state if no form submission
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$isAuthenticated) {
        header('Location: login.php');
        exit;
    }

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
                        $stmt = $pdo->prepare("UPDATE users SET score = score + 10, answered_questions = ? WHERE token = ?");
                        $stmt->execute([json_encode($answeredQuestions), $authToken]);
                    }
        
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
            break;
        

        case 'final':
            if (isset($_POST['player'])) {
                $stmt = $pdo->prepare("UPDATE users SET score = score + ? WHERE username = ?");
                $stmt->execute([$_SESSION['score'], htmlspecialchars($_POST['player'])]);
                session_destroy();
                header('Location: index.php');
                exit;
            }
            break;
    }
}
?>






<!DOCTYPE html>
<html lang="he">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>חדר בריחה צוות חדשות</title>
    <style>
        body {
            background: url('background.png') no-repeat center center fixed;
            background-size: cover;
            font-family: Arial, sans-serif;
            text-align: center;
            color: #fff;
        }
        .container {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 90%;
            max-width: 600px;
            background: rgba(0, 0, 0, 0.8);
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
        }
        h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        .question-box {
            background-size: cover;
            padding: 30px;
            border-radius: 8px;
            margin-bottom: 15px;
            color: #fff;
            font-weight: bold;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.7);
        }
        input, button {
            width: 80%;
            padding: 10px;
            margin: 5px;
            border-radius: 5px;
            border: none;
        }
        input {
            background: #fff;
            color: #000;
            font-size: 16px;
            text-align: center;
        }
        button {
            background: #f4a100;
            color: #fff;
            font-size: 18px;
            cursor: pointer;
            transition: 0.3s;
        }
        button:hover {
            background: #e58c00;
        }
        .scoreboard {
            margin-top: 20px;
            background: rgba(255, 255, 255, 0.1);
            padding: 15px;
            border-radius: 8px;
            color: #fff;
        }
        .scoreboard h2 {
            font-size: 22px;
            color: #f4a100;
        }
        .scoreboard ul {
            list-style: none;
            padding: 0;
        }
        .player {
            font-size: 18px;
            font-weight: bold;
        }
        .error {
            color: red;
            font-size: 20px;
            font-weight: bold;
        }
        .correct {
            color: green;
            font-size: 20px;
            font-weight: bold;
        }
    </style>
</head>
<body>
<img src="https://i.gyazo.com/2d655af08821f93ca232d3e338cae1c0.png">
    <div dir=rtl class="container">
        <?php if ($_SESSION['stage'] === 'welcome_page'): ?>
            <h1>אתגר חדר הבריחה של פורום איקרים ישראל</h1>
            <p>ברוכים הבאים לאתגר חדר הבריחה של פורום איקרים ישראל</p>
            <p>אתם תיתקלו במגוון חידות הקשורות למשחק, ועליכם יהיה לפתור אותם, ובכך תשיגו ניקוד.</p>
            <p>3 השחקנים אשר ישיגו את כמות הניקוד הגבוהה ביותר, יזכו בקופוני אמברוסיה שווים במיוחד :)</p>
            <?php if ($isAuthenticated): ?>
                <?php $_SESSION['stage'] = "start"; ?>
                <form method="post"><button name="login" type="submit">התחל</button></form>
            <?php else: ?>
                <form method="post"><button type="submit">הרשם/התחבר</button></form>
            <?php endif; ?>
        
        <?php elseif ($_SESSION['stage'] === 'question' && isset($_SESSION['question'])): ?>
            <h1>חידה</h1>
            <div class="question-box">
                <?= htmlspecialchars($_SESSION['question']['question']) ?>
            </div>
            <form method="post">
                <input type="text" name="answer" required>
                <button type="submit">בדוק</button>
            </form>
            <?php if (!empty($Message)): ?>
                <p class="<?= str_starts_with($Message, '✅') ? 'correct' : 'error' ?>"> <?= $Message ?> </p>
            <?php endif; ?>
        
        <?php elseif ($_SESSION['stage'] === 'final'): ?>
            <?php $_SESSION['stage'] = "welcome_page"; ?>
            <h1>הודעת מערכת</h1>
            <p>נראה כאילו ענית על כל השאלות במאגר :) מדי פעם מתעדכנים עוד שאלות, וכך תוכל להמשיך להתקדם בניקוד.</p>
            <p>הניקוד שלך: <?= $_SESSION['score'] ?></p>
            <form method="POST">
                <button type="submit">חזור למסך הבית</button>
            </form>
        <?php endif; ?>

        <?php if (!empty($scoreboardArray)): ?> 
            <div class="scoreboard">
                <h2>🏆 לוח משתתפים 🏆</h2>
                <ul>
                    <?php foreach ($scoreboardArray as $player): ?>
                        <li class="player"><?= htmlspecialchars($player['username']) ?> - <?= htmlspecialchars($player['score']) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</body>

</html>