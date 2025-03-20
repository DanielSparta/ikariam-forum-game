<?php
session_start();
header("Content-Security-Policy: default-src 'none'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self'; object-src 'none'; frame-ancestors 'none'; base-uri 'none'; form-action 'self'; upgrade-insecure-requests");




/**
 * Database Configuration
 */
class Database {
    private mysqli $conn;

    public function __construct() {
        $this->conn = new mysqli("localhost", "ubuntu", "", "ikariam_quiz");

        if ($this->conn->connect_error) {
            die("Database connection failed: " . $this->conn->connect_error);
        }
    }
    public function getConnection(): mysqli {
        return $this->conn;
    }
}

$db = new Database();
$mysqli = $db->getConnection();

/**
 * Logging Utility
 */
function logAction(mysqli $db, string $message, string $type, ?int $user_id = null, ?string $username = null): void {
    $stmt = $db->prepare("INSERT INTO logs (error_message, error_type, user_ip, user_agent, user_id, username)
                                       VALUES (?, ?, ?, ?, ?, ?)");
    $user_ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'UNKNOWN';

    $stmt->bind_param("sssssi", $message, $type, $user_ip, $user_agent, $user_id, $username);
    $stmt->execute();
    $stmt->close();
}

/**
 * CSRF Token Management
 */
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function validateCsrfToken(string $token): bool {
    return isset($token) && $token === $_SESSION['csrf_token'];
}

/**
 * Check for IPv4 address
 */
function isIpv4(): bool {
    return filter_var($_SERVER['REMOTE_ADDR'], FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
}

/**
 * Rate Limiting for 10 seconds between requests
 */
function isRequestAllowed(): bool {
    $ip = $_SERVER['REMOTE_ADDR']; // Get the user's IP address
    $currentTime = time();

    // SESSION-BASED CHECK FIRST
    if (!isset($_SESSION['last_request_time']) || $currentTime - $_SESSION['last_request_time'] >= 10) {
        $_SESSION['last_request_time'] = $currentTime;
    } else {
        return false;
    }

    // IP-BASED CHECK AFTER SESSION CHECK
    if (!isset($_SESSION['request_ips'][$ip]) || $currentTime - $_SESSION['request_ips'][$ip] >= 10) {
        $_SESSION['request_ips'][$ip] = $currentTime;
        return true;
    }

    return false;
}

if (isset($_SESSION['username'], $_POST['invited_by'])) {
    if (!empty($invited_by) && strlen($invited_by) > 30) {
        $error = "❌ שגיאה: שם המשתמש של חברך לא יכול להיות ארוך מ-30 תווים.";
        logAction($mysqli, "Invited_by username too long: {$invited_by}.", "error");
    }
    else {
        $updateStmt = $mysqli->prepare("UPDATE users SET invited_by = ? WHERE username = ?");
        $updateStmt->bind_param("ss", $_POST['invited_by'], $_SESSION['username']);
        $updateStmt->execute();
        $updateStmt->close();
    }
}


/**
 * Handle Login/Register Requests
 */
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['usrname'], $_POST['psswrd'], $_POST['csrf_token'])) {
    $username = trim($_POST['usrname']);
    $password = trim($_POST['psswrd']);

    // CSRF validation
    if (!validateCsrfToken($_POST['csrf_token'])) {
        $error = "❌ שגיאה: CSRF Token לא תקין.";
        logAction($mysqli, "CSRF token validation failed.", "error");
    } elseif (!isRequestAllowed()) {
        $error = "❌ שגיאה: יש להמתין 10 שניות בין כל ניסיון.";
        logAction($mysqli, "Login Request rate limit exceeded for: {$username}.", "info");
    } elseif (empty($username) || empty($password) || !is_string($username) || !is_string($password)) {
        $error = "❌ שגיאה: נא להזין שם משתמש וסיסמה תקפים.";
        logAction($mysqli, "Invalid input format.", "error");
    } elseif (strlen($username) > 30) {
        $error = "❌ שגיאה: שם המשתמש לא יכול להיות ארוך מ-30 תווים.";
        logAction($mysqli, "Username too long: {$username}.", "error");
    } elseif (stripos($username, "﷽") !== false) {
        $error = "❌ התו שאתה מנסה להשתמש בו נחסם";
        logAction($mysqli, "Invalid username character: {$username}.", "error");
    } else {
        // Check if user exists
        $stmt = $mysqli->prepare("SELECT id, username, password FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) { 
            // User exists, validate password
            $stmt->bind_result($userId, $fetchedUsername, $hashedPassword);
            $stmt->fetch();

            if (password_verify($password, $hashedPassword)) {
                $newToken = bin2hex(random_bytes(32));

                $updateStmt = $mysqli->prepare("UPDATE users SET token = ? WHERE id = ?");
                $updateStmt->bind_param("si", $newToken, $userId);
                $updateStmt->execute();
                $updateStmt->close();

                setcookie("auth_token", $newToken, [
                    "expires" => time() + (86400 * 30),
                    "path" => "/",
                    "domain" => "",
                    "secure" => true,
                    "httponly" => true,
                    "samesite" => "Strict"
                ]);
                
                $_SESSION['is_registred'] = true;
                $_SESSION['username'] = $fetchedUsername;
                logAction($mysqli, "User logged in.", "info", 0, $fetchedUsername);

                header("Location: login.php");
                exit;
            } else {
                $error = "❌ שגיאה: סיסמה שגויה.";
                logAction($mysqli, "Incorrect password attempt for {$username}.", "error");
            }
        } else { 
            // Validate username length (max 30 characters) before registering
            if (strlen($username) > 30) {
                $error = "❌ שגיאה: שם המשתמש חייב להיות עד 30 תווים.";
                logAction($mysqli, "Failed registration attempt: Username too long ({$username}).", "error");
            } else {
                // Register new user
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $token = bin2hex(random_bytes(32));
                $answeredQuestions = '[]'; // Valid JSON default value
                $user_note = ""; // Default empty user note

                $insertStmt = $mysqli->prepare("INSERT INTO users (username, password, user_note, token, answered_questions, invited_by) VALUES (?, ?, ?, ?, ?, ?)");
                $insertStmt->bind_param("ssssss", $username, $hashedPassword, $user_note, $token, $answeredQuestions, "none");

                if ($insertStmt->execute()) {
                    logAction($mysqli, "New user registered: {$username}.", "info");
                    $_SESSION['is_registred'] = true;
                    $_SESSION['username'] = $username;
                    setcookie("auth_token", $newToken, [
                        "expires" => time() + (86400 * 30),
                        "path" => "/",
                        "domain" => "",
                        "secure" => true,
                        "httponly" => true,
                        "samesite" => "Strict"
                    ]);
                    header("Location: login.php");
                    exit;
                } else {
                    $error = "❌ שגיאה: שם המשתמש כבר קיים.";
                    logAction($mysqli, "Failed registration attempt: {$username}.", "error");
                }

                $insertStmt->close();
            }
        }

        $stmt->close();
    }
}


$mysqli->close();
?>

<?php include 'login.view.php'; ?>