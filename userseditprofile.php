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

$statusMsg = '';
$errorMsg = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $user_id = $_SESSION['user_id'];

    // Fetch user data from DB (no staff_id column)
    $stmt = $pdo->prepare("SELECT user_id, first_name, last_name, email, phone, registered_date, password FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        die("User not found.");
    }

    // Handle phone update
    if (isset($_POST['update_phone'])) {
        $newPhone = trim($_POST['phone']);
        // Optional: Add phone validation here
        $updateStmt = $pdo->prepare("UPDATE users SET phone = ? WHERE user_id = ?");
        $updateStmt->execute([$newPhone, $user_id]);
        $statusMsg = "Phone number updated successfully.";
        $user['phone'] = $newPhone; // update local variable
    }

    // Handle email update
    if (isset($_POST['update_email'])) {
        $newEmail = trim($_POST['email']);
        if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            $errorMsg = "Invalid email format.";
        } else {
            $updateStmt = $pdo->prepare("UPDATE users SET email = ? WHERE user_id = ?");
            $updateStmt->execute([$newEmail, $user_id]);
            $statusMsg = "Email updated successfully.";
            $user['email'] = $newEmail; // update local variable
        }
    }

    // Handle password update
    if (isset($_POST['update_password'])) {
        $newPassword = $_POST['password'];
        $confirmPassword = $_POST['confirmPassword'];

        if ($newPassword !== $confirmPassword) {
            $errorMsg = "Passwords do not match.";
        } elseif (strlen($newPassword) < 6) {
            $errorMsg = "Password must be at least 6 characters.";
        } else {
            // Hash the password and update
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $updateStmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            $updateStmt->execute([$hashedPassword, $user_id]);
            $statusMsg = "Password updated successfully.";
        }
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
  <title>Edit Profile</title>
  <link
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    rel="stylesheet"
  />
  <link rel="stylesheet" href="userseditprofile.css" />
</head>
<body>
  <?php if ($statusMsg): ?>
    <script>
      alert("<?= addslashes($statusMsg) ?>");
    </script>
  <?php elseif ($errorMsg): ?>
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
        <a href="userseditprofile.php" class="menu-item active">
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
        <a href="usernotification.php" class="menu-item">
          <i class="fas fa-bell"></i> Notifications
        </a>
        <a href="logout.php" class="menu-item logout">
          <i class="fas fa-sign-out-alt"></i> Logout
        </a>
      </nav>
    </aside>

    <main>
      <div class="readonly-info">
        <div class="readonly-row">
          <div class="readonly-label">User ID:</div>
          <div class="readonly-value" id="userIdText"><?= htmlspecialchars($user['user_id']) ?></div>
        </div>
        <div class="readonly-row">
          <div class="readonly-label">First Name:</div>
          <div class="readonly-value" id="firstNameText"><?= htmlspecialchars($user['first_name']) ?></div>
        </div>
        <div class="readonly-row">
          <div class="readonly-label">Last Name:</div>
          <div class="readonly-value" id="lastNameText"><?= htmlspecialchars($user['last_name']) ?></div>
        </div>
        <div class="readonly-row">
          <div class="readonly-label">Registered Date:</div>
          <div class="readonly-value" id="registeredDateText"><?= htmlspecialchars($user['registered_date']) ?></div>
        </div>
      </div>

      <div class="form-contact">
        <form id="phoneForm" method="post" action="">
          <div class="form-group">
            <label for="phone">Phone Number:</label>
            <input
              type="tel"
              id="phone"
              name="phone"
              placeholder="Enter phone number"
              value="<?= htmlspecialchars($user['phone'] ?? '') ?>"
            />
          </div>
          <button type="submit" name="update_phone" class="save-btn">Save Phone</button>
        </form>

        <form id="emailForm" method="post" action="">
          <div class="form-group">
            <label for="email">Email:</label>
            <input
              type="email"
              id="email"
              name="email"
              placeholder="Enter email address"
              value="<?= htmlspecialchars($user['email']) ?>"
              required
            />
          </div>
          <button type="submit" name="update_email" class="save-btn">Save Email</button>
        </form>
      </div>

      <form id="passwordForm" class="password-section" method="post" action="">
        <h2>Change Password</h2>
        <div class="form-group">
          <label for="password">New Password:</label>
          <input
            type="password"
            id="password"
            name="password"
            placeholder="Enter new password"
            required
          />
        </div>

        <div class="form-group">
          <label for="confirmPassword">Confirm Password:</label>
          <input
            type="password"
            id="confirmPassword"
            name="confirmPassword"
            placeholder="Confirm new password"
            required
          />
        </div>

        <button type="submit" name="update_password" class="save-btn">Save Password</button>
      </form>
    </main>
  </div>
</body>
</html>
