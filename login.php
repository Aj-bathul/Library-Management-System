<?php
session_start();

// Database configuration
$servername = "localhost";
$dbusername = "root";
$dbpassword = "";
$dbname = "librarymanagementsystem01";

// Create connection
$conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$error_message = "";

// Process login form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $userid = trim($_POST['userid']);
    $password = trim($_POST['password']);

    if (!empty($userid) && !empty($password)) {
        // Check user type by prefix
        if (stripos($userid, 'lib') === 0) {
            // Case-sensitive employee_id
            $query = "SELECT employee_id, full_name, email, phone_number, password 
                      FROM librarian 
                      WHERE BINARY employee_id = ?";
            $user_type = "librarian";
            $redirect_page = "dashboardlib.php";
        } elseif (stripos($userid, 'stud') === 0 || stripos($userid, 'staff') === 0) {
            // Case-sensitive user_id
            $query = "SELECT user_id, first_name, last_name, email, phone, password 
                      FROM users 
                      WHERE BINARY user_id = ?";
            $user_type = stripos($userid, 'stud') === 0 ? "student" : "staff";
            $redirect_page = "users.php";
        } else {
            $error_message = "Invalid User ID format.";
        }

        if (empty($error_message)) {
            $stmt = $conn->prepare($query);
            if (!$stmt) {
                die("Prepare failed: " . $conn->error);
            }

            $stmt->bind_param("s", $userid);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                $user = $result->fetch_assoc();

                // Case-sensitive password check too
                if ($password === $user['password']) {
                    // Successful login
                    if ($user_type === "librarian") {
                        $_SESSION['employee_id'] = $user['employee_id'];
                        $_SESSION['role'] = 'librarian';
                        $_SESSION['user_data'] = [
                            'employee_id' => $user['employee_id'],
                            'full_name' => $user['full_name'],
                            'email' => $user['email'],
                            'phone' => $user['phone_number']
                        ];
                    } else {
                        $_SESSION['user_id'] = $user['user_id'];
                        $_SESSION['role'] = $user_type;
                        $_SESSION['user_data'] = [
                            'user_id' => $user['user_id'],
                            'first_name' => $user['first_name'],
                            'last_name' => $user['last_name'],
                            'email' => $user['email'],
                            'phone' => $user['phone']
                        ];
                    }

                    header("Location: " . $redirect_page);
                    exit();
                } else {
                    $error_message = "Invalid password (case-sensitive).";
                }
            } else {
                $error_message = "User ID not found (case-sensitive).";
            }
            $stmt->close();
        }
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Login - KnowledgeNest Library</title>
  <link rel="stylesheet" href="login.css" />
</head>
<body>
  <div class="main-container">
    <div class="left-container">
      <div class="library-image">
        <img src="loginimage1.gif" alt="Library Illustration" />
      </div>
    </div>

    <div class="right-container">
      <div class="login-container">
        <h1>KnowledgeNest</h1>
        <h2>Library Management System</h2>
        <p class="subtitle">Please login to access your account</p>

        <form id="loginForm" class="login-form" method="POST" action="login.php">
          <div class="form-group">
            <label for="userid">User ID</label>
            <input type="text" id="userid" name="userid" placeholder="Enter your user id" />
          </div>

          <div class="form-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="Enter your password" />
          </div>

          <button type="submit" class="btn">Login</button>
        </form>

        <p class="terms">By continuing, you agree to our <a href="#">Terms & Conditions</a></p>
      </div>
    </div>
  </div>

  <!-- Client-side alerts for empty fields -->
  <script>
    document.getElementById("loginForm").addEventListener("submit", function(e) {
      let userId = document.getElementById("userid").value.trim();
      let password = document.getElementById("password").value.trim();

      if (userId === "" && password === "") {
        alert("Please fill in both User ID and Password.");
        e.preventDefault();
      } else if (userId === "") {
        alert("Please enter your User ID.");
        e.preventDefault();
      } else if (password === "") {
        alert("Please enter your Password.");
        e.preventDefault();
      }
    });
  </script>

  <!-- Server-side alerts for invalid login -->
  <?php if (!empty($error_message)): ?>
    <script>
      alert("<?php echo addslashes($error_message); ?>");
    </script>
  <?php endif; ?>
</body>
</html>
