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
    <img src="/newsteam.png" style="max-width: 90%; height: auto;">
        <?php if (isset($_SESSION['stage']) && $_SESSION['stage'] === 'welcome_page'): ?>
            <h1>אתגר חדר הבריחה</h1>
            <p> ברוכים הבאים לחדר הבריחה של פורום איקרים! כאן תמצאו חידות ושאלות, חלקן קשורות למשחק, וחלקן לא. החידות לא בהכרח מצריכות ידע קודם במשחק! המטרה שלכם היא לענות על כמה שיותר חידות ושאלות, ובכך להשיג כמות ניקוד גבוהה יותר משל שאר המשתתפים! מי יתגלה כפותר החידות הטוב ביותר?</p>
            <p><b>🏆 3 השחקנים המובילים יזכו בקופוני אמברוסיה שווים! 🏆</b></p>
            <p>🔥 שאלות חדשות מדי יום 🔥</p>
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
                <form method="post">
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
            <!-- פיצר להוספה אולי בעתיד <h2>את חידה זו פתרו <?= (int) ($pdo->query("SELECT answers FROM questions WHERE id=" . $_SESSION['question']['id'])->fetchColumn() ?: 0)?> אנשים</h2>-->
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

            <?php if (!empty($Message)): ?>
                <p class="<?= str_starts_with($Message, '✅') ? 'correct' : 'error' ?>"> <?= $Message ?> </p>
            <?php endif; ?>

            <?php elseif (isset($_SESSION['stage']) && $_SESSION['stage'] === 'final'): ?>
            <?php $_SESSION['stage'] = "welcome_page"; ?>
            <h1>🎉 הודעת מערכת</h1>
            <p>כל הכבוד! ענית על כל השאלות הקיימות במאגר. המשך להתאמן, כי שאלות חדשות יתווספו בהמשך!</p>
            <p>💎 ניקודך: <strong><?= $_SESSION['score'] ?></strong></p>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                <button type="submit">🔄 חזור למסך הבית</button>
            </form>
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
        <?php if (isset($_SESSION['stage']) && $_SESSION['stage'] === 'admin_panel' && !empty($user['is_admin'])): ?>
    <div style="font-family: Arial, sans-serif;">
        <h1 style="text-align: center; margin-bottom: 20px;">פאנל ניהולי</h1>

        <!-- Navigation Tabs -->
        <div style="display: flex; justify-content: center; margin-bottom: 20px;">
            <button onclick="showSection('questions')" class="tab-btn">ניהול שאלות</button>
            <button onclick="showSection('users')" class="tab-btn">ניהול משתמשים</button>
            <button onclick="showSection('logs')" class="tab-btn">ניהול לוגים</button>
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
                                    <div id="edit-question-<?= $question['id'] ?>" style="display: none; margin-top: 10px;">
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
                            <th>Score</th>
                            <th>Admin</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?= htmlspecialchars($user['id']) ?></td>
                                <td><?= htmlspecialchars($user['username']) ?></td>
                                <td><?= htmlspecialchars($user['user_note']) ?></td>
                                <td><?= htmlspecialchars($user['score']) ?></td>
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
                                            <input type="text" name="updated_user_note" placeholder="Edit user note" value="<?= htmlspecialchars($user['user_note']) ?>" style="width: 100%; padding: 10px; margin-bottom: 10px;">
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


        <form method="post" style="text-align: center;">
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
            <button type="submit" name="set_homepage" style="background-color: #95a5a6; color: white; padding: 10px 20px; border: none; cursor: pointer;">חזור למסך הבית</button>
        </form>
    </div>

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
            const form = document.getElementById('edit-question-' + questionId);
            form.style.display = form.style.display === 'none' ? 'block' : 'none';
        }

        // Function to toggle the visibility of the edit user form
        function toggleEditUserForm(userId) {
            const form = document.getElementById('edit-user-' + userId);
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
                                <?= htmlspecialchars($player['username']) ?> - <?= (int)$player['score'] ?>
                            </span>
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