<?php
session_start();

function alertAndRedirect($message, $redirectUrl = 'login.php') {
    echo "<script>alert('". addslashes($message) ."'); window.location.href = '$redirectUrl';</script>";
    exit();
}

$host = "localhost";
$username = "root";
$password = "";
$dbname = "librarymanagementsystem01";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    alertAndRedirect("Database connection failed: " . $e->getMessage());
}

// Check session
if (!isset($_SESSION['employee_id']) || !isset($_SESSION['role'])) {
    alertAndRedirect("Session variables missing. Please log in.");
}
if (strtolower($_SESSION['role']) !== 'librarian') {
    alertAndRedirect("Access denied. Please log in as librarian.");
}

$librarianEmployeeId = $_SESSION['employee_id'];

// Mark unread feedback notifications as read
try {
    $stmtMarkRead = $pdo->prepare("
        UPDATE notifications 
        SET is_read = 1 
        WHERE employee_id = ? AND is_read = 0 AND type = 'feedback'
    ");
    $stmtMarkRead->execute([$librarianEmployeeId]);
} catch (PDOException $e) {
    // fail silently
}

// Fetch feedback notifications with sender names
try {
    $stmt = $pdo->prepare("
        SELECT n.notification_id, n.user_id, n.message, n.created_at, n.is_read,
               COALESCE(CONCAT(u.first_name, ' ', u.last_name), 'Unknown User') AS sender_name
        FROM notifications n
        LEFT JOIN users u ON n.user_id = u.user_id
        WHERE n.type = 'feedback' AND n.employee_id = ?
        ORDER BY n.created_at DESC
    ");
    $stmt->execute([$librarianEmployeeId]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    alertAndRedirect("Failed to fetch notifications: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Feedback Notifications</title>
  <link rel="stylesheet" href="notifications.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
</head>
<body>
  <div class="layout">
    <aside class="sidebar">
      <div class="sidebar-header">
        <h2>KnowledgeNest LMS</h2>
      </div>
      <nav class="menu">
        <a href="librarianprofile.php" class="menu-item"><i class="fas fa-user-circle"></i> My profile</a>
        <a href="managebooks.php" class="menu-item"><i class="fas fa-book"></i> Manage Books</a>
        <a href="manageborrowings.php" class="menu-item"><i class="fas fa-book-reader"></i> Manage Borrowing</a>
        <a href="managemembers.php" class="menu-item"><i class="fas fa-users"></i> Manage Members</a>
        <a href="notifications.php" class="menu-item active"><i class="fas fa-bell"></i> Notifications</a>
        <a href="announcements.php" class="menu-item"><i class="fas fa-bullhorn"></i> Announcements</a>
        <a href="reports.php" class="menu-item"><i class="fas fa-chart-bar"></i> View Reports</a>
        <a href="index.html" onclick="return logout()" class="menu-item logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
      </nav>
    </aside>
    <main class="main-content">
      <h1>Feedback Notifications</h1>
      <?php if (empty($notifications)): ?>
        <p>No feedback notifications yet.</p>
      <?php else: ?>
        <table>
          <thead>
            <tr>
              <th>Notification ID</th>
              <th>Sender</th>
              <th>Message</th>
              <th>Date</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($notifications as $notif): ?>
              <tr>
                <td><?= htmlspecialchars($notif['notification_id']) ?></td>
                <td><?= htmlspecialchars($notif['sender_name']) ?></td>
                <td><?= nl2br(htmlspecialchars($notif['message'])) ?></td>
                <td><?= htmlspecialchars(date('Y-m-d H:i', strtotime($notif['created_at']))) ?></td>
                <td><?= $notif['is_read'] ? 'Read' : 'Unread' ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </main>
  </div>

  <script>
    function logout() {
      return confirm('Are you sure you want to logout?');
    }
  </script>
</body>
</html>
