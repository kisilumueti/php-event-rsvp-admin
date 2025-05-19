<?php
// rsvp/index.php

require_once '../config/db.php';
require_once '../utils/functions.php';

// Get token from URL query string
$token = $_GET['token'] ?? '';
if (empty($token)) {
    die('Invalid RSVP link.');
}

// Fetch guest by token
$stmt = $pdo->prepare("SELECT * FROM guests WHERE rsvp_token = ? LIMIT 1");
$stmt->execute([$token]);
$guest = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$guest) {
    die('Invalid or expired RSVP link.');
}

// If guest already responded (not pending), show message and exit
if ($guest['rsvp_status'] !== 'pending') {
    echo "<h2>You have already responded with: <strong>" . htmlspecialchars($guest['rsvp_status']) . "</strong>.</h2>";
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $response = $_POST['rsvp'] ?? '';

    // Validate response value
    if (!in_array($response, ['confirmed', 'declined'])) {
        die('Invalid response.');
    }

    if ($response === 'confirmed') {
        // Check seat availability
        $seatCheck = $pdo->query("SELECT max_seats, current_reserved FROM seat_limit WHERE id = 1")->fetch(PDO::FETCH_ASSOC);

        if ($seatCheck['current_reserved'] >= $seatCheck['max_seats']) {
            // Seats full, set status to waitlisted
            $response = 'waitlisted';

            // Update guest RSVP status to waitlisted
            $stmtUpdate = $pdo->prepare("UPDATE guests SET rsvp_status = :status WHERE id = :id");
            $stmtUpdate->execute([':status' => $response, ':id' => $guest['id']]);

            echo "<h2>All seats are taken. You have been waitlisted.</h2>";
            exit;
        }

        // Seats available: reserve seat and update records inside transaction
        try {
            $pdo->beginTransaction();

            // Update guest RSVP status and mark seat_reserved
            $stmtUpdateGuest = $pdo->prepare("UPDATE guests SET rsvp_status = :status, seat_reserved = 1 WHERE id = :id");
            $stmtUpdateGuest->execute([':status' => 'confirmed', ':id' => $guest['id']]);

            // Increment current_reserved count in seat_limit table
            $pdo->prepare("UPDATE seat_limit SET current_reserved = current_reserved + 1 WHERE id = 1")->execute();

            // Insert reservation record (seat_number is nullable, can be assigned later if needed)
            $stmtInsertRes = $pdo->prepare("INSERT INTO reservations (guest_id) VALUES (:guest_id)");
            $stmtInsertRes->execute([':guest_id' => $guest['id']]);

            $pdo->commit();

            echo "<h2>Thank you for confirming. Your seat is reserved.</h2>";
            exit;
        } catch (Exception $e) {
            $pdo->rollBack();
            die("Error processing your RSVP: " . $e->getMessage());
        }
    } else {
        // For declined responses, update RSVP status accordingly
        $stmtUpdate = $pdo->prepare("UPDATE guests SET rsvp_status = :status WHERE id = :id");
        $stmtUpdate->execute([':status' => $response, ':id' => $guest['id']]);

        echo "<h2>Thank you for your response: " . htmlspecialchars($response) . ".</h2>";
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>RSVP Confirmation</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 2em; }
        button { padding: 10px 20px; margin: 10px; font-size: 1em; cursor: pointer; }
        h2 { color: #333; }
    </style>
</head>
<body>
    <h2>Hello, <?= htmlspecialchars($guest['name']) ?>. Please confirm your attendance:</h2>
    <form method="POST">
        <button type="submit" name="rsvp" value="confirmed">Yes, I will attend</button>
        <button type="submit" name="rsvp" value="declined">No, I can't attend</button>
    </form>
</body>
</html>
