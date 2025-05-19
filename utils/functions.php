<?php
// utils/functions.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config/db.php';

function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

function seatsAvailable($pdo) {
    $stmt = $pdo->query("SELECT max_seats, current_reserved FROM seat_limit WHERE id = 1");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return ($row['max_seats'] - $row['current_reserved']) > 0;
}

function reserveSeat($pdo, $guest_id) {
    try {
        $pdo->beginTransaction();

        $stmt = $pdo->query("SELECT max_seats, current_reserved FROM seat_limit WHERE id = 1 FOR UPDATE");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row['current_reserved'] >= $row['max_seats']) {
            $pdo->rollBack();
            return false;
        }

        $seat_number = $row['current_reserved'] + 1;

        $stmt = $pdo->prepare("INSERT INTO reservations (guest_id, seat_number) VALUES (?, ?)");
        $stmt->execute([$guest_id, $seat_number]);

        $stmt = $pdo->prepare("UPDATE seat_limit SET current_reserved = current_reserved + 1 WHERE id = 1");
        $stmt->execute();

        $pdo->commit();
        return $seat_number;

    } catch (Exception $e) {
        $pdo->rollBack();
        return false;
    }
}

function sendRSVPEmail($guest_email, $guest_name, $token) {
    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'muetijohnbosco35@gmail.com';
        $mail->Password   = 'your_app_password_here'; // Use App Password from Gmail
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('muetijohnbosco35@gmail.com', 'Event Organizer');
        $mail->addAddress($guest_email, $guest_name);

        $mail->isHTML(true);
        $mail->Subject = 'You're Invited to the Event!';

        $rsvp_link = "http://localhost/event-rsvp-system/rsvp/index.php?token=$token";
        $mail->Body = "<p>Dear $guest_name,</p>
                        <p>You are invited to our exclusive event. Please confirm your attendance by clicking the link below:</p>
                        <p><a href='$rsvp_link'>$rsvp_link</a></p>
                        <p>Thank you!</p>";

        $mail->send();
        return ["status" => true];
    } catch (Exception $e) {
        return ["status" => false, "error" => $mail->ErrorInfo];
    }
}
