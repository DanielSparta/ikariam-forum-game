<?php
$host = 'localhost';
$dbname = 'ikariam_quiz';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, [
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage()); // Log the error instead of using die()
    exit("Database connection error. Please try again later.");
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
        ],
        "hangman_event_words" =>
        [
            "id INT AUTO_INCREMENT PRIMARY KEY",
            "word_topic TEXT",
            "word TEXT",
        ],
        "hangman_event_user_state" =>
        [
            "id INT AUTO_INCREMENT PRIMARY KEY",
            "user_id INT NOT NULL",
            "current_word_index INT NOT NULL",
            "used_guesses INT NOT NULL",
            "maximum_guesses INT NOT NULL",
            "remaining_words_array TEXT",
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
?>
