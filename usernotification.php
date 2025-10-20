<?php
// DB connection settings
$host = "localhost";
$username = "root";
$password = "";
$dbname = "librarymanagementsystem01";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

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
  <title>Notifications - KnowledgeNest LMS</title>
  <link
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    rel="stylesheet"
  />
  <link rel="stylesheet" href="usernotification.css" />
 
</head>
<body>
  <div class="container">
    <aside class="sidebar">
      <div class="sidebar-header">
        <h2>KnowledgeNest LMS</h2>
      </div>
      <nav class="menu">
        <a href="userseditprofile.php" class="menu-item">
          <i class="fas fa-user"></i> Profile
        </a>
        <a href="borrowedbooks.php" class="menu-item">
          <i class="fas fa-book-reader"></i> Borrowed
        </a>
        <a href="reserve.php" class="menu-item">
          <i class="fas fa-calendar-check"></i> Reservations
        </a>
        <a href="feedback.php" class="menu-item">
          <i class="fas fa-comment-dots"></i> Feedback
        </a>
        <a href="usernotification.php" class="menu-item active">
          <i class="fas fa-bell"></i> Notifications
        </a>
        <a href="index.html" onclick="logout()" class="menu-item logout">
          <i class="fas fa-sign-out-alt"></i> Logout
        </a>
      </nav>
    </aside>

    <main>
      <h1>Notifications</h1>

      <div class="notifications-list">

        <?php if (empty($announcements)): ?>
          <p>No announcements at the moment.</p>
        <?php else: ?>
          <?php foreach ($announcements as $a): ?>
            <div class="notification-item info">
              <div class="notification-icon">
                <i class="fas fa-bullhorn"></i>
              </div>
              <div class="notification-content">
                <p><strong><?= htmlspecialchars($a['title']) ?>:</strong> <?= nl2br(htmlspecialchars($a['content'])) ?></p>
                <span class="notification-date"><?= htmlspecialchars($a['date']) ?></span>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>

        <!-- You can keep other types of notifications below if needed -->
        <!-- Example hardcoded notification -->
       
         

       
      </div>
    </main>
  </div>
</body>
</html>
