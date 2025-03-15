<?php
require 'update_json.php';
session_start();

// Database Connection
$mysqli = new mysqli("localhost", "root", "", "ikariam_quiz");

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Function to generate a secure token
function generateToken(): string {
    return bin2hex(random_bytes(32)); // 64-character secure token
}

// Authenticate user via cookie
if (!isset($_SESSION['is_registred']) && isset($_COOKIE['auth_token'])) {
    $stmt = $mysqli->prepare("SELECT username FROM users WHERE token = ?");
    $stmt->bind_param("s", $_COOKIE['auth_token']);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($username);
        $stmt->fetch();
        $_SESSION['is_registred'] = true;
        $_SESSION['username'] = $username;
    }
    $stmt->close();
}

// Handle login/register
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'], $_POST['password'])) {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    if (!empty($username) && !empty($password)) {
        $stmt = $mysqli->prepare("SELECT id, password FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) { // User exists, validate password
            $stmt->bind_result($userId, $hashedPassword);
            $stmt->fetch();
            if (password_verify($password, $hashedPassword)) {
                $token = generateToken();
                $stmt = $mysqli->prepare("UPDATE users SET token = ? WHERE id = ?");
                $stmt->bind_param("si", $token, $userId);
                $stmt->execute();
            } else {
                die("שגיאה: הסיסמה שהוזנה אינה נכונה.");
            }
        } else { // Register new user
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $token = generateToken();
            $stmt = $mysqli->prepare("INSERT INTO users (username, password, token) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $username, $hashedPassword, $token);
            if (!$stmt->execute()) {
                die("שגיאה: שם המשתמש כבר קיים במערכת.");
            }
            update_json();
        }

        // Set session and token cookie
        $_SESSION['is_registred'] = true;
        $_SESSION['username'] = $username;
        setcookie("auth_token", $token, time() + (86400 * 30), "/", "", true, true);

        header("Location: index.php");
        exit;
    } else {
        die("שגיאה: יש למלא את כל השדות!");
    }
}
$mysqli->close();
?>



<!DOCTYPE html>
<html lang="he">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>חידון איקרים</title>
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
<img src="https://i.gyazo.com/2d655af08821f93ca232d3e338cae1c0.png">
    <div dir=rtl class="container">
        <?php if (!isset($_SESSION['is_registred'])): ?>
            <h1 dir="rtl">מסך הרשמה/התחברות</h1>
            <p dir="rtl">הכניסו שם משתמש וססמא כדי להירשם או להתחבר.</p>
            <p dir="rtl">דף זה משתמש כדף התחברות וכדף הרשמה, זאת אומרת שאם תרשמו משתמש שלא קיים, אתם תרשמו איתו לאתר. במידה ותרשמו משתמש קיים, אתם תתחברו איתו.</p>
            <p dir="rtl">מטרת ההתחברות היא על מנת שתוכלו לגשת למשתמש שלכם לאורך זמן ולהמשיך להתקדם בניקוד. בעת יצירת המשתמש, אל תשתמשו בפרטי החשבון האמיתיים של המשתמש איקרים שלכם</p>
            <form method="post" action="login.php">
                <input type="text" name="username" placeholder="כינוי שיוצג בטבלת הניקוד" required>
                <input type="password" name="password" placeholder="ססמא איתה תתחברו לאתר הזה" required>
                <button name="login" type="submit">התחבר/הרשם</button>
            </form>
            <?php if (!empty($error)): ?>
                <p class="error"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>
        <?php else: ?>
            <h1 dir=rtl><?= htmlspecialchars($_SESSION['username']) ?>, נראה שאתה כבר מחובר.</h1>
            <p dir=rtl>אתה כבר מחובר ועל כן אתה יכול להתחיל להשתתף בחדר בריחה.</p>
            <form method="POST" action="index.php">
                <button type="submit">התחל בחדר בריחה</button>
            </form>
        <?php endif; ?>
        <!-- Scoreboard Display -->
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
