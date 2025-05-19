<?php
// admin/export_data.php

session_start();

// Check admin login
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/../config/db.php';

try {
    // Fetch all guest data with relevant fields
    $stmt = $pdo->query("
        SELECT 
            id, 
            name, 
            email, 
            rsvp_token, 
            rsvp_status, 
            seat_reserved, 
            created_at, 
            updated_at
        FROM guests
        ORDER BY created_at DESC
    ");
    $guests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$guests) {
        die('No guest data found to export.');
    }

    // Set headers for CSV download
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=event_guests_export_' . date('Y-m-d') . '.csv');

    $output = fopen('php://output', 'w');

    // CSV column headers
    fputcsv($output, [
        'ID',
        'Name',
        'Email',
        'RSVP Token',
        'RSVP Status',
        'Seat Reserved',
        'Created At',
        'Updated At'
    ]);

    // Write each guest row to CSV
    foreach ($guests as $guest) {
        fputcsv($output, [
            $guest['id'],
            $guest['name'],
            $guest['email'],
            $guest['rsvp_token'],
            $guest['rsvp_status'],
            $guest['seat_reserved'] ? 'Yes' : 'No',
            $guest['created_at'],
            $guest['updated_at']
        ]);
    }

    fclose($output);
    exit;

} catch (PDOException $e) {
    die('Database error: ' . $e->getMessage());
}
