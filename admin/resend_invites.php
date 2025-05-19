<?php
// admin/resend_invites.php

session_start();

// Check admin login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Email sender config
define('EMAIL_FROM', 'muetijohnbosco35@gmail.com');
define('EMAIL_NAME', 'Event Team');
define('EMAIL_SUBJECT', 'Your RSVP Invitation');
define('BASE_RSVP_URL', 'http://localhost/event-rsvp-system/rsvp/index.php?token=');
define('EMAIL_APP_PASSWORD', 'dhaovvzdcstqpakg'); // Your Gmail app password

$message = '';
$batchSize = 50;  // smaller batch for resends

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resend'])) {
    try {
        // Select guests whose last email attempt failed
        $stmt = $pdo->prepare("
            SELECT g.* FROM guests g
            JOIN (
                SELECT guest_id, MAX(id) AS max_log_id
                FROM email_logs
                GROUP BY guest_id
            ) latest_log ON g.id = latest_log.guest_id
            JOIN email_logs e ON e.id = latest_log.max_log_id
            WHERE e.status = 'failed'
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $batchSize, PDO::PARAM_INT);
        $stmt->execute();
        $failedGuests = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (!$failedGuests) {
            $message = "<p>✅ No failed invites to resend.</p>";
        } else {
            $sentCount = 0;
            foreach ($failedGuests as $guest) {
                try {
                    $rsvpLink = BASE_RSVP_URL . urlencode($guest['rsvp_token']);
                    $mailBody = "
                        <p>Dear {$guest['name']},</p>
                        <p>You are invited to our upcoming event. Please confirm your attendance by clicking the button below:</p>
                        <p><a href='{$rsvpLink}' style='padding:10px 20px; background:#007bff; color:#fff; text-decoration:none;'>Confirm RSVP</a></p>
                        <p>Thank you,<br>Event Management Team</p>
                    ";

                    $mail = new PHPMailer(true);
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = EMAIL_FROM;
                    $mail->Password = EMAIL_APP_PASSWORD;
                    $mail->SMTPSecure = 'tls';
                    $mail->Port = 587;

                    $mail->setFrom(EMAIL_FROM, EMAIL_NAME);
                    $mail->addAddress($guest['email'], $guest['name']);
                    $mail->isHTML(true);
                    $mail->Subject = EMAIL_SUBJECT;
                    $mail->Body = $mailBody;

                    $mail->send();

                    // Log success (new log entry)
                    $logStmt = $pdo->prepare("INSERT INTO email_logs (guest_id, status) VALUES (:guest_id, 'sent')");
                    $logStmt->execute([':guest_id' => $guest['id']]);

                    $sentCount++;

                } catch (Exception $e) {
                    // Log failure (new log entry)
                    $logStmt = $pdo->prepare("
                        INSERT INTO email_logs (guest_id, status, error_message)
                        VALUES (:guest_id, 'failed', :error)
                    ");
                    $logStmt->execute([
                        ':guest_id' => $guest['id'],
                        ':error' => $e->getMessage()
                    ]);
                }
            }
            $message = "<p>✅ Resent invites to {$sentCount} guests.</p>";
        }

    } catch (PDOException $e) {
        $message = "<p>❌ Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin - Resend Failed Invites</title>
</head>
<body>
    <h1>Resend Failed RSVP Invites</h1>
    <p>This tool resends invitations to guests whose previous email delivery failed.</p>

    <?php if ($message) echo $message; ?>

    <form method="POST">
        <button type="submit" name="resend">Resend Failed Invites</button>
    </form>

    <p><a href="index.php">Back to Dashboard</a></p>
</body>
</html>
