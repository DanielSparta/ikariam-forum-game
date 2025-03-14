<?php
error_reporting(0);
ini_set('display_errors', 0);
session_start();
// Reset session if the user refreshes the page
if (!isset($_POST['answer1']) && !isset($_POST['answer2']) && !isset($_POST['player'])) {
    session_destroy();
    session_start();
    $_SESSION['stage'] = 'welcome';
    $_SESSION['score'] = 0;
}

// Load or create scoreboard.json
$scoreboardFile = 'scoreboard.json';
if (!file_exists($scoreboardFile)) {
    file_put_contents($scoreboardFile, json_encode([])); // Create file if it doesn't exist
}
$scoreboard = json_decode(file_get_contents($scoreboardFile), true) ?? [];

// Track incorrect answers
$incorrectMessage = "";

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['is_registred'])) {
        echo "<h1>not exist</h1>";
    }
    else{
    if ($_SESSION['stage'] === 'welcome') {
        $_SESSION['stage'] = 'question1';
    } elseif ($_SESSION['stage'] === 'question1' && isset($_POST['answer1'])) {
        if ($_POST['answer1'] == '10') {
            $_SESSION['score'] += 10;
            $_SESSION['stage'] = 'question2';
        } else {
            $incorrectMessage = "❌ תשובה שגויה!";
        }
    } elseif ($_SESSION['stage'] === 'question2' && isset($_POST['answer2'])) {
        if (strtolower(trim($_POST['answer2'])) == 'marble') {
            $_SESSION['score'] += 10;
            $_SESSION['stage'] = 'final';
        } else {
            $incorrectMessage = "❌ תשובה שגויה!";
        }
    } elseif ($_SESSION['stage'] === 'final' && isset($_POST['player'])) {
        $player = htmlspecialchars($_POST['player']);

        // Save player name only (no score)
        if (!in_array($player, $scoreboard)) {
            $scoreboard[] = $player;
            file_put_contents($scoreboardFile, json_encode($scoreboard, JSON_PRETTY_PRINT));
        }

        session_destroy(); // Reset session
        header('Location: index.php'); // Reload to show welcome screen again
        exit;
    }
}
}
?>

<!DOCTYPE html>
<html lang="he">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>חידון איקריאם</title>
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
    </style>
</head>
<body>
    <div class="container">
        <?php if ($_SESSION['stage'] === 'welcome'): ?>
            <h1 dir=rtl>אתגר חדר הבריחה של פורום איקרים ישראל</h1>
            <p dir=rtl>פתרו כמה שיותר חידות על מנת לצבור כמה שיותר ניקוד!</p>
            <p dir=rtl>אלו אשר ישלימו את החידות שתתמודדו מולם, יזכו בפרסי אמברוסיה :)</p>
            <form method="post">
                <button type="submit">התחל</button>
            </form>
            <a href="/">התחבר/הרשם כמשתמש</a>

            <!-- Scoreboard Display -->
            <?php if (!empty($scoreboard)): ?>
                <div class="scoreboard">
                    <h2>🏆 לוח משתתפים 🏆</h2>
                    <ul>
                        <?php foreach ($scoreboard as $player): ?>
                            <li class="player"><?= htmlspecialchars($player) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

        <?php elseif ($_SESSION['stage'] === 'question1'): ?>
            <h1>שאלה 1</h1>
            <div class="question-box" style="background-image: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('ikariam_academy.jpg');"
            >
            לוחמים אמיצים, הקרב בעיצומו! אויבי העיר צרים על חומותינו, והגנרלים זקוקים בדחיפות למידע קריטי: כמה יחידות מסוגלות לעמוד בעומס הקרב בזכות השריון הכבד שלהן?

המשימה שלכם פשוטה – חפשו היטב בין הדיווחים הצבאיים, פתחו את הספרים האסטרטגיים, או אפילו כנסו אל שדה הקרב בעצמכם. רק מי שמכיר את הכוחות החזקים ביותר של איקריאם יוכל לפתור את החידה ולהתקדם לשלב הבא!


            </div>
            <form method="post">
                <input type="number" name="answer1" placeholder="הכנס מספר" required>
                <button type="submit">בדוק</button>
            </form>
            <?php if ($incorrectMessage): ?>
                <p class="error"><?= $incorrectMessage ?></p>
            <?php endif; ?>

        <?php elseif ($_SESSION['stage'] === 'question2'): ?>
            <h1>שאלה 2</h1>
            <div class="question-box" style="background-image: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('ikariam_tavern.jpg');">
                איזה משאב משמש לבניית פסלים באקרופוליס?
            </div>
            <form method="post">
                <input type="text" name="answer2" placeholder="הכנס שם המשאב" required>
                <button type="submit">בדוק</button>
            </form>
            <?php if ($incorrectMessage): ?>
                <p class="error"><?= $incorrectMessage ?></p>
            <?php endif; ?>

        <?php elseif ($_SESSION['stage'] === 'final'): ?>
            <h1 dir=rtl>כל הכבוד!</h1>
            <p>הניקוד שלך: <?= $_SESSION['score'] ?></p>
            <div class="question-box" style="background-image: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('ikariam_treasure.jpg');">
                הזן את שמך כדי להופיע בלוח הניקוד
            </div>
            <form method="post">
                <input type="text" name="player" placeholder="שם שחקן" required>
                <button type="submit">שלח</button>
            </form>

        <?php else: ?>
            <h1>שגיאה</h1>
        <?php endif; ?>
    </div>
</body>
</html>
