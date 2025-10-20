<?php
// DB connection settings
$host = "localhost";
$username = "root";
$password = "";
$dbname = "librarymanagementsystem01";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $title = trim($_POST['announcement-title'] ?? '');
        $date = $_POST['announcement-date'] ?? '';
        $content = trim($_POST['announcement-content'] ?? '');

        if ($title && $date && $content) {
            $stmt = $pdo->prepare("INSERT INTO announcements (title, date, content) VALUES (?, ?, ?)");
            $stmt->execute([$title, $date, $content]);

            // ✅ Redirect with success flag
            header("Location: announcements.php?success=1"); 
            exit();
        } else {
            $error = "Please fill in all fields.";
        }
    }

    // Fetch all announcements ordered by date descending
    $stmt = $pdo->query("SELECT * FROM announcements ORDER BY date DESC");
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Librarian - Manage Announcements</title>
  <link rel="stylesheet" href="announcements.css" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />
</head>
<body>

  <aside class="sidebar">
    <div class="sidebar-header">
      <h2>KnowledgeNest LMS</h2>
    </div>
    <nav class="menu">
      <a href="librarianprofile.php" class="menu-item"><i class="fas fa-user-circle"></i> My Profile</a>
      <a href="managebooks.php" class="menu-item"><i class="fas fa-book"></i> Manage Books</a>
      <a href="manageborrowings.php" class="menu-item"><i class="fas fa-book-reader"></i> Manage Borrowing</a>
      <a href="managemembers.php" class="menu-item"><i class="fas fa-users"></i> Manage Members</a>
      <a href="notifications.php" class="menu-item"><i class="fas fa-bell"></i> Notifications</a>
      <a href="announcements.php" class="menu-item active"><i class="fas fa-bullhorn"></i> Announcements</a>
      <a href="reports.php" class="menu-item"><i class="fas fa-chart-bar"></i> View Reports</a>
      <a href="index.html" onclick="return confirm('Are you sure you want to logout?');" class="menu-item logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </nav>
  </aside>

  <div class="container">
    <h2 class="section-title">Announcements</h2>

    <?php if (!empty($error)): ?>
      <div class="error-message"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form class="announcement-form" method="POST" action="announcements.php">
      <label for="announcement-title">Title:</label>
      <input type="text" id="announcement-title" name="announcement-title" placeholder="Enter announcement title" required />

      <label for="announcement-date">Date:</label>
      <input type="date" id="announcement-date" name="announcement-date" required />

      <label for="announcement-content">Content:</label>
      <textarea id="announcement-content" name="announcement-content" placeholder="Enter announcement content" rows="5" required></textarea>

      <button type="submit" class="btn-primary">Post Announcement</button>
    </form>

    <table class="announcements-table" aria-label="List of announcements">
      <thead>
        <tr>
          <th>Title</th>
          <th>Date</th>
          <th>Content</th>
        </tr>
      </thead><br><br>
      <tbody id="announcement-list">
        <?php if (empty($announcements)): ?>
          <tr><td colspan="3">No announcements posted yet.</td></tr>
        <?php else: ?>
          <?php foreach ($announcements as $a): ?>
            <tr>
              <td><?= htmlspecialchars($a['title']) ?></td>
              <td><?= htmlspecialchars($a['date']) ?></td>
              <td><?= nl2br(htmlspecialchars($a['content'])) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
      </tbody>
    </table>
  </div>

  <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
    <script>
      alert("Announcement posted successfully!");
    </script>
  <?php endif; ?>

</body>
</html>
