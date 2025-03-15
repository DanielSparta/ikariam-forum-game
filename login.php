<?php
session_start();

// Database Connection
$mysqli = new mysqli("localhost", "root", "", "ikariam_quiz");

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Check if 'logs' table exists, if not create it
$table_check = $mysqli->query("SHOW TABLES LIKE 'logs'");
if ($table_check->num_rows == 0) {
    // Create 'logs' table
    $create_table = "
        CREATE TABLE logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            error_message TEXT NULL,
            error_type VARCHAR(50) NULL,
            user_ip VARCHAR(255) NOT NULL,
            user_agent TEXT NOT NULL,
            user_id INT NULL,
            username VARCHAR(255) NULL,
            timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
    ";
    if (!$mysqli->query($create_table)) {
        die("Error creating logs table: " . $mysqli->error);
    }
}

// Function to log errors and actions into the 'logs' table
function logAction($mysqli, $error_message, $error_type, $user_id = null, $username = null) {
    $user_ip = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];

    // Insert the log into the 'logs' table
    $stmt = $mysqli->prepare("INSERT INTO logs (error_message, error_type, user_ip, user_agent, user_id, username) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $error_message, $error_type, $user_ip, $user_agent, $user_id, $username);

    if (!$stmt->execute()) {
        // In case the insert fails, we can log the failure as well
        error_log("Failed to log action: " . $stmt->error);
    }

    $stmt->close();
}

// Generate CSRF Token if not set (in session)
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Secure random token
}

// Validate CSRF Token
function validateCsrfToken($token) {
    if (!isset($token) || !is_string($token) || $token !== $_SESSION['csrf_token']) {
        logAction($GLOBALS['mysqli'], "CSRF token validation failed.", "error");
        return false;
    }
    return true;
}

// Authenticate user via cookie (check if already connected)
if (isset($_COOKIE['auth_token'])) {
    // Check if the token is valid in the database
    $stmt = $mysqli->prepare("SELECT id, username FROM users WHERE token = ?");
    $stmt->bind_param("s", $_COOKIE['auth_token']);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($userId, $username);
        $stmt->fetch();

        // User is already logged in, no need to process login again
        $_SESSION['is_registred'] = true;
        $_SESSION['username'] = $username;

        // Log the action
        logAction($mysqli, "User already logged in with valid token.", "info", $userId, $username);
    } else {
        // Invalid token, log the failed attempt
        logAction($mysqli, "Invalid auth_token attempted.", "error", null, null);
    }

    $stmt->close();
}

// Handle login/register
$error = ''; // Initialize error message variable
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['username'], $_POST['password'], $_POST['csrf_token'])) {
    // Validate CSRF token
    if (!validateCsrfToken($_POST['csrf_token'])) {
        $error = "❌ שגיאה: csrf token לא תואם, אנא נסה שוב. ❌";
        logAction($mysqli, "Attempted request without a valid CSRF token.", "error", null, null);
    } else {
        // CSRF token is valid, process login/register
        $username = $_POST['username'];
        $password = $_POST['password'];

        if (!empty($username) && !empty($password)) {
            if (!is_array($username) && !is_array($password)) {
                $stmt = $mysqli->prepare("SELECT id, password FROM users WHERE username = ?");
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $stmt->store_result();

                if ($stmt->num_rows > 0) { // User exists, validate password
                    $stmt->bind_result($userId, $hashedPassword);
                    $stmt->fetch();
                    if (password_verify($password, $hashedPassword)) {
                        $token = bin2hex(random_bytes(32)); // Generate a new token
                        $stmt = $mysqli->prepare("UPDATE users SET token = ? WHERE id = ?");
                        $stmt->bind_param("si", $token, $userId);
                        $stmt->execute();

                        // Set session and token cookie
                        $_SESSION['is_registred'] = true;
                        $_SESSION['username'] = $username;
                        setcookie("auth_token", $token, time() + (86400 * 30), "/", "", false, true);
                        // Log the successful login
                        logAction($mysqli, "User logged in successfully.", "info", $userId, $username);
                        header("Location: login.php");
                        exit;
                    } else {
                        // Incorrect password, log the failed attempt
                        $error = "הססמא עבור המשתמש " . $username . " שגויה.";
                        logAction($mysqli, "Incorrect password for user: " . $username, "error", null, $username);
                    }
                } else { // Register new user
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $token = bin2hex(random_bytes(32)); // Generate a new token
                    $stmt = $mysqli->prepare("INSERT INTO users (username, password, token) VALUES (?, ?, ?)");
                    $stmt->bind_param("sss", $username, $hashedPassword, $token);
                    if (!$stmt->execute()) {
                        $error = "שם משתמש כבר קיים";
                        logAction($mysqli, "Username already exists: " . $username, "error", null, $username);
                    }
                    else{
                        logAction($mysqli, "User created: " . $username, "info", null, $username);
                        header('Location: login.php');
                    }
                }
            } else {
                $error = "עלייך להכניס מידע כמחרוזת.";
                logAction($mysqli, "Invalid input, non-string data received.", "error", null, null);
            }
        } else {
            $error = "עלייך להזין מידע משתמש בשדה שם המשתמש ובססמא.";
            logAction($mysqli, "Missing username or password.", "error", null, null);
        }
    }
}

$mysqli->close();
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
        /* General Styles */
* {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}

body {
    background: url('/background.png') no-repeat center center fixed;
    background-size: cover;
    font-family: 'Heebo', sans-serif;
    text-align: center;
    color: #fff;
    margin: 0;
    padding: 0;
}

/* Default Style for Computers */
.container {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 90%;
    max-width: 600px;
    background: rgba(0, 0, 0, 0.85);
    padding: 30px;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.7);
    backdrop-filter: blur(10px);
}

h1 {
    font-size: 30px;
    margin-bottom: 15px;
    font-weight: 700;
}

.question-box {
    background: rgba(255, 255, 255, 0.1);
    padding: 25px;
    border-radius: 10px;
    margin-bottom: 15px;
    font-weight: bold;
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.7);
}

input, button {
    width: 85%;
    padding: 12px;
    margin: 8px 0;
    border-radius: 6px;
    border: none;
    font-size: 16px;
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
    font-size: 18px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.3s ease-in-out;
    box-shadow: 0 4px 10px rgba(255, 152, 0, 0.5);
}

button:hover {
    transform: scale(1.05);
    background: linear-gradient(45deg, #e68900, #e64a19);
}

.scoreboard {
    margin-top: 20px;
    background: rgba(255, 255, 255, 0.1);
    padding: 15px;
    border-radius: 10px;
}

.scoreboard h2 {
    font-size: 24px;
    color: #f4a100;
    font-weight: bold;
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
    color: #ff4d4d;
    font-size: 20px;
    font-weight: bold;
}

.correct {
    color: #27ae60;
    font-size: 20px;
    font-weight: bold;
}

/* Mobile Styles */
@media (max-width: 768px) {
    body {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        min-height: 100vh;
        padding: 15px;
    }

    .container {
        width: 90%;
        max-width: 600px;
        padding: 20px;
    }

    h1 {
        font-size: 6vw;
    }

    .question-box {
        font-size: 5vw;
        padding: 15px;
    }

    .scoreboard h2 {
        font-size: 5vw;
    }

    .player {
        font-size: 4vw;
    }

    input, button {
        width: 100%;
        font-size: 16px;
    }
}

@media (max-width: 480px) {
    h1 {
        font-size: 7vw;
    }

    .question-box {
        font-size: 6vw;
    }

    .scoreboard h2 {
        font-size: 6vw;
    }

    .player {
        font-size: 5vw;
    }

    button {
        font-size: 16px;
        padding: 10px;
    }
}

    </style>
</head>
<body>
    <div dir="rtl" class="container">
        <img src="https://i.gyazo.com/2d655af08821f93ca232d3e338cae1c0.png" style="max-width: 90%; height: auto;">
        
        <?php if (!isset($_SESSION['is_registred'])): ?>
            <h1 dir="rtl">מסך הרשמה/התחברות</h1>
            <p dir="rtl">הכניסו שם משתמש וססמא כדי להירשם או להתחבר.</p>
            <p dir="rtl">דף זה משתמש כדף התחברות וכדף הרשמה, זאת אומרת שאם תרשמו משתמש שלא קיים, אתם תרשמו איתו לאתר. במידה ותרשמו משתמש קיים, אתם תתחברו איתו.</p>
            <p dir="rtl">מטרת ההתחברות היא על מנת שתוכלו לגשת למשתמש שלכם לאורך זמן ולהמשיך להתקדם בניקוד. בעת יצירת המשתמש, אל תשתמשו בפרטי החשבון האמיתיים של המשתמש איקרים שלכם</p>
            
            <form method="POST" action="login.php">
                <input type="text" name="username" placeholder="כינוי שיוצג בטבלת הניקוד" required>
                <input type="password" name="password" placeholder="ססמא איתה תתחברו לאתר הזה" required>
                <!-- CSRF Token -->
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <button name="login" type="submit">התחבר/הרשם</button>
            </form>

            <?php if (!empty($error)): ?>
                <p class="error"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>

        <?php else: ?>
            <h1 dir="rtl"><?= htmlspecialchars($_SESSION['username']) ?>, נראה שאתה מחובר.</h1>
            <p dir="rtl">אתה מחובר ועל כן אתה יכול להתחיל להשתתף בחדר בריחה.</p>
            
            <form method="POST" action="index.php">
                <!-- CSRF Token for starting the escape room -->
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
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