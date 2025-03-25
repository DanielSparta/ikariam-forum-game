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
            background: url('assets/background.png') no-repeat center center fixed;
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

        /* Make images inside .question-box responsive */
        .question-box img {
            max-width: 100%;  /* Ensure the image does not exceed the container width */
            height: auto;     /* Maintain aspect ratio */
            display: block;   /* Remove any extra space below the image */
            margin: 0 auto;   /* Center the image */
        }
        
        #countdown {
        font-size: 22px;
        font-weight: bold;
        color: #ffcc00;
        text-align: center;
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
    <img src="assets/newsteam.png" style="max-width: 90%; height: auto;">
        <?php if (isset($_SESSION['stage']) && $_SESSION['stage'] === 'welcome_page'): ?>
            <h1>אתגר חדר הבריחה</h1>
            <p> ברוכים הבאים לחדר הבריחה של פורום איקרים! כאן תמצאו חידות ושאלות, חלקן קשורות למשחק, וחלקן לא. החידות לא בהכרח מצריכות ידע קודם במשחק! המטרה שלכם היא לענות על כמה שיותר חידות ושאלות, ובכך להשיג כמות ניקוד גבוהה יותר משל שאר המשתתפים! מי יתגלה כפותר החידות הטוב ביותר?</p>
            <p><b>🏆 3 השחקנים המובילים יזכו בקופוני אמברוסיה שווים! 🏆</b></p>
            <p><b>בכל רגע נתון, גם יום לפני סיום התחרות, כל שחקן יכול להגיע למיקום הראשון.</b></p>
            <p>🔥 שאלות חדשות מדי יום 🔥</p>
            <br>
            <hr>
            <?php if ($isAuthenticated): ?>
                <?php $_SESSION['stage'] = "start"; ?>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                    <button name="login" type="submit">🔓 התחל</button>
                </form>
                <?php if (isset($_SESSION['HangmanEventAvailable'])): ?>
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                        <button name="hangman" type="submit">😵 איוונט איש תלוי 😵 - זמני!</button>
                    </form>
                <?php endif; ?>

                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                    <button name="settings" type="submit">⚙️ הגדרות</button>
                    <hr>
                        <span id="copyIcon" onclick="copyToClipboard()" title="העתק קישור 📋">מוזמנים לשתף את האתר: https://ikaforum.servegame.com/</span>
                    <script>function copyToClipboard() {
                        const link = "https://ikaforum.servegame.com/";
                        navigator.clipboard.writeText(link).then(() => {
                            alert("הקישור הועתק ללוח!");
                        }).catch(err => {
                            console.error("Error copying link: ", err);
                        });
                    }
                    </script>
                    <p>קישור לפוסט הפעילות בפורום איקרים ישראל: <a href="https://forum.ikariam.gameforge.com/forum/thread/107762">https://forum.ikariam.gameforge.com/forum/thread/107762</a></p>
                </form>

            <?php else: ?>
                <form method="post" action=login.php>
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                    <button type="submit">🔑 הרשם / התחבר</button>
                    <hr>
                    <br><p>על מנת להשתתף, עלייך להצטרף ללוח המשתתפים תחילה.<br>לחץ על הכפתור "הרשם/התחבר" והתחל לעלות בניקוד!</p>
                    <p>קישור לפוסט הפעילות בפורום איקרים ישראל: <a href="https://forum.ikariam.gameforge.com/forum/thread/107762">https://forum.ikariam.gameforge.com/forum/thread/107762</a></p>
                </form>
        <?php endif; ?>

        <?php if ($isAuthenticated && $user['is_admin']): ?>
                <form method="post">
                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                    <button name="admin_panel" type="submit">🔧 כניסה לניהול</button>
                </form>
        <?php endif; ?>

        <?php elseif (isset($_SESSION['stage']) && $_SESSION['stage'] === 'question' && isset($_SESSION['question'])): ?>
            <h1>💡 חידה 💡</h1>
            <h2>את חידה זו פתרו <?= (int) ($pdo->query("SELECT answers FROM questions WHERE id=" . $_SESSION['question']['id'])->fetchColumn() ?: 0)?> אנשים</h2>
            <div class="question-box">
            <?= isset($_SESSION['question']['question']) ? $_SESSION['question']['question'] : 'שגיאה בהצגת השאלה, אנא הצג את מסך זה בפני לדניאל ספרטה :)' ?>
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

            <h4>בדיקה שגויה לא מורידה ניקוד, והחלפת שאלה אינה מוחקת את השאלה מהמשתמש שלכם.</h4>

            <?php if (!empty($Message)): ?>
                <p class="<?= str_starts_with($Message, '✅') ? 'correct' : 'error' ?>"> <?= $Message ?> </p>
        <?php endif; ?>

        <?php elseif (isset($_SESSION['stage']) && $_SESSION['stage'] === 'final'): ?>
            <?php $_SESSION['stage'] = "welcome_page"; ?>
            <h1>🎉 הודעת מערכת</h1>
            <p>כל הכבוד! ענית על כל השאלות הקיימות במאגר. המשך להתאמן, כי שאלות חדשות יתווספו בהמשך!<br>צלם את מסך זה לפוסט בפורום המשחק! https://forum.ikariam.gameforge.com/forum/thread/107762</p>
            <p><br>זכור שהפעילות נמשכת 30 ימים, והמערכת בנויה באופן כזה שלכל אחד יש סיכוי להגיע להיות מקום ראשון עד שהפעילות תסתיים - כל הזמן מתווספות שאלות חדשות. בונוס של נקודות בודדות לא יהיו מה שינצח את הפעילות בסופו של דבר, וגם לקראת סוף הפעילות יהיו דברים שוברי שוויון. <br><h1><b>האם תצליח לשמור על הרצף שלך?</b></h1></p>
            <button onclick="window.location.href = 'index.php'" type="submit">🔄 חזור למסך הבית</button>

        <?php elseif (isset($_SESSION['stage']) && $_SESSION['stage'] === 'hangman'): ?>
            <h1>😵 איוונט איש תלוי 😵</h1>
            <p><h2>ברוכים הבאים לאיוונט איש תלוי! </h2>מדובר בפעילות זמנית שוברת שוויון. כיצד הפעילות תתנהל?</p>
            <li>כל משתמש מקבל 3 מילים שהוא יצטרך לנחש באיש תלוי</li>
            <li>לכל משתמש יש 12 ניסיונות בלבד לניחוש אותיות!</li>
            <li>על כל מילה נכונה שתצליחו לנחש - תזכו ב5 נקודות.</li>
            <li>המצב של האיש תלוי שלכם נשמר, זאת אומרת שאתם יכולים לצאת מהאתר ולהמשיך בפעם הבאה שתכנסו</li>
            <li>אתם מקבלים 3 </li>
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                <button type="submit" name="start_hangman">בואו נתחיל</button>
            </form>
            <button onclick="window.location.href = 'index.php'" type="submit">🔄 חזור למסך הבית</button>

        <?php elseif (isset($_SESSION['stage']) && $_SESSION['stage'] === 'start_hangman'): ?>
            <h1>😵 איוונט איש תלוי 😵</h1s>
            <h3>תכירו את מוטי, הוא יהיה האיש תלוי שלכם</h3>
            <h3>מספר טעויות שנשארו: </h3>
            <img src="moti0.png">
            
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                <input type="text" maxlength=1 name="guess_hangman" value="">
                <button type="submit" name="submit_hangman">בדוק</button>
            </form>
            <button onclick="window.location.href = 'index.php'" type="submit">🔄 חזור למסך הבית</button>
        <?php endif; ?>
        
        <?php 
            if (isset($_SESSION['show_popup'])): 
                $stmt = $pdo->query("SELECT id, message, timestamp FROM broadcast_message ORDER BY timestamp DESC");
                $broadcast_messages = $stmt->fetchAll(PDO::FETCH_ASSOC); 
                if (isset($broadcast_messages[0]) && $broadcast_messages[0]):
        ?>
                    <div id="popup-overlay" class="popup-overlay">
                        <div id="popup" class="popup">
                            <div class="popup-content">
                                <h2>📢 עדכון 📢</h2>
                                <p>
                                    <?php
                                        echo $broadcast_messages[0]['message'];
                                    ?>
                                </p>
                                <button onclick="closePopup(<?php echo $broadcast_messages[0]['id']; ?>)">OK</button>
                            </div>
                        </div>
                    </div>

                    <script>
                        // Function to close the popup and set the cookie with an array of shown message IDs
                        function closePopup(messageId) {
                            let cookie = document.cookie.split('; ').find(row => row.startsWith('popup_showed='));
                            let popupShowedIds = cookie ? cookie.split('=')[1] : '';
                            popupShowedIds += "\\" + messageId.toString() + "\\";
                            document.cookie = "popup_showed=" + popupShowedIds + "; path=/; max-age=" + (60 * 60 * 24 * 365); // Cookie lasts for 1 year
                            document.getElementById("popup-overlay").style.display = "none";
                        }

                        // Check if the popup_showed cookie exists and matches the broadcast message IDs
                        window.onload = function() {
                            <?php
                            $show_popup = true;
                            $cookie = isset($_COOKIE['popup_showed']) ? $_COOKIE['popup_showed'] : '';
                            foreach ($broadcast_messages as $message){
                                if (str_contains($cookie, "\\" . $message['id'] . "\\"))
                                    $show_popup = false;
                            }
                            ?>
                            <?php if($show_popup): ?>
                                document.getElementById("popup-overlay").classList.add('show');
                            <?php else: ?>
                                document.getElementById("popup-overlay").style.display = "none";
                            <?php endif; ?>
                        };
                    </script>

<style>
    /* Import Google Fonts */
    @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@700&family=Lato:wght@400;700&display=swap');

    /* Overlay background for the popup */
    .popup-overlay {
        position: fixed;
        top: 0;
        left: 50%;
        transform: translateX(-50%); /* Center horizontally */
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.4); /* Gray overlay */
        display: flex;
        justify-content: center; /* Center horizontally */
        align-items: flex-start; /* Align to top */
        z-index: 9999;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.5s ease-in-out;
    }

    .popup-overlay.show {
        opacity: 1;
        pointer-events: all;
    }

    /* Popup box with black background */
    .popup {
        background: rgba(0, 0, 0, 0.9); /* Black background */
        border-radius: 15px;
        padding: 40px;
        width: 500px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        text-align: center;
        animation: popupFadeIn 0.6s ease-out;
        transform: scale(0.95);
        margin-top: 150px; /* Adjust margin to create space from the top */
    }

    /* Fade-in animation */
    @keyframes popupFadeIn {
        from {
            opacity: 0;
            transform: scale(0.9);
        }
        to {
            opacity: 1;
            transform: scale(1);
        }
    }

    /* Title styling with modern, bold font */
    .popup h2 {
        font-family: 'Roboto', sans-serif;
        font-size: 36px; /* Larger title */
        font-weight: 700;
        margin-bottom: 20px;
        color: white; /* White text */
        text-transform: uppercase;
        letter-spacing: 2px;
        text-align: center;
    }

    /* Message text styling with clean font and ample spacing */
    .popup p {
        font-family: 'Lato', sans-serif;
        font-size: 22px;
        font-weight: 600;
        color: white; /* White text */
        line-height: 1.7;
        margin-bottom: 30px;
        text-align: center;
    }

    /* Button styling with modern and smooth feel */
    .popup button {
        padding: 15px 30px;
        background-color: #007bff;
        color: white;
        border: none;
        border-radius: 50px;
        font-size: 18px;
        cursor: pointer;
        transition: background-color 0.3s ease, transform 0.2s ease;
        font-family: 'Lato', sans-serif;
        font-weight: 600;
        text-transform: uppercase;
    }

    /* Button hover effect */
    .popup button:hover {
        background-color: #0056b3;
        transform: scale(1.05);
    }

    /* Button active (clicked) effect */
    .popup button:active {
        transform: scale(0.98);
    }

    /* Button focus effect for accessibility */
    .popup button:focus {
        outline: none;
        box-shadow: 0 0 15px rgba(0, 123, 255, 0.6);
    }
</style>

            <?php endif; ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['stage']) && $_SESSION['stage'] === 'settings'): ?>
            <h1>מסך ההגדרות</h1>
            <hr>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                <input type="text" name="usertext" placeholder="רשום פתק משתמש שיישמר ליד שמכם בלוח המשתתפים">
                <button type="submit">שמור פתק משתמש</button>
            </form>
            <hr>
            <button onclick="window.location.href = 'index.php'" type="submit">🔄 חזור למסך הבית</button>
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

        <?php if (isset($_SESSION['stage']) && $_SESSION['stage'] === 'admin_panel' && !empty($user['is_admin'])): ?>
            <div style="font-family: Arial, sans-serif;">
                <h1 style="text-align: center; margin-bottom: 20px;">פאנל ניהולי</h1>

                <!-- Navigation Tabs -->
                <div style="display: flex; justify-content: center; margin-bottom: 20px;">
                    <button onclick="showSection('questions')" class="tab-btn">ניהול שאלות</button>
                    <button onclick="showSection('users')" class="tab-btn">ניהול משתמשים</button>
                    <button onclick="showSection('logs')" class="tab-btn">ניהול לוגים</button>
                    <button onclick="showSection('broadcast')" class="tab-btn">הודעות מערכת</button>
                    <button onclick="showSection('hangman')" class="tab-btn">איש תלוי</button>
                </div>

                <!-- Content Sections -->
                <div id="questions" class="admin-section">
                    <h2 style="text-align: center;">ניהול שאלות</h2>
                    <div style="padding: 20px;">
                        <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Question</th>
                                    <th>Answer</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($questions as $question): ?>
                                    <tr>
                                    <td><?= $question['id'] ?? 'null' ?></td>
                                    <td><?= $question['question'] ?? 'null' ?></td>
                                    <td><?= $question['answer'] ?? 'null' ?></td>
                                        <td>
                                            <!-- Edit and Delete Actions -->
                                            <form method="post" style="display: inline-block;">
                                                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                                <input type="hidden" name="question_id" value="<?= $question['id'] ?>">
                                                <button type="submit" name="delete_question" style="background-color: #e74c3c; color: white; padding: 5px 10px; border: none; cursor: pointer;">מחק</button>
                                            </form>
                                            <button onclick="toggleEditForm(<?= $question['id'] ?>)" class="edit-btn">ערוך</button>
                                            
                                            <!-- Edit Form -->
                                            <div id="edit-data-<?= $question['id'] ?>" style="display: none; margin-top: 10px;">
                                                <form method="post">
                                                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                                    <input type="hidden" name="question_id" value="<?= $question['id'] ?>">
                                                    <input type="text" name="updated_question" placeholder="Edit question" value="<?= htmlspecialchars($question['question']) ?>" style="width: 100%; padding: 10px; margin-bottom: 10px;">
                                                    <input type="text" name="updated_answer" placeholder="Edit answer" value="<?= htmlspecialchars($question['answer']) ?>" style="width: 100%; padding: 10px; margin-bottom: 10px;">
                                                    <button type="submit" name="edit_question" style="background-color: #2ecc71; color: white; padding: 10px; border: none; cursor: pointer;">עדכן</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <h3>הוסף שאלה חדשה</h3>
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                            <input type="text" name="new_question" placeholder="רשום שאלה" required style="width: 100%; padding: 10px; margin-bottom: 10px;">
                            <input type="text" name="new_answer" placeholder="רשום תשובה" required style="width: 100%; padding: 10px; margin-bottom: 20px;">
                            <button type="submit" name="add_question" style="background-color: #3498db; color: white; padding: 10px; border: none; cursor: pointer;">הוסף שאלה</button>
                        </form>
                    </div>
                </div>

                <div id="users" class="admin-section" style="display: none;">
                    <h2 style="text-align: center;">ניהול משתמשים</h2>
                    <div style="padding: 20px;">
                        <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Note</th>
                                    <th>Invited by</th>
                                    <th>Score</th>
                                    <th>Admin</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?= $user['id'] ?></td>
                                        <td><?= $user['username'] ?></td>
                                        <td><?= $user['user_note'] ?></td>
                                        <td><?= $user['invited_by'] ?></td>
                                        <td><?= $user['score'] ?></td>
                                        <td><?= $user['is_admin'] ? 'Yes' : 'No' ?></td>
                                        <td>
                                            <!-- Edit and Delete Actions -->
                                            <form method="post" style="display: inline-block;">
                                                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                <button type="submit" name="delete_user" style="background-color: #e74c3c; color: white; padding: 5px 10px; border: none; cursor: pointer;">מחק</button>
                                            </form>
                                            <button onclick="toggleEditUserForm(<?= $user['id'] ?>)" style="background-color: #e74c3c; color: white; padding: 5px 10px; border: none; cursor: pointer;">ערוך</button>

                                            <!-- Edit User Form -->
                                            <div id="edit-user-<?= $user['id'] ?>" style="display: none; margin-top: 10px;">
                                                <form method="post">
                                                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                                    <input type="text" name="updated_username" placeholder="Edit username" value="<?= htmlspecialchars($user['username']) ?>" style="width: 100%; padding: 10px; margin-bottom: 10px;">
                                                    <input type="text" name="updated_user_note" placeholder="Edit user note" value="<?= $user['user_note'] ?>" style="width: 100%; padding: 10px; margin-bottom: 10px;">
                                                    <input type="number" name="updated_score" placeholder="Edit score" value="<?= htmlspecialchars($user['score']) ?>" style="width: 100%; padding: 10px; margin-bottom: 10px;">
                                                    <label for="updated_is_admin" style="font-weight: bold;">מנהל</label>
                                                    <input type="checkbox" name="updated_is_admin" <?= $user['is_admin'] ? 'checked' : '' ?> style="margin-bottom: 20px;">
                                                    <button type="submit" name="edit_user" style="background-color: #2ecc71; color: white; padding: 10px; border: none; cursor: pointer;">עדכן משתמש</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Logs Management Section -->
                <div dir=ltr id="logs" class="admin-section" style="display: none;">
                    <h2 style="text-align: center;">ניהול לוגים</h2>
                    <div style="padding: 20px;">
                        <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px; white-space: nowrap;">
                            <thead>
                                <tr>
                                    <th style="padding: 5px;">Type</th>
                                    <th style="padding: 5px;">Info</th>
                                    <th style="padding: 5px;">User IP</th>
                                    <th style="padding: 5px;">Username</th>
                                    <th style="padding: 5px;">Timestamp</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td style="padding: 10px;"><?= htmlspecialchars($log['error_type']) ?></td>
                                        <td style="padding: 10px; overflow: hidden; text-overflow: ellipsis; max-width: 400px; display: inline-block;">
                                            <?= htmlspecialchars(preg_replace('/\s+/', ' ', $log['error_message'])) ?>
                                        </td>
                                        <td style="padding: 10px;"><?= htmlspecialchars($log['user_ip']) ?></td>
                                        <td style="padding: 10px;"><?= htmlspecialchars($log['username'] ?? 'not logged') ?></td>
                                        <td style="padding: 10px;"><?= htmlspecialchars($log['timestamp']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <div style="text-align: center;">
                            <form method="post">
                                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>"> <!-- Include CSRF Token -->
                                <input type="hidden" name="page" value="<?= $page + 1 ?>"> <!-- Page value for next logs -->
                                <button type="submit" style="background-color: #3498db; color: white; padding: 10px 20px; border: none; cursor: pointer;">
                                    הצג לוגים נוספים
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div id="broadcast" class="admin-section">
                    <h2 style="text-align: center;">ניהול הודעות מערכת</h2>
                    <div style="padding: 20px;">
                        <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>message</th>
                                    <th>timestamp</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                    $stmt = $pdo->query("SELECT id, message, timestamp FROM broadcast_message");
                                    $broadcast_messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                ?>
                                <?php foreach ($broadcast_messages as $data): ?>
                                    <tr>
                                    <td><?= $data['id'] ?? 'null' ?></td>
                                    <td><?= $data['message'] ?? 'null' ?></td>
                                    <td><?= $data['timestamp'] ?? 'null' ?></td>
                                        <td>
                                            <!-- Edit and Delete Actions -->
                                            <form method="post" style="display: inline-block;">
                                                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                                <input type="hidden" name="broadcast_message_id" value="<?= $data['id'] ?>">
                                                <button type="submit" name="delete_broadcast_message" style="background-color: #e74c3c; color: white; padding: 5px 10px; border: none; cursor: pointer;">מחק</button>
                                            </form>
                                            <button onclick="toggleEditBroadcastForm(<?= $data['id'] ?>)" class="edit-btn" style="background-color: #e74c3c; color: white; padding: 5px 10px; border: none; cursor: pointer;">ערוך</button>
                                            
                                            <!-- Edit Form -->
                                            <div id="edit-broadcast-<?= $data['id'] ?>" style="display: none; margin-top: 10px;">
                                                <form method="post">
                                                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                                    <input type="hidden" name="broadcast_message_id" value="<?= $data['id'] ?>">
                                                    <input type="text" name="updated_broadcast_message" placeholder="Edit message" value="<?= htmlspecialchars($data['message']) ?>" style="width: 100%; padding: 10px; margin-bottom: 10px;">
                                                    <button type="submit" name="update_broadcast_message" style="background-color: #2ecc71; color: white; padding: 10px; border: none; cursor: pointer;">עדכן</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <h3>הוסף הודעת מערכת</h3>
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                            <input type="text" name="new_broadcast_message" placeholder="רשום הודעת מערכת" required style="width: 100%; padding: 10px; margin-bottom: 10px;">
                            <button type="submit" name="add_broadcast_message" style="background-color: #3498db; color: white; padding: 10px; border: none; cursor: pointer;">הוסף הודעה</button>
                        </form>
                    </div>
                </div>


                <div id="hangman" class="admin-section">
                    <h2 style="text-align: center;">ניהול איש תלוי</h2>
                    <div style="padding: 20px;">
                        <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>word</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                    $stmt = $pdo->query("SELECT id, word FROM hangman_event_words");
                                    $broadcast_messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                ?>
                                <?php foreach ($broadcast_messages as $data): ?>
                                    <tr>
                                    <td><?= $data['id'] ?? 'null' ?></td>
                                    <td><?= $data['word'] ?? 'null' ?></td>
                                        <td>
                                            <!-- Edit and Delete Actions -->
                                            <form method="post" style="display: inline-block;">
                                                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                                <input type="hidden" name="hangman_word_id" value="<?= $data['id'] ?>">
                                                <button type="submit" name="delete_hangman_word" style="background-color: #e74c3c; color: white; padding: 5px 10px; border: none; cursor: pointer;">מחק</button>
                                            </form>
                                            <button onclick="toggleEditBroadcastForm(<?= $data['id'] ?>)" class="edit-btn" style="background-color: #e74c3c; color: white; padding: 5px 10px; border: none; cursor: pointer;">ערוך</button>
                                            
                                            <!-- Edit Form -->
                                            <div id="edit-hangman-<?= $data['id'] ?>" style="display: none; margin-top: 10px;">
                                                <form method="post">
                                                    <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                                    <input type="hidden" name="hangman_word_id" value="<?= $data['id'] ?>">
                                                    <input type="text" name="update_hangman_word" placeholder="Edit message" value="<?= htmlspecialchars($data['word']) ?>" style="width: 100%; padding: 10px; margin-bottom: 10px;">
                                                    <button type="submit" name="update_hangman" style="background-color: #2ecc71; color: white; padding: 10px; border: none; cursor: pointer;">עדכן</button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>

                        <h3>הוסף מילה לאיש תלוי</h3>
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                            <input type="text" name="new_hangman_word" placeholder="רשום מילה לאיש תלוי" required style="width: 100%; padding: 10px; margin-bottom: 10px;">
                            <button type="submit" name="add_hangman_word" style="background-color: #3498db; color: white; padding: 10px; border: none; cursor: pointer;">הוסף הודעה</button>
                        </form>
                    </div>
                </div>
            </div>
        <button onclick="window.location.href = 'index.php'" type="submit" style="background-color: #95a5a6; color: white; padding: 10px 20px; border: none; cursor: pointer;">🔄 חזור למסך הבית</button>

    <script>
        // Function to toggle between sections
        function showSection(section) {
            const sections = document.querySelectorAll('.admin-section');
            sections.forEach(function(sec) {
                sec.style.display = 'none';  // Hide all sections
            });
            document.getElementById(section).style.display = 'block';  // Show selected section
        }

        // Function to toggle the visibility of the edit question form
        function toggleEditForm(questionId) {
            const form = document.getElementById('edit-data-' + questionId);
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        }

        // Function to toggle the visibility of the edit user form
        function toggleEditUserForm(userId) {
            const form = document.getElementById('edit-user-' + userId);
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        }

        // Function to toggle the visibility of the edit broadcast_message form
        function toggleEditBroadcastForm(userId) {
            const form = document.getElementById('edit-broadcast-' + userId);
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        }

        function toggleEditBroadcastForm(userId) {
            const form = document.getElementById('edit-hangman-' + userId);
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        }
    </script>
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
                $currentUsername = isset($_SESSION['username']) ? $_SESSION['username'] : null; 
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
                                <?= $player['username'] ?> - <?= (int)$player['score'] ?>
                            </span>
                            <?php if (!empty($player['user_note'])): ?>
                                <div class="user-note">
                                    <?= $player['user_note'] ?>
                                </div>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        <div id="countdown-container">
    <span id="countdown-label"></span>
    <span id="countdown"></span>
</div>

<div id="countdown"></div>

<script>
    function updateCountdown() {
        const targetDate = new Date("April 22, 2025 00:00:00").getTime();
        const now = new Date().getTime();
        const difference = targetDate - now;
        const countdownEl = document.getElementById("countdown");

        if (difference > 0) {
            const days = Math.floor(difference / (1000 * 60 * 60 * 24));
            const hours = Math.floor((difference % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((difference % (1000 * 60 * 60)) / (1000 * 60));

            countdownEl.innerHTML = `${days} ימים, ${hours} שעות, ${minutes} דקות`;
        } else {
            countdownEl.innerHTML = "🎯 האירוע הסתיים!";
            clearInterval(timer);
        }
    }

    updateCountdown();
    const timer = setInterval(updateCountdown, 1000);
</script>
</body>
</html>