<?php
session_start();
require_once '../config/db.php';

$error = '';
$success = '';
$step = 1; // Step 1: ask username, Step 2: ask new password

// Store username for step 2
$username = '';

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['step']) && $_POST['step'] == '1') {
        // Step 1: Validate username
        $username = trim($_POST['username'] ?? '');
        if (empty($username)) {
            $error = 'Please enter your username.';
        } else {
            // Check if username exists
            $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$admin) {
                $error = 'Username not found. Please try again or create a new account.';
            } else {
                // Username found, proceed to step 2
                $step = 2;
            }
        }
    } elseif (isset($_POST['step']) && $_POST['step'] == '2') {
        // Step 2: Process new password reset
        $username = trim($_POST['username'] ?? '');
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($new_password) || empty($confirm_password)) {
            $error = 'Please enter and confirm your new password.';
            $step = 2;
        } elseif (strlen($new_password) < 8) {
            $error = 'Password must be at least 8 characters long.';
            $step = 2;
        } elseif ($new_password !== $confirm_password) {
            $error = 'Passwords do not match. Please try again.';
            $step = 2;
        } else {
            // Check if username exists again before update (security)
            $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = ? LIMIT 1");
            $stmt->execute([$username]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$admin) {
                $error = 'Username not found. Please start over.';
                $step = 1;
            } else {
                // Update password hash
                $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $updateStmt = $pdo->prepare("UPDATE admin_users SET password_hash = ? WHERE username = ?");
                $updateStmt->execute([$new_hash, $username]);

                $success = 'Password reset successful! Redirecting to login...';
                // Redirect to login after 3 seconds
                header("refresh:3;url=login.php");
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Forgot Password - Admin - Event RSVP System</title>
    <style>
        /* Base and reset */
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            background: linear-gradient(135deg, #e76f51, #264653);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            color: #fff;
        }
        .container {
            background: #ffffffdd;
            padding: 35px 40px;
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.3);
            max-width: 420px;
            width: 100%;
            color: #264653;
            transition: transform 0.3s ease;
        }
        .container:hover {
            transform: translateY(-4px);
        }
        h2 {
            text-align: center;
            margin-bottom: 28px;
            color: #264653;
            font-weight: 700;
            letter-spacing: 1px;
        }
        label {
            display: block;
            font-weight: 600;
            margin-bottom: 8px;
            color: #264653;
        }
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px 14px;
            margin-bottom: 22px;
            border: 2px solid #a8dadc;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }
        input[type="text"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #e76f51;
            box-shadow: 0 0 8px #e76f5166;
        }
        button {
            width: 100%;
            padding: 14px 0;
            background-color: #e76f51;
            border: none;
            border-radius: 8px;
            color: white;
            font-weight: 700;
            font-size: 18px;
            cursor: pointer;
            transition: background-color 0.3s ease;
            margin-top: 5px;
        }
        button:hover, button:focus {
            background-color: #d95c3c;
            outline: none;
        }
        .error {
            background-color: #f8d7da;
            color: #842029;
            border: 1px solid #f5c2c7;
            border-radius: 8px;
            padding: 14px 16px;
            margin-bottom: 22px;
            font-weight: 600;
            box-shadow: 0 2px 8px #f5c2c7aa;
            animation: shake 0.3s ease;
            color: #842029;
            background: #fddede;
        }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-8px); }
            40%, 80% { transform: translateX(8px); }
        }
        .success {
            background-color: #d1e7dd;
            color: #0f5132;
            border: 1px solid #badbcc;
            border-radius: 8px;
            padding: 14px 16px;
            margin-bottom: 22px;
            font-weight: 600;
            box-shadow: 0 2px 8px #badbccaa;
        }
        .links {
            margin-top: 20px;
            text-align: center;
            font-size: 14px;
            color: #264653;
        }
        .links a {
            color: #e76f51;
            text-decoration: none;
            font-weight: 600;
            margin: 0 10px;
            transition: color 0.3s ease;
        }
        .links a:hover,
        .links a:focus {
            color: #d95c3c;
            text-decoration: underline;
        }

        /* Responsive */
        @media (max-width: 480px) {
            .container {
                padding: 30px 20px;
            }
            h2 {
                font-size: 24px;
            }
            button {
                font-size: 16px;
                padding: 12px 0;
            }
        }
    </style>
</head>
<body>
    <main class="container" role="main" aria-label="Admin forgot password form">
        <h2>Forgot Password</h2>

        <?php if ($error): ?>
            <div class="error" role="alert" aria-live="assertive"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success" role="alert" aria-live="polite"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if ($step === 1): ?>
            <!-- Step 1: Request username -->
            <form method="POST" action="" novalidate>
                <label for="username">Enter your admin username</label>
                <input
                    type="text"
                    id="username"
                    name="username"
                    required
                    autocomplete="username"
                    placeholder="Your username"
                    value="<?= htmlspecialchars($username) ?>"
                    aria-describedby="usernameHelp"
                />
                <small id="usernameHelp" style="color:#6c757d; font-size: 12px; display: block; margin-bottom: 15px;">
                    We'll check if your username exists.
                </small>

                <input type="hidden" name="step" value="1" />
                <button type="submit" aria-label="Submit username to reset password">Next</button>
            </form>
            <div class="links" role="region" aria-label="Helpful links">
                <a href="create_admin.php" aria-label="Create a new admin user">Create New Admin Account</a>
                |
                <a href="login.php" aria-label="Back to login page">Back to Login</a>
            </div>

        <?php elseif ($step === 2): ?>
            <!-- Step 2: New password input -->
            <form method="POST" action="" novalidate>
                <p style="font-weight: 600; margin-bottom: 20px; color:#264653;">
                    Username <strong><?= htmlspecialchars($username) ?></strong> found.<br>
                    Please enter your new password.
                </p>

                <label for="new_password">New Password</label>
                <input
                    type="password"
                    id="new_password"
                    name="new_password"
                    required
                    autocomplete="new-password"
                    placeholder="At least 8 characters"
                    aria-describedby="passwordHelp"
                />
                <label for="confirm_password">Confirm New Password</label>
                <input
                    type="password"
                    id="confirm_password"
                    name="confirm_password"
                    required
                    autocomplete="new-password"
                    placeholder="Re-enter your new password"
                />
                <small id="passwordHelp" style="color:#6c757d; font-size: 12px; display: block; margin-bottom: 20px;">
                    Password must be at least 8 characters long.
                </small>

                <input type="hidden" name="username" value="<?= htmlspecialchars($username) ?>" />
                <input type="hidden" name="step" value="2" />
                <button type="submit" aria-label="Reset password">Reset Password</button>
            </form>
            <div class="links" role="region" aria-label="Helpful links">
                <a href="forgot_password.php" aria-label="Restart forgot password process">Try Another Username</a>
                |
                <a href="login.php" aria-label="Back to login page">Back to Login</a>
            </div>
        <?php endif; ?>
    </main>
</body>
</html>
