<?php
session_start();
require_once '../config/db.php';

// Check admin login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    $file = $_FILES['csv_file'];

    if ($file['error'] === UPLOAD_ERR_OK) {
        $tmpName = $file['tmp_name'];
        $handle = fopen($tmpName, 'r');

        if ($handle !== false) {
            $rowCount = 0;
            $insertCount = 0;

            while (($data = fgetcsv($handle, 1000, ',')) !== false) {
                $rowCount++;

                // Skip header row (assuming first row is header)
                if ($rowCount === 1 && strtolower($data[0]) === 'name' && strtolower($data[1]) === 'email') {
                    continue;
                }

                // Validate CSV columns
                $name = trim($data[0] ?? '');
                $email = trim($data[1] ?? '');

                if (!empty($name) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    // Generate a random token for RSVP
                    $token = bin2hex(random_bytes(16));

                    // Insert into guests table
                    $stmt = $pdo->prepare("INSERT INTO guests (name, email, rsvp_token, created_at) VALUES (?, ?, ?, NOW())");
                    $stmt->execute([$name, $email, $token]);
                    $insertCount++;
                }
            }

            fclose($handle);
            $message = "Uploaded $insertCount guests successfully.";
        } else {
            $message = "Error opening the CSV file.";
        }
    } else {
        $message = "File upload error. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Upload Guests - Admin - Event RSVP System</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 2em; background: #f9f9f9; }
        .container {
            max-width: 500px; margin: auto; background: white; padding: 20px; box-shadow: 0 0 10px rgba(0,0,0,0.1);
            border-radius: 6px;
        }
        h2 { text-align: center; margin-bottom: 20px; }
        input[type="file"] { margin-top: 10px; }
        button {
            margin-top: 15px; padding: 10px; width: 100%; background: #007bff; color: white;
            border: none; border-radius: 4px; cursor: pointer; font-size: 16px;
        }
        button:hover { background: #0056b3; }
        .message {
            margin-top: 15px; padding: 10px; background: #d4edda; color: #155724; border-radius: 4px;
            border: 1px solid #c3e6cb;
            text-align: center;
        }
        .back-link {
            display: block; margin-top: 20px; text-align: center; text-decoration: none; color: #007bff;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Upload Guests CSV</h2>

        <form method="POST" enctype="multipart/form-data">
            <label for="csv_file">Choose CSV file (format: name,email):</label>
            <input type="file" name="csv_file" id="csv_file" accept=".csv" required />
            <button type="submit">Upload</button>
        </form>

        <?php if ($message): ?>
            <div class="message"><?= htmlspecialchars($message) ?></div>
        <?php endif; ?>

        <a href="index.php" class="back-link">Back to Admin Dashboard</a>
    </div>
</body>
</html>
