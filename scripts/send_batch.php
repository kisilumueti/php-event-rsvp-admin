<?php
// scripts/send_batch.php

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../utils/functions.php';
require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Email sender config
define('EMAIL_FROM', 'muetijohnbosco35@gmail.com');
define('EMAIL_NAME', 'Event Team');
define('EMAIL_SUBJECT', 'Your RSVP Invitation');
define('BASE_RSVP_URL', 'http://localhost/event-rsvp-system/rsvp/index.php?token=');

// Gmail app password
define('EMAIL_APP_PASSWORD', 'dhaovvzdcstqpakg');

// Batch size
$batchSize = 100;

try {
    // Select guests who have not received email
    $stmt = $pdo->prepare("
        SELECT g.* FROM guests g
        LEFT JOIN email_logs e ON g.id = e.guest_id
        WHERE e.id IS NULL
        LIMIT :limit
    ");
    $stmt->bindValue(':limit', $batchSize, PDO::PARAM_INT);
    $stmt->execute();
    $guests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$guests) {
        echo "✅ All guests have been emailed.\n";
        exit;
    }

    foreach ($guests as $guest) {
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
            $mail->Password = EMAIL_APP_PASSWORD; // Use App Password
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->setFrom(EMAIL_FROM, EMAIL_NAME);
            $mail->addAddress($guest['email'], $guest['name']);
            $mail->isHTML(true);
            $mail->Subject = EMAIL_SUBJECT;
            $mail->Body = $mailBody;

            $mail->send();

            // Log success
            $logStmt = $pdo->prepare("INSERT INTO email_logs (guest_id, status) VALUES (:guest_id, 'sent')");
            $logStmt->execute([':guest_id' => $guest['id']]);

            echo "✅ Email sent to {$guest['email']}\n";

        } catch (Exception $e) {
            // Log failure
            $logStmt = $pdo->prepare("
                INSERT INTO email_logs (guest_id, status, error_message)
                VALUES (:guest_id, 'failed', :error)
            ");
            $logStmt->execute([
                ':guest_id' => $guest['id'],
                ':error' => $e->getMessage()
            ]);
            echo "❌ Failed to send to {$guest['email']}: " . $e->getMessage() . "\n";
        }
    }
} catch (PDOException $e) {
    echo "❌ Database error: " . $e->getMessage();
}
