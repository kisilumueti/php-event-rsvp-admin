<?php
session_start();
require_once '../config/db.php';

// Redirect if already logged in
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        // Fetch admin user by username
        $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $admin = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($admin && password_verify($password, $admin['password_hash'])) {
            // Login success
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_username'] = $admin['username'];

            header('Location: index.php');
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Admin Login - Event RSVP System</title>
    <style>
        /* Reset and base */
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
            color: #333;
        }
        .login-container {
            background: #ffffffee;
            padding: 30px 40px;
            border-radius: 12px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
            max-width: 420px;
            width: 100%;
            transition: transform 0.3s ease;
        }
        .login-container:hover {
            transform: translateY(-4px);
        }
        h2 {
            text-align: center;
            margin-bottom: 25px;
            color: #264653;
            font-weight: 700;
            letter-spacing: 1px;
        }
        label {
            display: block;
            font-weight: 600;
            margin-bottom: 6px;
            color: #264653;
        }
        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px 14px;
            margin-bottom: 18px;
            border: 2px solid #a8dadc;
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
        .error {
            background-color: #f8d7da;
            color: #842029;
            border: 1px solid #f5c2c7;
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 20px;
            font-weight: 600;
            box-shadow: 0 2px 8px #f5c2c7aa;
            animation: shake 0.3s ease;
        }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-8px); }
            40%, 80% { transform: translateX(8px); }
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
        .create-admin-btn {
            background-color: #264653;
            margin-top: 15px;
        }
        .create-admin-btn:hover, .create-admin-btn:focus {
            background-color: #1b2e3b;
        }
        .form-footer {
            margin-top: 20px;
            text-align: center;
        }
        .forgot-password {
            font-size: 14px;
            color: #2a9d8f;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }
        .forgot-password:hover,
        .forgot-password:focus {
            color: #21867a;
            text-decoration: underline;
        }

        /* Responsive */
        @media (max-width: 480px) {
            .login-container {
                padding: 25px 20px;
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
    <div class="login-container" role="main" aria-label="Admin login form">
        <h2>Admin Login</h2>

        <?php if ($error): ?>
            <div class="error" role="alert" aria-live="assertive"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST" action="" novalidate>
            <label for="username">Username</label>
            <input
                type="text"
                id="username"
                name="username"
                required
                autocomplete="username"
                aria-describedby="usernameHelp"
                autofocus
                value="<?= isset($_POST['username']) ? htmlspecialchars($_POST['username']) : '' ?>"
                placeholder="Enter your username"
            />
            <small id="usernameHelp" style="color:#6c757d; font-size: 12px; display: block; margin-bottom: 12px;">
                Use your admin username
            </small>

            <label for="password">Password</label>
            <input
                type="password"
                id="password"
                name="password"
                required
                autocomplete="current-password"
                placeholder="Enter your password"
                aria-describedby="passwordHelp"
            />
            <small id="passwordHelp" style="color:#6c757d; font-size: 12px; display: block; margin-bottom: 10px;">
                Your secure password
            </small>

            <button type="submit" aria-label="Log in to admin dashboard">Login</button>
        </form>

        <div class="form-footer">
            <a href="forgot_password.php" class="forgot-password" aria-label="Forgot your password? Reset here">Forgot Password?</a>
        </div>

        <!-- Button linking to create_admin.php -->
        <form method="GET" action="create_admin.php" style="margin-top: 15px;">
            <button type="submit" class="create-admin-btn" aria-label="Create new admin user">Create New Admin User</button>
        </form>
    </div>
</body>
</html>
