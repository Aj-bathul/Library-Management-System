<?php
session_start();

if (!isset($_SESSION['employee_id'])) {
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

    $employee_id = $_SESSION['employee_id'];

    $stmt = $pdo->prepare("SELECT employee_id, full_name, hire_date, email, phone_number, password 
                           FROM librarian WHERE employee_id = ?");
    $stmt->execute([$employee_id]);
    $librarian = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$librarian) die("Librarian not found.");

    // Update Email
    if (isset($_POST['update_email'])) {
        $newEmail = trim($_POST['email']);
        if (!filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            $errorMsg = "Invalid email format.";
        } else {
            $updateStmt = $pdo->prepare("UPDATE librarian SET email = ? WHERE employee_id = ?");
            $updateStmt->execute([$newEmail, $employee_id]);
            $statusMsg = "Email updated successfully.";
            $librarian['email'] = $newEmail;
        }
    }

    // Update Phone
    if (isset($_POST['update_phone'])) {
        $newPhone = trim($_POST['phone']);
        $updateStmt = $pdo->prepare("UPDATE librarian SET phone_number = ? WHERE employee_id = ?");
        $updateStmt->execute([$newPhone, $employee_id]);
        $statusMsg = "Phone number updated successfully.";
        $librarian['phone_number'] = $newPhone;
    }

    // Update Password
    if (isset($_POST['update_password'])) {
        $newPassword = $_POST['password'];
        $confirmPassword = $_POST['confirmPassword'];

        if ($newPassword !== $confirmPassword) {
            $errorMsg = "Passwords do not match.";
        } elseif (strlen($newPassword) < 6) {
            $errorMsg = "Password must be at least 6 characters.";
        } else {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $updateStmt = $pdo->prepare("UPDATE librarian SET password = ? WHERE employee_id = ?");
            $updateStmt->execute([$hashedPassword, $employee_id]);
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
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Librarian Profile</title>
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet"/>
<link rel="stylesheet" href="librarianprofile.css">
</head>
<body>

<?php if ($statusMsg): ?>
<script>alert("<?= addslashes($statusMsg) ?>");</script>
<?php elseif ($errorMsg): ?>
<script>alert("<?= addslashes($errorMsg) ?>");</script>
<?php endif; ?>

<div class="container">
    <aside class="sidebar">
        <div class="sidebar-header"><h2>KnowledgeNest <br>LMS</h2></div>
        <nav class="menu">
            <a href="librarianprofile.php" class="menu-item active"><i class="fas fa-user"></i> <span>My Profile</span></a>
            <a href="managebooks.php" class="menu-item"><i class="fas fa-book"></i> <span>Manage Books</span></a>
            <a href="manageborrowing.php" class="menu-item"><i class="fas fa-book-reader"></i> <span>Manage Borrowing</span></a>
            <a href="managemembers.php" class="menu-item"><i class="fas fa-users"></i> <span>Manage Members</span></a>
            <a href="notifications.php" class="menu-item"><i class="fas fa-bell"></i> <span>Notifications</span></a>
            <a href="announcements.php" class="menu-item"><i class="fas fa-bullhorn"></i> <span>Announcements</span></a>
            <a href="reports.php" class="menu-item"><i class="fas fa-chart-line"></i> <span>View Reports</span></a>
            <a href="logout.php" class="menu-item logout"><i class="fas fa-sign-out-alt"></i> <span>Logout</span></a>
        </nav>
    </aside>

    <main class="main-content">
        <div class="profile-container">
            <h2><i class="fas fa-user-circle"></i> My Profile</h2>
            <div class="readonly-info-group">
                <div class="info-row">
                    <div class="info-label"><i class="fas fa-user"></i> Full Name:</div>
                    <div class="info-value"><?= htmlspecialchars($librarian['full_name']) ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label"><i class="fas fa-id-card"></i> Employee ID:</div>
                    <div class="info-value"><?= htmlspecialchars($librarian['employee_id']) ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label"><i class="fas fa-calendar-alt"></i> Hire Date:</div>
                    <div class="info-value"><?= htmlspecialchars($librarian['hire_date']) ?></div>
                </div>
            </div>

            <div class="editable-info-group">
                <div class="info-row">
                    <div class="info-label"><i class="fas fa-envelope"></i> Email:</div>
                    <form method="post" class="editable-form">
                        <input type="email" name="email" value="<?= htmlspecialchars($librarian['email']) ?>" required>
                        <button type="submit" name="update_email" class="save-btn"><i class="fas fa-sync-alt"></i> Update Email</button>
                    </form>
                </div>
                <div class="info-row">
                    <div class="info-label"><i class="fas fa-phone"></i> Phone Number:</div>
                    <form method="post" class="editable-form">
                        <input type="tel" name="phone" value="<?= htmlspecialchars($librarian['phone_number']) ?>">
                        <button type="submit" name="update_phone" class="save-btn"><i class="fas fa-sync-alt"></i> Update Phone</button>
                    </form>
                </div>
            </div>
            
            <div class="password-section">
                <h3><i class="fas fa-key"></i> Change Password</h3>
                <form method="post" class="password-form">
                    <div class="form-item">
                        <label for="new-password"><i class="fas fa-lock"></i> New Password:</label>
                        <input type="password" id="new-password" name="password" placeholder="Enter new password" required>
                    </div>
                    <div class="form-item">
                        <label for="confirm-password"><i class="fas fa-lock"></i> Confirm Password:</label>
                        <input type="password" id="confirm-password" name="confirmPassword" placeholder="Confirm new password" required>
                    </div>
                    <button type="submit" name="update_password" class="save-btn password-save-btn"><i class="fas fa-sync-alt"></i> Update Password</button>
                </form>
            </div>
        </div>
    </main>
</div>

</body>
</html>