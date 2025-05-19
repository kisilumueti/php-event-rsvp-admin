<?php
session_start();
require_once '../config/db.php';
require_once '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_invites'])) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.yourdomain.co.ke';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'your_email@yourdomain.co.ke';
        $mail->Password   = 'your_password';
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;
        $mail->setFrom('no-reply@yourdomain.co.ke', 'Event RSVP');
        $mail->isHTML(true);
        $mail->Subject = 'You are Invited! RSVP for the Event';

        $stmt = $pdo->prepare("
            SELECT g.id, g.name, g.email 
            FROM guests g 
            LEFT JOIN email_logs l ON g.id = l.guest_id 
            WHERE l.guest_id IS NULL
            LIMIT 50
        ");
        $stmt->execute();
        $guests = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$guests) {
            $message = "All guests have already been sent invitations.";
        } else {
            $sentCount = 0;
            $failedEmails = [];

            foreach ($guests as $guest) {
                try {
                    $mail->clearAddresses();
                    $mail->addAddress($guest['email'], $guest['name']);
                    $rsvpLink = "http://yourdomain.co.ke/rsvp/index.php?guest_id=" . $guest['id'];

                    $mail->Body = "
                        <p>Dear " . htmlspecialchars($guest['name']) . ",</p>
                        <p>You are cordially invited to our event. Please confirm your attendance by clicking the link below:</p>
                        <p><a href='$rsvpLink'>$rsvpLink</a></p>
                        <p>Thank you!</p>
                    ";

                    $mail->send();
                    $logStmt = $pdo->prepare("INSERT INTO email_logs (guest_id, sent_at) VALUES (?, NOW())");
                    $logStmt->execute([$guest['id']]);
                    $sentCount++;
                } catch (Exception $e) {
                    $failedEmails[] = $guest['email'];
                }
            }

            $message = "Invitations sent: $sentCount";
            if ($failedEmails) {
                $error = "Failed to send invites to: " . implode(', ', $failedEmails);
            }
        }
    } catch (Exception $e) {
        $error = "Mailer Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Send Invites - Admin Panel</title>
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">Event RSVP Admin</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="index.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="upload_guests.php">Upload Guests</a></li>
                <li class="nav-item"><a class="nav-link" href="export_data.php">Export Data</a></li>
                <li class="nav-item"><a class="nav-link" href="resend_invites.php">Resend Invites</a></li>
                <li class="nav-item"><a class="nav-link" href="create_admin.php">Create Admin</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container">
    <div class="card shadow-sm">
        <div class="card-body">
            <h2 class="card-title text-center mb-4">Send Invitations</h2>

            <?php if ($message): ?>
                <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" action="" class="text-center">
                <button type="submit" name="send_invites" class="btn btn-success btn-lg">
                    <i class="bi bi-send-fill"></i> Send Invites to Pending Guests
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Bootstrap JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
