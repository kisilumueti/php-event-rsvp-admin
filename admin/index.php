<?php
session_start();
require_once '../config/db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

// Pagination variables
$limit = 20;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Search and filter parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$filterStatus = isset($_GET['status']) ? trim($_GET['status']) : '';
$allowedStatuses = ['confirmed', 'declined', 'waitlisted', 'pending'];

// Sorting parameters
$allowedSortColumns = ['name', 'email', 'rsvp_status', 'created_at'];
$sort = isset($_GET['sort']) && in_array($_GET['sort'], $allowedSortColumns) ? $_GET['sort'] : 'created_at';
$order = isset($_GET['order']) && in_array(strtolower($_GET['order']), ['asc', 'desc']) ? strtoupper($_GET['order']) : 'DESC';

// Prepare search and filter SQL parts
$whereClauses = [];
$params = [];

if ($search !== '') {
    $whereClauses[] = "(name LIKE :search OR email LIKE :search)";
    $params[':search'] = "%$search%";
}

if ($filterStatus !== '' && in_array($filterStatus, $allowedStatuses)) {
    $whereClauses[] = "rsvp_status = :status";
    $params[':status'] = $filterStatus;
}

$whereSQL = '';
if (count($whereClauses) > 0) {
    $whereSQL = "WHERE " . implode(' AND ', $whereClauses);
}

// Fetch summary statistics
$summaryStmt = $pdo->query("
    SELECT 
        COUNT(*) AS total_guests,
        SUM(rsvp_status = 'confirmed') AS confirmed,
        SUM(rsvp_status = 'declined') AS declined,
        SUM(rsvp_status = 'waitlisted') AS waitlisted,
        SUM(rsvp_status = 'pending' OR rsvp_status IS NULL) AS pending
    FROM guests
");
$summary = $summaryStmt->fetch(PDO::FETCH_ASSOC);

// Fetch seat limits
$seatLimitStmt = $pdo->query("SELECT max_seats, current_reserved FROM seat_limit WHERE id = 1");
$seatLimit = $seatLimitStmt->fetch(PDO::FETCH_ASSOC);
$seatsAvailable = max(0, $seatLimit['max_seats'] - $seatLimit['current_reserved']);

// Fetch total guests count (with filters)
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM guests $whereSQL");
$countStmt->execute($params);
$totalGuests = $countStmt->fetchColumn();
$totalPages = ceil($totalGuests / $limit);

// Fetch guests with pagination, filters and sorting
$sql = "SELECT * FROM guests $whereSQL ORDER BY $sort $order LIMIT :limit OFFSET :offset";
$guestsStmt = $pdo->prepare($sql);

foreach ($params as $key => $val) {
    $guestsStmt->bindValue($key, $val);
}
$guestsStmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$guestsStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$guestsStmt->execute();
$guests = $guestsStmt->fetchAll(PDO::FETCH_ASSOC);

function sortLink($column, $label, $currentSort, $currentOrder, $search, $status) {
    $order = 'ASC';
    $arrow = '';
    if ($currentSort === $column) {
        if ($currentOrder === 'ASC') {
            $order = 'DESC';
            $arrow = ' ▲';
        } else {
            $order = 'ASC';
            $arrow = ' ▼';
        }
    }
    $url = "?sort=$column&order=$order";
    if ($search !== '') $url .= "&search=" . urlencode($search);
    if ($status !== '') $url .= "&status=" . urlencode($status);
    return "<a href='$url'>$label$arrow</a>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Admin Dashboard - Event RSVP System</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f8f9fa;
            color: #333;
            padding: 2em;
        }
        h1 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        nav a {
            margin-right: 15px;
            font-weight: 600;
            text-decoration: none;
            color: #3498db;
        }
        nav a:hover {
            text-decoration: underline;
        }
        .logout {
            float: right;
            font-weight: 600;
            color: #e74c3c;
        }
        .summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1em;
            margin: 2em 0;
        }
        .summary div {
            background: #fff;
            border-radius: 12px;
            padding: 1em;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            font-weight: 600;
            text-align: center;
        }
        .progress-bar {
            width: 100%;
            background: #ddd;
            height: 16px;
            border-radius: 10px;
            overflow: hidden;
            margin-top: 8px;
        }
        .progress-bar-inner {
            height: 100%;
            background: #2ecc71;
            width: 0;
            transition: width 0.5s ease;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            background: #fff;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            border-radius: 10px;
            overflow: hidden;
        }
        th, td {
            padding: 10px;
            border: 1px solid #eee;
        }
        th {
            background: #ecf0f1;
            text-align: left;
        }
        .pagination {
            margin-top: 20px;
            text-align: center;
        }
        .pagination a {
            padding: 8px 12px;
            margin: 0 4px;
            border-radius: 5px;
            background: #ddd;
            color: #333;
            text-decoration: none;
        }
        .pagination a.active {
            background: #3498db;
            color: white;
        }
        form.search-filter {
            margin: 1em 0;
        }
        form.search-filter input,
        form.search-filter select,
        form.search-filter button {
            padding: 8px;
            margin-right: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }
        form.search-filter button {
            background: #3498db;
            color: white;
            font-weight: 600;
            border: none;
        }
        .toggle-seat {
            padding: 6px 10px;
            background: #27ae60;
            border: none;
            color: white;
            border-radius: 5px;
            cursor: pointer;
        }
        .toggle-seat.no-seat {
            background: #e74c3c;
        }
    </style>
    <script>
        async function toggleSeat(guestId, button) {
            const resp = await fetch('toggle_seat.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({guest_id: guestId})
            });
            const data = await resp.json();
            if(data.success) {
                button.textContent = data.seat_reserved ? 'Yes' : 'No';
                button.classList.toggle('no-seat', !data.seat_reserved);
            } else {
                alert('Failed to update seat status.');
            }
        }
        window.onload = () => {
            const bar = document.querySelector('.progress-bar-inner');
            if(bar) {
                const available = parseInt(bar.dataset.available);
                const maxSeats = parseInt(bar.dataset.maxseats);
                const percent = (available / maxSeats) * 100;
                bar.style.width = percent + '%';
            }
        }
    </script>
</head>
<body>
    <h1>Admin Dashboard - Event RSVP System</h1>
    <a href="logout.php" class="logout">Logout</a>
    <nav>
        <a href="index.php">Dashboard</a>
        <a href="upload_guests.php">Upload Guests</a>
        <a href="export_data.php">Export Data</a>
        <a href="resend_invites.php">Resend Invites</a>
        <a href="create_admin.php">Create Admin</a>
        <a href="send_invites.php">Send Invites</a>
    </nav>

    <div class="summary">
        <div>Total Guests<br><?= htmlspecialchars($summary['total_guests']) ?></div>
        <div>Confirmed<br><?= htmlspecialchars($summary['confirmed']) ?></div>
        <div>Declined<br><?= htmlspecialchars($summary['declined']) ?></div>
        <div>Waitlisted<br><?= htmlspecialchars($summary['waitlisted']) ?></div>
        <div>Pending<br><?= htmlspecialchars($summary['pending']) ?></div>
        <div>Seats Available<br><?= $seatsAvailable ?>
            <div class="progress-bar" title="Seat availability">
                <div class="progress-bar-inner" data-available="<?= $seatsAvailable ?>" data-maxseats="<?= $seatLimit['max_seats'] ?>"></div>
            </div>
        </div>
    </div>

    <form method="GET" class="search-filter">
        <input type="text" name="search" placeholder="Search name or email" value="<?= htmlspecialchars($search) ?>" />
        <select name="status">
            <option value="">All RSVP Status</option>
            <?php foreach ($allowedStatuses as $statusOpt): ?>
                <option value="<?= $statusOpt ?>" <?= $filterStatus === $statusOpt ? 'selected' : '' ?>><?= ucfirst($statusOpt) ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Filter</button>
        <a href="export_data.php" style="margin-left: 20px;">Export CSV</a>
    </form>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th><?= sortLink('name', 'Name', $sort, $order, $search, $filterStatus) ?></th>
                <th><?= sortLink('email', 'Email', $sort, $order, $search, $filterStatus) ?></th>
                <th><?= sortLink('rsvp_status', 'RSVP Status', $sort, $order, $search, $filterStatus) ?></th>
                <th>Seat Reserved</th>
                <th><?= sortLink('created_at', 'Registered At', $sort, $order, $search, $filterStatus) ?></th>
            </tr>
        </thead>
        <tbody>
            <?php if ($guests): ?>
                <?php foreach ($guests as $index => $guest): ?>
                    <tr>
                        <td><?= htmlspecialchars($offset + $index + 1) ?></td>
                        <td><?= htmlspecialchars($guest['name']) ?></td>
                        <td><?= htmlspecialchars($guest['email']) ?></td>
                        <td><?= htmlspecialchars(ucfirst($guest['rsvp_status'] ?? 'pending')) ?></td>
                        <td>
                            <button class="toggle-seat <?= $guest['seat_reserved'] ? '' : 'no-seat' ?>" onclick="toggleSeat(<?= (int)$guest['id'] ?>, this)">
                                <?= $guest['seat_reserved'] ? 'Yes' : 'No' ?>
                            </button>
                        </td>
                        <td><?= htmlspecialchars($guest['created_at']) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="6">No guests found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="pagination">
        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
            <a href="?page=<?= $p ?>&search=<?= urlencode($search) ?>&status=<?= urlencode($filterStatus) ?>&sort=<?= $sort ?>&order=<?= strtolower($order) ?>" class="<?= $p == $page ? 'active' : '' ?>">
                <?= $p ?>
            </a>
        <?php endfor; ?>
    </div>
</body>
</html>
