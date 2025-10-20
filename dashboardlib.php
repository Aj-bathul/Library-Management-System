<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "librarymanagementsystem01";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}

// Fetch Book Status
$book_status_sql = "SELECT status, COUNT(*) as count FROM books GROUP BY status";
$book_status_result = $conn->query($book_status_sql);

// Fetch Borrowing Activity
$borrowing_sql = "SELECT b.title, br.user_id, br.borrow_date, br.return_date, br.status 
                  FROM borrowings br 
                  JOIN books b ON br.book_id = b.book_id
                  ORDER BY br.borrow_date DESC";
$borrowing_result = $conn->query($borrowing_sql);

// Fetch Registered Users Count
$users_count_sql = "SELECT COUNT(*) AS total_users FROM users";
$users_count_result = $conn->query($users_count_sql);
$total_users = ($users_count_result && $users_count_result->num_rows > 0) 
    ? $users_count_result->fetch_assoc()['total_users'] 
    : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Librarian Dashboard - Library Management System</title>
  <link rel="stylesheet" href="dasboardlib.css" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />
</head>
<body>
<div class="container">
  <!-- Sidebar -->
  <aside class="sidebar">
    <div class="sidebar-header">
      <h2>KNOWLEDGENEST<br>LMS</h2>
    </div>

    <nav class="menu">
      <a href="librarianprofile.php" class="menu-item"><i class="fas fa-user-circle"></i> My Profile</a>
      <a href="managebooks.php" class="menu-item"><i class="fas fa-book"></i> Manage Books</a>
      <a href="manageborrowings.php" class="menu-item"><i class="fas fa-book-reader"></i> Manage Borrowing</a>
      <a href="managemembers.php" class="menu-item"><i class="fas fa-users"></i> Manage Members</a>
      <a href="notifications.php" class="menu-item"><i class="fas fa-bell"></i> Notifications</a>
      <a href="announcements.php" class="menu-item"><i class="fas fa-bullhorn"></i> Announcements</a>
      <a href="reports.php" class="menu-item"><i class="fas fa-chart-bar"></i> View Reports</a>
      <a href="index.html" onclick="return confirm('Are you sure you want to logout?')" class="menu-item logout">
        <i class="fas fa-sign-out-alt"></i> Logout
      </a>
    </nav>
  </aside>

  <!-- Main Content -->
  <div class="main-content">
    <header class="top-header">
      <h1>Welcome Librarian !!</h1>
    </header>

    <!-- Registered Users -->
    <section>
      <h2>Registered Users</h2>
      <div class="stat-box">
        Total Registered Users: <strong><?php echo $total_users; ?></strong>
      </div>
    </section>

    <!-- Books Status Table -->
    <section>
      <h2>Books Status</h2>
      <table>
        <thead>
          <tr>
            <th>Status</th>
            <th>Number of Books</th>
          </tr>
        </thead>
        <tbody>
          <?php while($row = $book_status_result->fetch_assoc()): ?>
            <tr>
              <td>
                <?php 
                  $status = trim($row['status']); 
                  echo $status !== '' ? htmlspecialchars($status) : 'Unknown';
                ?>
              </td>
              <td><?php echo $row['count']; ?></td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </section>

    <!-- Borrowing Activity Table -->
    <section>
      <h2>Borrowing Activity</h2>
      <table>
        <thead>
          <tr>
            <th>Book Title</th>
            <th>User ID</th>
            <th>Borrow Date</th>
            <th>Return Date</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <?php while($row = $borrowing_result->fetch_assoc()): ?>
            <tr>
              <td><?php echo htmlspecialchars($row['title']); ?></td>
              <td><?php echo $row['user_id']; ?></td>
              <td><?php echo $row['borrow_date']; ?></td>
              <td><?php echo $row['return_date'] ?: 'Not Returned'; ?></td>
              <td><?php echo htmlspecialchars($row['status']); ?></td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </section>
  </div>
</div>
</body>
</html>
<?php $conn->close(); ?>
