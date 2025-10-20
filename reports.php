<?php
// DB connection info
$host = 'localhost';
$dbname = 'librarymanagementsystem01';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Book status counts
    $totalBooks = $pdo->query("SELECT COUNT(*) FROM books")->fetchColumn();
    $availableBooks = $pdo->query("SELECT COUNT(*) FROM books WHERE available_copies > 0")->fetchColumn();
    $borrowedBooks = $pdo->query("SELECT COUNT(*) FROM borrowings WHERE status = 'borrowed'")->fetchColumn();
    $overdueBooks = $pdo->query("SELECT COUNT(*) FROM borrowings WHERE status = 'overdue'")->fetchColumn();

    // Borrowing records
    $borrowings = $pdo->query("
        SELECT user_id, book_id, borrow_date, return_date, status 
        FROM borrowings 
        ORDER BY borrow_date DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    // Reservation records with fulfillment check
    $reservationsRaw = $pdo->query("
        SELECT 
            r.reservation_id, 
            r.user_id, 
            r.book_id, 
            b.title AS book_title, 
            r.reservation_date, 
            r.expiry_date,
            r.status
        FROM reservations r
        LEFT JOIN books b ON r.book_id = b.book_id
        ORDER BY r.reservation_date DESC
    ")->fetchAll(PDO::FETCH_ASSOC);

    $reservations = [];
    foreach ($reservationsRaw as $r) {
        // Default expiry: 7 days after reservation date
        $expiryDate = $r['expiry_date'] ?? date('Y-m-d H:i:s', strtotime($r['reservation_date'].' +7 days'));

        // Check if a borrowing exists for this user & book with status 'borrowed'
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM borrowings WHERE user_id = ? AND book_id = ? AND status = 'borrowed'");
        $stmt->execute([$r['user_id'], $r['book_id']]);
        $borrowedCount = $stmt->fetchColumn();
        $status = ($borrowedCount > 0) ? 'fulfilled' : $r['status'];

        $reservations[] = [
            'reservation_id' => $r['reservation_id'],
            'user_id' => $r['user_id'],
            'book_id' => $r['book_id'],
            'book_title' => $r['book_title'],
            'reservation_date' => $r['reservation_date'],
            'expiry_date' => $expiryDate,
            'status' => $status
        ];
    }

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>View Reports - KnowledgeNest LMS</title>
<link rel="stylesheet" href="reports.css" />
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />
</head>
<body>
<div class="container">
<aside class="sidebar">
    <div class="sidebar-header"><h2>KnowledgeNest LMS</h2></div>
    <nav class="menu">
        <a href="librarianprofile.php" class="menu-item"><i class="fas fa-user-circle"></i> My Profile</a>
        <a href="managebooks.php" class="menu-item"><i class="fas fa-book"></i> Manage Books</a>
        <a href="manageborrowings.php" class="menu-item"><i class="fas fa-book-reader"></i> Manage Borrowings</a>
        <a href="managemembers.php" class="menu-item"><i class="fas fa-users"></i> Manage Members</a>
        <a href="notifications.php" class="menu-item"><i class="fas fa-bell"></i> Notifications</a>
        <a href="announcements.php" class="menu-item"><i class="fas fa-bullhorn"></i> Announcements</a>
        <a href="reports.php" class="menu-item active"><i class="fas fa-chart-bar"></i> View Reports</a>
        <a href="index.html" class="menu-item logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </nav>
</aside>

<main class="main-content">

<!-- Book Status Report -->
<section class="report-card">
<h2>Book Status Report</h2>
<table class="report-table">
<thead>
<tr>
<th>Total</th>
<th>Available</th>
<th>Borrowed</th>
<th>Overdue</th>
</tr>
</thead>
<tbody>
<tr>
<td><?= htmlspecialchars($totalBooks ?? '') ?></td>
<td><?= htmlspecialchars($availableBooks ?? '') ?></td>
<td><?= htmlspecialchars($borrowedBooks ?? '') ?></td>
<td><?= htmlspecialchars($overdueBooks ?? '') ?></td>
</tr>
</tbody>
</table>
</section>

<!-- Borrowing Activity Report -->
<section class="report-card">
<h2>Borrowing Activity Report</h2>
<table class="report-table">
<thead>
<tr>
<th>User ID</th>
<th>Book ID</th>
<th>Borrow Date</th>
<th>Return Date</th>
<th>Status</th>
</tr>
</thead>
<tbody>
<?php foreach ($borrowings as $b): ?>
<tr>
<td><?= htmlspecialchars($b['user_id'] ?? '') ?></td>
<td><?= htmlspecialchars($b['book_id'] ?? '') ?></td>
<td><?= htmlspecialchars($b['borrow_date'] ?? '') ?></td>
<td><?= htmlspecialchars($b['return_date'] ?? '-') ?></td>
<td><?= htmlspecialchars(ucfirst($b['status'] ?? '')) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</section>

<!-- Reservation Report -->
<section class="report-card">
<h2>Reservation Report</h2>
<table class="report-table">
<thead>
<tr>
<th>Reservation ID</th>
<th>User ID</th>
<th>Book ID</th>
<th>Book Title</th>
<th>Reservation Date</th>
<th>Expiry Date</th>
<th>Status</th>
</tr>
</thead>
<tbody>
<?php foreach ($reservations as $r): ?>
<tr>
<td><?= htmlspecialchars($r['reservation_id'] ?? '') ?></td>
<td><?= htmlspecialchars($r['user_id'] ?? '') ?></td>
<td><?= htmlspecialchars($r['book_id'] ?? '') ?></td>
<td><?= htmlspecialchars($r['book_title'] ?? '') ?></td>
<td><?= htmlspecialchars($r['reservation_date'] ?? '') ?></td>
<td><?= htmlspecialchars($r['expiry_date'] ?? '') ?></td>
<td><?= htmlspecialchars(ucfirst($r['status'] ?? '')) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</section>

</main>
</div>
</body>
</html>
