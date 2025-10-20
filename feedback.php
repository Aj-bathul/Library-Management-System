<?php
session_start();

// DB connection details
$host = "localhost";
$username = "root";
$password = "";
$dbname = "librarymanagementsystem01";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $senderId = $_SESSION['user_id'] ?? null;

    if (!$senderId) {
        die("You must be logged in to send feedback.");
    }

    $message = trim($_POST['message'] ?? '');

    if (empty($message)) {
        $errorMessage = "Please enter a feedback message.";
    } else {
        // Fetch librarian's employee_id (assuming one librarian)
        $stmt = $pdo->prepare("SELECT employee_id FROM librarian LIMIT 1");
        $stmt->execute();
        $librarian = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$librarian) {
            die("No librarian found in the system.");
        }

        $librarianEmployeeId = $librarian['employee_id'];

        // Compose message including sender info
        $fullMessage = "Feedback from user #$senderId: " . $message;

        try {
            // Insert notification
            $insert = $pdo->prepare("
                INSERT INTO notifications (employee_id, user_id, type, message, is_read)
                VALUES (?, ?, 'feedback', ?, 0)
            ");
            $insert->execute([$librarianEmployeeId, $senderId, $fullMessage]);

            // ✅ Redirect to prevent duplicate submissions
            header("Location: feedback.php?success=1");
            exit();
        } catch (PDOException $e) {
            $errorMessage = "Failed to send feedback: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Send Feedback</title>
  <link rel="stylesheet" href="feedback.css" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />
</head>
<body>
  <div class="container">
    <aside class="sidebar">
      <div class="sidebar-header">
        <h2>KnowledgeNest LMS</h2>
      </div>
      <nav class="menu">
        <a href="userseditprofile.php" class="menu-item"><i class="fas fa-user"></i> Profile</a>
        <a href="borrowedbooks.php" class="menu-item"><i class="fas fa-book-reader"></i> Borrowed</a>
        <a href="reserve.php" class="menu-item"><i class="fas fa-calendar-check"></i> Reservations</a>
        <a href="feedback.php" class="menu-item active"><i class="fas fa-comment-dots"></i> Feedback</a>
        <a href="usernotification.php" class="menu-item"><i class="fas fa-bell"></i> Notifications</a>
        <a href="index.html" class="menu-item logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
      </nav>
    </aside>
    <main>
      <h1>Send Feedback</h1>

      <!-- ✅ Success and error messages -->
      <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
        <p style="color: green;">Feedback sent to librarian successfully!</p>
      <?php elseif (!empty($errorMessage)): ?>
        <p style="color: red;"><?= htmlspecialchars($errorMessage) ?></p>
      <?php endif; ?>

      <form class="feedback-form" action="" method="POST">
        <div class="form-group">
          <label for="message">Feedback Message</label>
          <textarea id="message" name="message" rows="6" placeholder="Write your feedback here..." required></textarea>
        </div>
        <button type="submit" class="submit-btn">Send Feedback</button>
      </form>
    </main>
  </div>
</body>
</html>
