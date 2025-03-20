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
        <img src="/newsteam.png" style="max-width: 90%; height: auto;">
        
        <?php if (!isset($_SESSION['is_registred'])): ?>
            <h1 dir="rtl">מסך הרשמה/התחברות</h1>
            <p dir="rtl">הכניסו שם משתמש וססמא כדי להירשם או להתחבר.</p>
            <p dir="rtl">דף זה משתמש כדף התחברות וכדף הרשמה, זאת אומרת שאם תרשמו משתמש שלא קיים, אתם תרשמו איתו לאתר. במידה ותרשמו משתמש קיים, אתם תתחברו איתו.</p>
            <p dir="rtl">מטרת ההתחברות היא על מנת שתוכלו לגשת למשתמש שלכם לאורך זמן ולהמשיך להתקדם בניקוד. בעת יצירת המשתמש, אל תשתמשו בפרטי החשבון האמיתיים של המשתמש איקרים שלכם</p>
            <br>
            <form method="POST" action="login.php">
                <input type="text" name="usrname" placeholder="כינוי שיוצג בטבלת הניקוד" required>
                <input type="password" name="psswrd" placeholder="ססמא איתה תתחברו לאתר הזה" required>
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <button name="login" type="submit">התחבר/הרשם</button>
            </form>
            <?php if (!empty($error)): ?>
                <p class="error"><?= htmlspecialchars($error) ?></p>
            <?php endif; ?>
        <?php else: ?>
            <?php if (isset($_SESSION['show_invited_by'])): ?>
                <form method="POST" action="login.php">
                    <input type="text" name="invited_by" placeholder="(לא חובה למלא) שם משתמש של מי שהגעתם דרכו לאתר">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <button name="login" type="submit">המשך לאתר!</button>
                </form>
                <?php unset($_SESSION['show_invited_by']) ?>
            <?php else: ?>
                <h1 class="loggedIn" dir="rtl"><?= htmlspecialchars($_SESSION['username']) ?>, נראה שאתה מחובר.</h1>
                <form method="POST" action="index.php">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <button type="submit">התחל בחדר בריחה</button>
                </form>
            <?php endif; ?>
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