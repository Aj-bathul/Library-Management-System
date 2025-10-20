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

$searchTitle = $_GET['title'] ?? '';
$searchAuthor = $_GET['author'] ?? '';
$searchCategory = $_GET['category'] ?? '';

$user_id = $_SESSION['user_id'];
$message = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Handle reservation submission (POST)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reserve_book_id'])) {
        $bookIdToReserve = trim($_POST['reserve_book_id']); // Keep as string

        // Check if book exists and has available copies
        $stmtCheck = $pdo->prepare("SELECT available_copies FROM books WHERE book_id = ?");
        $stmtCheck->execute([$bookIdToReserve]);
        $book = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        if (!$book) {
            $message = "Book not found.";
        } elseif ((int)$book['available_copies'] <= 0) {
            $message = "Sorry, this book is not available for reservation.";
        } else {
            // Check if user already has a pending reservation for this book
            $stmtExists = $pdo->prepare("SELECT * FROM reservations WHERE user_id = ? AND book_id = ? AND status = 'Pending'");
            $stmtExists->execute([$user_id, $bookIdToReserve]);

            if ($stmtExists->fetch()) {
                $message = "You have already reserved this book and it's pending.";
            } else {
                // Insert reservation
                $stmtInsert = $pdo->prepare("INSERT INTO reservations (user_id, book_id, reservation_date, status) VALUES (?, ?, NOW(), 'Pending')");
                $stmtInsert->execute([$user_id, $bookIdToReserve]);

                // Optionally, decrease available copies
                $stmtUpdate = $pdo->prepare("UPDATE books SET available_copies = available_copies - 1 WHERE book_id = ?");
                $stmtUpdate->execute([$bookIdToReserve]);

                $message = "Book reserved successfully!";
            }
        }
    }

    // Build search query
    $query = "SELECT * FROM books WHERE 1=1";
    $params = [];

    if ($searchTitle !== '') {
        $query .= " AND title LIKE ?";
        $params[] = "%$searchTitle%";
    }
    if ($searchAuthor !== '') {
        $query .= " AND author LIKE ?";
        $params[] = "%$searchAuthor%";
    }
    if ($searchCategory !== '') {
        $query .= " AND category = ?";
        $params[] = $searchCategory;
    }

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Search & Reserve Books</title>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet" />
  <link rel="stylesheet" href="reserve.css" />
</head>
<body>
  <?php if ($message): ?>
    <script>
      alert("<?= addslashes($message) ?>");
    </script>
  <?php endif; ?>

  <div class="container">
    <aside class="sidebar">
      <div class="sidebar-header">
        <h2>KnowledgeNest LMS</h2>
      </div>
      <nav class="menu">
        <a href="userseditprofile.php" class="menu-item"><i class="fas fa-user"></i> Profile</a>
        <a href="borrowedbooks.php" class="menu-item"><i class="fas fa-book-reader"></i> Borrowed</a>
        <a href="reserve.php" class="menu-item active"><i class="fas fa-calendar-check"></i> Reservations</a>
        <a href="feedback.php" class="menu-item"><i class="fas fa-comment-dots"></i> Feedback</a>
        <a href="usernotification.php" class="menu-item"><i class="fas fa-bell"></i> Notifications</a>
        <a href="index.html" class="menu-item logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
      </nav>
    </aside>

    <main>
      <h1>Search Books</h1><br>

      <form class="search-form" action="reserve.php" method="GET">
        <div class="form-group">
          <label for="title">Book Title</label>
          <input type="text" id="title" name="title" placeholder="Enter book title" value="<?= htmlspecialchars($searchTitle) ?>" />
        </div>
        <div class="form-group">
          <label for="author">Author</label>
          <input type="text" id="author" name="author" placeholder="Enter author name" value="<?= htmlspecialchars($searchAuthor) ?>" />
        </div>
        <div class="form-group">
          <label for="category">Category</label>
          <select id="category" name="category">
            <option value="">-- Select category --</option>
            <?php
            $categories = ['Fiction','Non-fiction','Science','Technology','History','Biography','Others'];
            foreach ($categories as $cat) {
                $selected = ($cat === $searchCategory) ? 'selected' : '';
                echo "<option value=\"$cat\" $selected>$cat</option>";
            }
            ?>
          </select>
        </div>
        <button type="submit" class="search-btn">Search</button>
      </form>

      <br>

      <section class="results-section">
        <h2>Search Results</h2>
        <div class="books-table-container">
          <table class="borrowed-books-table" id="resultsTable">
            <thead>
              <tr>
                <th>Title</th>
                <th>Author</th>
                <th>Category</th>
                <th>Availability</th>
                <th>Reserve</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!empty($books)): ?>
                <?php foreach ($books as $book): 
                  $availableCopies = (int)($book['available_copies'] ?? 0);
                  $isAvailable = $availableCopies > 0;
                  $statusText = $isAvailable ? 'Available' : 'Not Available';
                  $statusClass = $isAvailable ? 'status-on-time' : 'status-overdue';
                ?>
                  <tr>
                    <td><?= htmlspecialchars($book['title']) ?></td>
                    <td><?= htmlspecialchars($book['author']) ?></td>
                    <td><?= htmlspecialchars($book['category']) ?></td>
                    <td><span class="status-badge <?= $statusClass ?>"><?= $statusText ?></span></td>
                    <td>
                      <?php if ($isAvailable): ?>
                        <form method="POST" style="margin:0;">
                          <input type="hidden" name="reserve_book_id" value="<?= htmlspecialchars($book['book_id']) ?>">
                          <button type="submit" class="reserve-btn">Reserve</button>
                        </form>
                      <?php else: ?>
                        <button class="reserve-btn" disabled>Reserve</button>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr><td colspan="5" style="text-align:center;">No books found matching your criteria.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </section>
    </main>
  </div>
</body>
</html>
