<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$host = 'localhost';
$dbname = 'librarymanagementsystem01';
$username = 'root';
$password = '';

$borrowings = [];
$errorMsg = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $user_id = $_SESSION['user_id'];

    // Fetch borrowed books 
    $stmt = $pdo->prepare("
        SELECT books.title, books.author, borrowings.borrow_date, borrowings.due_date, borrowings.status
        FROM borrowings
        JOIN books ON borrowings.book_id = books.book_id
        WHERE borrowings.user_id = ?
        ORDER BY borrowings.borrow_date DESC
    ");
    $stmt->execute([$user_id]);
    $borrowings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (!$borrowings) {
        $errorMsg = "You have no borrowed books at the moment.";
    }
} catch (PDOException $e) {
    $errorMsg = "Database error: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Borrowed Books</title>
  <link rel="stylesheet" href="borrowedbooks.css" />
  <link
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    rel="stylesheet"
  />
</head>
<body>
  <?php if ($errorMsg): ?>
    <script>
      alert("<?= addslashes($errorMsg) ?>");
    </script>
  <?php endif; ?>

  <div class="container">
    <aside class="sidebar">
      <div class="sidebar-header">
        <h2>KnowledgeNest LMS</h2>
      </div>
      <nav class="menu">
        <a href="userseditprofile.php" class="menu-item ">
          <i class="fas fa-user"></i> Profile
        </a>
        <a href="borrowedbooks.php" class="menu-item active">
          <i class="fas fa-book-reader"></i> Borrowed
        </a>
        <a href="reserve.php" class="menu-item">
          <i class="fas fa-calendar-check"></i> Reservations
        </a>
        <a href="feedback.php" class="menu-item">
          <i class="fas fa-comment-dots"></i> Feedback
        </a>
        <a href="usernotification.php" class="menu-item">
          <i class="fas fa-bell"></i> Notifications
        </a>
        <a href="index.html" class="menu-item logout">
          <i class="fas fa-sign-out-alt"></i> Logout
        </a>
      </nav>
    </aside>

    <div class="table-wrapper">
      <div class="books-table-container">
        <h1>Borrowed Books</h1>
        <table class="borrowed-books-table">
          <thead>
            <tr>
              <th>Book Title</th>
              <th>Author</th>
              <th>Borrow Date</th>
              <th>Due Date</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($borrowings): ?>
              <?php foreach ($borrowings as $row): ?>
                <tr>
                  <td><?= htmlspecialchars($row['title']) ?></td>
                  <td><?= htmlspecialchars($row['author']) ?></td>
                  <td><?= htmlspecialchars($row['borrow_date']) ?></td>
                  <td><?= htmlspecialchars($row['due_date']) ?></td>
                  <td class="<?= strtolower(str_replace(' ', '-', $row['status'])) ?>">
                    <?= htmlspecialchars($row['status']) ?>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr><td colspan="5" style="text-align:center;">No borrowed books found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</body>
</html>
