<?php
session_start();
require_once '../config/db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Validate inputs
    if (empty($username) || empty($password) || empty($confirm_password)) {
        $error = 'Please fill in all fields.';
    } elseif (strlen($username) < 3) {
        $error = 'Username must be at least 3 characters.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        // Check if username exists already
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM admin_users WHERE username = ?");
        $stmt->execute([$username]);
        $count = $stmt->fetchColumn();

        if ($count > 0) {
            $error = 'Username already exists. Please choose another.';
        } else {
            // Insert new admin user
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $insertStmt = $pdo->prepare("INSERT INTO admin_users (username, password_hash) VALUES (?, ?)");
            $insertStmt->execute([$username, $password_hash]);

            $success = "Admin user <strong>" . htmlspecialchars($username) . "</strong> created successfully!";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Create Admin User - Event RSVP System</title>
    <style>
        /* Styles consistent with other admin pages */
        * {
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            background: linear-gradient(135deg, #2a9d8f, #264653);
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
            border: 2px solid #81c784;
            border-radius: 8px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }
        input[type="text"]:focus,
        input[type="password"]:focus {
            outline: none;
            border-color: #2a9d8f;
            box-shadow: 0 0 8px #2a9d8f66;
        }
        button {
            width: 100%;
            padding: 14px 0;
            background-color: #2a9d8f;
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
            background-color: #21867a;
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
            color: #2a9d8f;
            text-decoration: none;
            font-weight: 600;
            margin: 0 10px;
            transition: color 0.3s ease;
        }
        .links a:hover,
        .links a:focus {
            color: #21867a;
            text-decoration: underline;
        }
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
    <main class="container" role="main" aria-label="Create new admin user form">
        <h2>Create Admin User</h2>

        <?php if ($error): ?>
            <div class="error" role="alert" aria-live="assertive"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success" role="alert" aria-live="polite"><?= $success ?></div>
        <?php endif; ?>

        <form method="POST" action="" novalidate>
            <label for="username">Username</label>
            <input
                type="text"
                id="username"
                name="username"
                required
                autocomplete="username"
                minlength="3"
                placeholder="Enter username"
                value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                aria-describedby="usernameHelp"
            />
            <small id="usernameHelp" style="color:#6c757d; font-size: 12px; display: block; margin-bottom: 15px;">
                Minimum 3 characters
            </small>

            <label for="password">Password</label>
            <input
                type="password"
                id="password"
                name="password"
                required
                autocomplete="new-password"
                minlength="8"
                placeholder="At least 8 characters"
                aria-describedby="passwordHelp"
            />
            <label for="confirm_password">Confirm Password</label>
            <input
                type="password"
                id="confirm_password"
                name="confirm_password"
                required
                autocomplete="new-password"
                minlength="8"
                placeholder="Re-enter password"
            />
            <small id="passwordHelp" style="color:#6c757d; font-size: 12px; display: block; margin-bottom: 20px;">
                Password must be at least 8 characters long.
            </small>

            <button type="submit" aria-label="Create admin user">Create Admin</button>
        </form>

        <div class="links" role="region" aria-label="Helpful links">
            <a href="login.php" aria-label="Back to login page">Back to Login</a>
            |
            <a href="forgot_password.php" aria-label="Forgot password page">Forgot Password?</a>
        </div>
    </main>
</body>
</html>
