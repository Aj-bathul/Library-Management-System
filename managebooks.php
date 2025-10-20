<?php
session_start();

// Database config
$servername = "localhost";
$dbusername = "root";
$dbpassword = "";
$dbname = "librarymanagementsystem01";

$conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle AJAX requests
if (isset($_GET['action'])) {
    $action = $_GET['action'];

    header('Content-Type: application/json');

    if ($action === 'fetch') {
        // Fetch ONLY available books (status='available' and available_copies > 0)
        $result = $conn->query("SELECT * FROM books WHERE status='available' AND available_copies > 0 ORDER BY book_id ASC");
        $books = [];
        while ($row = $result->fetch_assoc()) {
            $books[] = $row;
        }
        echo json_encode($books);
        exit();
    }

    if ($action === 'delete' && isset($_POST['bookId'])) {
        // Delete book by book_id
        $bookId = $conn->real_escape_string($_POST['bookId']);
        $conn->query("DELETE FROM books WHERE book_id='$bookId'");
        echo json_encode(['success' => true]);
        exit();
    }

    if ($action === 'add_update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);

        $requiredFields = ['bookId', 'title', 'author', 'publisher', 'year', 'totalCopies', 'availableCopies', 'category', 'status'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                echo json_encode(['success' => false, 'message' => "Field $field is required."]);
                exit();
            }
        }

        $bookId = $conn->real_escape_string($data['bookId']);
        $title = $conn->real_escape_string($data['title']);
        $author = $conn->real_escape_string($data['author']);
        $publisher = $conn->real_escape_string($data['publisher']);
        $year = (int)$data['year'];
        $totalCopies = (int)$data['totalCopies'];
        $availableCopies = (int)$data['availableCopies'];
        $category = $conn->real_escape_string($data['category']);
        $status = $conn->real_escape_string($data['status']);

        if ($availableCopies > $totalCopies) {
            echo json_encode(['success' => false, 'message' => "Available copies can't exceed total copies."]);
            exit();
        }

        // Check if book exists
        $checkRes = $conn->query("SELECT * FROM books WHERE book_id='$bookId'");

        if ($checkRes->num_rows > 0) {
            // Update
            $sql = "UPDATE books SET 
                    title='$title',
                    author='$author',
                    publisher='$publisher',
                    publication_year=$year,
                    total_copies=$totalCopies,
                    available_copies=$availableCopies,
                    category='$category',
                    status='$status'
                    WHERE book_id='$bookId'";
        } else {
            // Insert
            $sql = "INSERT INTO books (book_id, title, author, publisher, publication_year, total_copies, available_copies, category, status) 
                    VALUES ('$bookId', '$title', '$author', '$publisher', $year, $totalCopies, $availableCopies, '$category', '$status')";
        }

        if ($conn->query($sql) === TRUE) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => $conn->error]);
        }
        exit();
    }

    if ($action === 'reserve' && isset($_POST['bookId'])) {
        $bookId = $conn->real_escape_string($_POST['bookId']);
        $check = $conn->query("SELECT * FROM books WHERE book_id='$bookId'");

        if ($check->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Book not found.']);
            exit();
        }

        $book = $check->fetch_assoc();

        if ($book['status'] !== 'available' || $book['available_copies'] <= 0) {
            echo json_encode(['success' => false, 'message' => 'Book not available for reservation.']);
            exit();
        }

        // Decrease available copies
        $conn->query("UPDATE books SET available_copies = available_copies - 1 WHERE book_id='$bookId'");
        echo json_encode(['success' => true, 'message' => 'Book reserved successfully.']);
        exit();
    }

    // Invalid action
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Manage Books</title>
  <link rel="stylesheet" href="managebooks.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

</head>
<body>
  <div class="container">
    <aside class="sidebar">
      <div class="sidebar-header">
        <h2>KnowledgeNest LMS</h2>
      </div>
      <nav class="menu">
        <a href="librarianprofile.php" class="menu-item">
          <i class="fas fa-user-circle"></i> My profile
        </a>
        <a href="managebooks.php" class="menu-item active">
          <i class="fas fa-book"></i> Manage Books
        </a>
        <a href="manageborrowings.php" class="menu-item">
          <i class="fas fa-book-reader"></i> Manage Borrowing
        </a>
        <a href="managemembers.php" class="menu-item">
          <i class="fas fa-users"></i> Manage Members
        </a>
        <a href="notifications.php" class="menu-item">
          <i class="fas fa-bell"></i> Notifications
        </a>
        <a href="announcements.php" class="menu-item">
          <i class="fas fa-bullhorn"></i> Announcements
        </a>
        <a href="reports.php" class="menu-item">
          <i class="fas fa-chart-bar"></i> View Reports
        </a>
        <a href="index.html" onclick="logout()" class="menu-item logout">
          <i class="fas fa-sign-out-alt"></i> Logout
        </a>
      </nav>
    </aside>

    <div class="main-content">
      <header class="top-header">
        <h1 style="text-align:center;">Manage Books</h1>
      </header>

      <form id="bookForm">
        <input type="hidden" id="bookIndex" />
        <div class="form-column">
          <label for="bookId">Book ID:</label>
          <input type="text" id="bookId" required />
          <label for="title">Title:</label>
          <input type="text" id="title" required />
          <label for="author">Author:</label>
          <input type="text" id="author" required />
          <label for="publisher">Publisher:</label>
          <input type="text" id="publisher" required />
        </div>
        <div class="form-column">
          <label for="year">Publication Year:</label>
          <input type="number" id="year" min="1500" max="2099" required />
          <label for="totalCopies">Total Copies:</label>
          <input type="number" id="totalCopies" min="0" required />
          <label for="availableCopies">Available Copies:</label>
          <input type="number" id="availableCopies" min="0" required />
          <label for="category">Category:</label>
          <select id="category" required>
            <option value="" disabled selected>Select category</option>
            <option value="Fiction">Fiction</option>
            <option value="Dystopian">Dystopian</option>
            <option value="Classic">Classic</option>
            <option value="Biography">Biography</option>
            <option value="Science">Science</option>
            <option value="History">History</option>
            <option value="Technology">Technology</option>
            <option value="Science Fiction">Science Fiction</option>
          </select>
          <label for="status">Status:</label>
          <select id="status" required>
            <option value="available">Available</option>
            <option value="borrowed">Borrowed</option>
          </select>
        </div>
        <div class="button-wrapper">
          <button type="submit" id="submitBtn">Add / Update</button>
        </div>
      </form>

      <table id="booksTable">
        <thead>
          <tr>
            <th>Book ID</th><th>Title</th><th>Author</th><th>Publisher</th><th>Year</th>
            <th>Total Copies</th><th>Available Copies</th><th>Category</th><th>Status</th><th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <!-- Rows inserted by JS -->
        </tbody>
      </table>
    </div>
  </div>

  <script>
    const bookForm = document.getElementById('bookForm');
    const booksTableBody = document.querySelector('#booksTable tbody');
    const clearBtn = document.getElementById('clearBtn');

    let books = [];

    // Fetch books from server and render table
    async function fetchBooks() {
      const res = await fetch('managebooks.php?action=fetch');
      books = await res.json();
      renderTable();
    }

    function renderTable() {
      booksTableBody.innerHTML = '';
      books.forEach((book, index) => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
          <td>${book.book_id}</td>
          <td>${book.title}</td>
          <td>${book.author}</td>
          <td>${book.publisher}</td>
          <td>${book.publication_year}</td>
          <td>${book.total_copies}</td>
          <td>${book.available_copies}</td>
          <td>${book.category}</td>
          <td>${book.status}</td>
          <td>
            <button onclick="editBook(${index})" title="Edit"><i class="fas fa-edit"></i></button>
            <button onclick="deleteBook('${book.book_id}')" title="Delete"><i class="fas fa-trash-alt"></i></button>
          </td>
        `;
        booksTableBody.appendChild(tr);
      });
    }

    function clearForm() {
      bookForm.reset();
      document.getElementById('bookId').disabled = false;
      document.getElementById('bookIndex').value = '';
    }

    // Edit book data
    function editBook(index) {
      const book = books[index];
      document.getElementById('bookId').value = book.book_id;
      document.getElementById('title').value = book.title;
      document.getElementById('author').value = book.author;
      document.getElementById('publisher').value = book.publisher;
      document.getElementById('year').value = book.publication_year;
      document.getElementById('totalCopies').value = book.total_copies;
      document.getElementById('availableCopies').value = book.available_copies;
      document.getElementById('category').value = book.category;
      document.getElementById('status').value = book.status;
      document.getElementById('bookId').disabled = true; // Cannot edit primary key
      document.getElementById('bookIndex').value = index;
    }

    async function deleteBook(bookId) {
      if (!confirm("Are you sure you want to delete this book?")) return;

      const res = await fetch('managebooks.php?action=delete', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `bookId=${encodeURIComponent(bookId)}`
      });
      const data = await res.json();
      if (data.success) {
        alert("Book deleted successfully.");
        fetchBooks();
        clearForm();
      } else {
        alert("Failed to delete book.");
      }
    }

    bookForm.addEventListener('submit', async (e) => {
      e.preventDefault();

      const bookData = {
        bookId: document.getElementById('bookId').value.trim(),
        title: document.getElementById('title').value.trim(),
        author: document.getElementById('author').value.trim(),
        publisher: document.getElementById('publisher').value.trim(),
        year: document.getElementById('year').value,
        totalCopies: document.getElementById('totalCopies').value,
        availableCopies: document.getElementById('availableCopies').value,
        category: document.getElementById('category').value,
        status: document.getElementById('status').value,
      };

      if (parseInt(bookData.availableCopies) > parseInt(bookData.totalCopies)) {
        alert("Available copies can't exceed total copies.");
        return;
      }

      const res = await fetch('managebooks.php?action=add_update', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify(bookData),
      });
      const data = await res.json();

      if (data.success) {
        alert("Book saved successfully.");
        fetchBooks();
        clearForm();
      } else {
        alert("Error: " + data.message);
      }
    });

    clearBtn.addEventListener('click', () => {
      clearForm();
    });

    // Initialize
    fetchBooks();
  </script>
</body>
</html>
