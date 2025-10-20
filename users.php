<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// DB connection
$host = 'localhost';
$dbname = 'librarymanagementsystem01';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Fetch logged-in user's profile info
    $stmt = $pdo->prepare("SELECT user_id, first_name, last_name, email, registered_date FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo "User not found.";
        exit;
    }

    // Fetch borrowed books for this user 
    $stmt = $pdo->prepare("
        SELECT b.title, br.borrow_date, br.due_date
        FROM borrowings br
        JOIN books b ON br.book_id = b.book_id
        WHERE br.user_id = ?
        ORDER BY br.due_date ASC
    ");
    $stmt->execute([$user_id]);
    $borrowedBooks = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>User Dashboard - Library Management System</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="users.css" />
  <script>
    function confirmLogout() {
      return confirm('Are you sure you want to logout?');
    }
  </script>
</head>
<body>
  <div class="container">
    <!-- Sidebar -->
    <aside class="sidebar">
      <div class="sidebar-header"><h2>KnowledgeNest LMS</h2></div>
      <nav class="menu">
        <a href="userseditprofile.php" class="menu-item"><i class="fas fa-user"></i> Profile</a>
        <a href="borrowedbooks.php" class="menu-item"><i class="fas fa-book-reader"></i> Borrowed</a>
        <a href="reserve.php" class="menu-item"><i class="fas fa-calendar-check"></i> Reservations</a>
        <a href="feedback.php" class="menu-item"><i class="fas fa-comment-dots"></i> Feedback</a>
        <a href="usernotification.php" class="menu-item"><i class="fas fa-bell"></i> Notifications</a>
        <a href="index.html" onclick="return confirmLogout()" class="menu-item logout">
          <i class="fas fa-sign-out-alt"></i> Logout
        </a>
      </nav>
    </aside>

    <!-- Main content -->
    <div class="main-content">
      <header class="top-header"><h1>Hi <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>!!</h1></header>
      <main class="dashboard-container">
        <!-- Account Details -->
        <section id="account" class="dashboard-card">
          <h2>Account Details</h2>
          <p><strong>Name:</strong> <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></p>
          <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
          <p><strong>Member Since:</strong> <?= htmlspecialchars($user['registered_date']) ?></p><br>
          <a href="userseditprofile.php" class="btn">Edit Profile</a>
        </section>

        <!-- Borrowed Books -->
        <section id="borrowed" class="dashboard-card">
          <h2>My Borrowed Books</h2>
          <?php if (count($borrowedBooks) > 0): ?>
          <table class="data-table">
            <thead>
              <tr><th>Title</th><th>Borrowed Date</th><th>Due Date</th></tr>
            </thead>
            <tbody>
              <?php foreach ($borrowedBooks as $book): ?>
              <tr>
                <td><?= htmlspecialchars($book['title']) ?></td>
                <td><?= htmlspecialchars($book['borrow_date']) ?></td>
                <td><?= htmlspecialchars($book['due_date']) ?></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
          <?php else: ?>
            <p>You have no borrowed books currently.</p>
          <?php endif; ?>
        </section>
      </main>
    </div>
  </div>
</body>
</html>
