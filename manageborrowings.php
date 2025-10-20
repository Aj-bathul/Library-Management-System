<?php
// DB connection
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'librarymanagementsystem01';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error = '';

// Handle form submission (add or update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['borrowingId'] ?? '';
    $userId = $_POST['userId'];
    $bookId = $_POST['bookId'];
    $borrowDate = $_POST['borrowDate'];
    $dueDate = $_POST['dueDate'];
    $returnDate = $_POST['returnDate'] ?: null; // nullable
    $status = $_POST['status'];

    // Validate user exists
    $stmtUser = $conn->prepare("SELECT COUNT(*) FROM users WHERE user_id = ?");
    $stmtUser->bind_param("s", $userId);
    $stmtUser->execute();
    $stmtUser->bind_result($userCount);
    $stmtUser->fetch();
    $stmtUser->close();

    // Validate book exists
    $stmtBook = $conn->prepare("SELECT COUNT(*) FROM books WHERE book_id = ?");
    $stmtBook->bind_param("s", $bookId);
    $stmtBook->execute();
    $stmtBook->bind_result($bookCount);
    $stmtBook->fetch();
    $stmtBook->close();

    if ($userCount == 0 || $bookCount == 0) {
        $errorParts = [];
        if ($userCount == 0) {
            $errorParts[] = "User ID does not exist in the system.";
        }
        if ($bookCount == 0) {
            $errorParts[] = "Book ID does not exist in the system.";
        }
        $error = implode(" ", $errorParts);
    } else {
        if ($id) {
            // Update
            $stmt = $conn->prepare("UPDATE borrowings SET user_id=?, book_id=?, borrow_date=?, due_date=?, return_date=?, status=? WHERE id=?");
            $stmt->bind_param("ssssssi", $userId, $bookId, $borrowDate, $dueDate, $returnDate, $status, $id);
            $stmt->execute();
            $stmt->close();
        } else {
            // Insert
            $stmt = $conn->prepare("INSERT INTO borrowings (user_id, book_id, borrow_date, due_date, return_date, status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssss", $userId, $bookId, $borrowDate, $dueDate, $returnDate, $status);
            $stmt->execute();
            $stmt->close();
        }

        header("Location: manageborrowings.php"); // reload page after POST
        exit;
    }
}

// Handle delete request
if (isset($_GET['delete_id'])) {
    $del_id = (int) $_GET['delete_id'];
    $conn->query("DELETE FROM borrowings WHERE id=$del_id");
    header("Location: manageborrowings.php");
    exit;
}

// Fetch borrowings
$sql = "SELECT 
  b.id, 
  u.user_id, 
  CONCAT(u.first_name, ' ', u.last_name) AS user_name, 
  bk.book_id, 
  bk.title AS book_title, 
  b.borrow_date, 
  b.due_date, 
  b.return_date, 
  b.status
FROM borrowings b
JOIN users u ON b.user_id = u.user_id
JOIN books bk ON b.book_id = bk.book_id
ORDER BY b.id DESC";

$result = $conn->query($sql);

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Manage Borrowings</title>
  <link rel="stylesheet" href="manageborrowings.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
</head>
<body>
<div class="container">
  <aside class="sidebar">
    <div class="sidebar-header">
      <h2>KnowledgeNest LMS</h2>
    </div>
    <nav class="menu">
      <a href="librarianprofile.php" class="menu-item"><i class="fas fa-user-circle"></i> My profile</a>
      <a href="managebooks.php" class="menu-item"><i class="fas fa-book"></i> Manage Books</a>
      <a href="manageborrowings.php" class="menu-item active"><i class="fas fa-book-reader"></i> Manage Borrowing</a>
      <a href="managemembers.php" class="menu-item"><i class="fas fa-users"></i> Manage Members</a>
      <a href="notifications.php" class="menu-item"><i class="fas fa-bell"></i> Notifications</a>
      <a href="announcements.php" class="menu-item"><i class="fas fa-bullhorn"></i> Announcements</a>
      <a href="reports.php" class="menu-item"><i class="fas fa-chart-bar"></i> View Reports</a>
      <a href="index.html" onclick="logout()" class="menu-item logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </nav>
  </aside>

  <div class="page-wrapper">
    <h1>Manage Borrowings</h1>

    <form id="borrowingForm" method="POST" action="manageborrowings.php" onsubmit="return validateForm()">
      <input type="hidden" name="borrowingId" id="borrowingId" />

      <label for="userId">User ID:</label>
      <input type="text" name="userId" id="userId" required />

      <label for="bookId">Book ID:</label>
      <input type="text" name="bookId" id="bookId" required />

      <label for="borrowDate">Borrow Date:</label>
      <input type="date" name="borrowDate" id="borrowDate" required />

      <label for="dueDate">Due Date:</label>
      <input type="date" name="dueDate" id="dueDate" required />

      <label for="returnDate">Return Date:</label>
      <input type="date" name="returnDate" id="returnDate" />

      <label for="status">Status:</label>
      <select name="status" id="status" required>
        <option value="borrowed">Borrowed</option>
        <option value="returned">Returned</option>
        <option value="damaged">Returned but Damaged</option>
        <option value="overdue">Overdue</option>
      </select>

      <button type="submit">Save Borrowing</button>
    </form>

    <table id="borrowingsTable">
      <thead>
        <tr>
          <th>ID</th>
          <th>User ID</th>
          <th>User Name</th>
          <th>Book ID</th>
          <th>Book Title</th>
          <th>Borrow Date</th>
          <th>Due Date</th>
          <th>Return Date</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
      <?php while($row = $result->fetch_assoc()): ?>
        <tr>
          <td><?= htmlspecialchars($row['id']) ?></td>
          <td><?= htmlspecialchars($row['user_id']) ?></td>
          <td><?= htmlspecialchars($row['user_name']) ?></td>
          <td><?= htmlspecialchars($row['book_id']) ?></td>
          <td><?= htmlspecialchars($row['book_title']) ?></td>
          <td><?= htmlspecialchars($row['borrow_date']) ?></td>
          <td><?= htmlspecialchars($row['due_date']) ?></td>
          <td><?= htmlspecialchars($row['return_date'] ?: '-') ?></td>
          <td><?= htmlspecialchars($row['status']) ?></td>
          <td>
            <button type="button" onclick='editBorrowing(<?= json_encode($row) ?>)'>Edit</button>
            <button type="button" onclick='deleteBorrowing(<?= $row['id'] ?>)'>Delete</button>
          </td>
        </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
  </div>
</div>

<script>
  // Show error alert if PHP error exists
  <?php if ($error): ?>
    alert(<?= json_encode($error) ?>);
  <?php endif; ?>

  function editBorrowing(borrow) {
    document.getElementById('borrowingId').value = borrow.id;
    document.getElementById('userId').value = borrow.user_id;
    document.getElementById('bookId').value = borrow.book_id;
    document.getElementById('borrowDate').value = borrow.borrow_date;
    document.getElementById('dueDate').value = borrow.due_date;
    document.getElementById('returnDate').value = borrow.return_date || '';
    document.getElementById('status').value = borrow.status;
  }

  function clearForm() {
    document.getElementById('borrowingForm').reset();
    document.getElementById('borrowingId').value = '';
  }

  function deleteBorrowing(id) {
    if(confirm("Are you sure you want to delete this borrowing record?")) {
      window.location.href = 'manageborrowings.php?delete_id=' + id;
    }
  }

  // Optional basic client-side validation for date logic
  function validateForm() {
    const borrowDate = document.getElementById('borrowDate').value;
    const dueDate = document.getElementById('dueDate').value;

    if (borrowDate && dueDate && borrowDate > dueDate) {
      alert("Due Date must be after Borrow Date.");
      return false;
    }
    return true;
  }

  function logout() {
    // Implement logout logic if needed
    alert("Logging out...");
  }
</script>
</body>
</html>

<?php
$conn->close();
?>
