<?php
// toggle_seat.php

require_once 'db.php'; // Make sure this includes your PDO connection ($pdo)

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    echo json_encode(['error' => 'Invalid request method']);
    exit;
}

// Fetch and validate guest ID
$input = json_decode(file_get_contents('php://input'), true);
$guestId = isset($input['id']) ? (int)$input['id'] : 0;

if ($guestId <= 0) {
    http_response_code(400); // Bad Request
    echo json_encode(['error' => 'Invalid guest ID']);
    exit;
}

try {
    // Get current seat status
    $stmt = $pdo->prepare("SELECT seat_reserved FROM guests WHERE id = ?");
    $stmt->execute([$guestId]);
    $guest = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$guest) {
        http_response_code(404); // Not Found
        echo json_encode(['error' => 'Guest not found']);
        exit;
    }

    // Toggle seat_reserved status
    $newStatus = $guest['seat_reserved'] ? 0 : 1;

    $updateStmt = $pdo->prepare("UPDATE guests SET seat_reserved = ? WHERE id = ?");
    $updateStmt->execute([$newStatus, $guestId]);

    echo json_encode([
        'success' => true,
        'new_status' => $newStatus,
        'message' => $newStatus ? 'Seat reserved' : 'Seat unreserved'
    ]);

} catch (PDOException $e) {
    http_response_code(500); // Server error
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
