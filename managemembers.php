<?php
// managemembers.php

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "librarymanagementsystem01";

// Connect to DB
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle AJAX requests
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    $action = $_GET['action'];

    if ($action === 'list') {
        $searchId = $_GET['searchId'] ?? '';
        $searchRole = $_GET['searchRole'] ?? '';

        $sql = "SELECT * FROM users WHERE 1=1";
        $params = [];
        $types = '';

        if ($searchId !== '') {
            $sql .= " AND user_id LIKE ?";
            $searchId = "%$searchId%";
            $params[] = &$searchId;
            $types .= 's';
        }
        if ($searchRole !== '') {
            $sql .= " AND role = ?";
            $params[] = &$searchRole;
            $types .= 's';
        }

        $stmt = $conn->prepare($sql);
        if ($params) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        $members = [];
        while ($row = $result->fetch_assoc()) {
            $members[] = [
                'id' => $row['user_id'],
                'first_name' => $row['first_name'],
                'last_name' => $row['last_name'],
                'email' => $row['email'],
                'phone' => $row['phone'],
                'role' => $row['role'],
                'registered_date' => $row['registered_date']
            ];
        }
        echo json_encode(["success" => true, "data" => $members]);
        exit;
    }
    elseif ($action === 'add') {
        $data = json_decode(file_get_contents('php://input'), true);

        if (
            empty($data['id']) || empty($data['first_name']) || empty($data['last_name']) || empty($data['email']) ||
            empty($data['phone']) || empty($data['role']) || empty($data['registered_date'])
        ) {
            echo json_encode(["success" => false, "message" => "All fields are required"]);
            exit;
        }

        $stmt = $conn->prepare("INSERT INTO users (user_id, first_name, last_name, email, phone, role, registered_date) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssssss", $data['id'], $data['first_name'], $data['last_name'], $data['email'], $data['phone'], $data['role'], $data['registered_date']);
        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Member added"]);
        } else {
            echo json_encode(["success" => false, "message" => "Insert failed: " . $stmt->error]);
        }
        exit;
    }
    elseif ($action === 'update') {
        $data = json_decode(file_get_contents('php://input'), true);

        if (
            empty($data['id']) || empty($data['first_name']) || empty($data['last_name']) || empty($data['email']) ||
            empty($data['phone']) || empty($data['role']) || empty($data['registered_date'])
        ) {
            echo json_encode(["success" => false, "message" => "All fields are required"]);
            exit;
        }

        $stmt = $conn->prepare("UPDATE users SET first_name=?, last_name=?, email=?, phone=?, role=?, registered_date=? WHERE user_id=?");
        $stmt->bind_param("sssssss", $data['first_name'], $data['last_name'], $data['email'], $data['phone'], $data['role'], $data['registered_date'], $data['id']);
        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Member updated"]);
        } else {
            echo json_encode(["success" => false, "message" => "Update failed: " . $stmt->error]);
        }
        exit;
    }
    elseif ($action === 'delete') {
        $id = $_GET['id'] ?? '';
        if ($id === '') {
            echo json_encode(["success" => false, "message" => "ID is required"]);
            exit;
        }
        $stmt = $conn->prepare("DELETE FROM users WHERE user_id=?");
        $stmt->bind_param("s", $id);
        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Member deleted"]);
        } else {
            echo json_encode(["success" => false, "message" => "Delete failed: " . $stmt->error]);
        }
        exit;
    }
    else {
        echo json_encode(["success" => false, "message" => "Invalid action"]);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Manage Members</title>
  <link rel="stylesheet" href="managemembers.css" />
  <link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"
  />
</head>
<body>
  <div class="container">
    <aside class="sidebar">
      <div class="sidebar-header">
        <h2>KnowledgeNest LMS</h2>
      </div>
      <nav class="menu">
        <a href="librarianprofile.php" class="menu-item ">
          <i class="fas fa-user-circle"></i> My profile
        </a>
        <a href="managebooks.php" class="menu-item">
          <i class="fas fa-book"></i> Manage Books
        </a>
        <a href="manageborrowings.php" class="menu-item">
          <i class="fas fa-book-reader"></i> Manage Borrowing
        </a>
        <a href="managemembers.php" class="menu-item active">
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
        <a href="index.html" onclick="return logout()" class="menu-item logout">
          <i class="fas fa-sign-out-alt"></i> Logout
        </a>
      </nav>
    </aside>

    <div class="main-content">
      <h1>Manage Members</h1>

      <div id="memberFormContainer">
        <!-- Hidden input to store original ID when editing -->
        <input type="hidden" id="memberId" />

        <label for="userId">User ID:</label>
        <input type="text" id="userId" placeholder="Enter user ID" />

        <label for="firstName">First Name:</label>
        <input type="text" id="firstName" />

        <label for="lastName">Last Name:</label>
        <input type="text" id="lastName" />

        <label for="email">Email:</label>
        <input type="email" id="email" />

        <label for="phone">Phone Number:</label>
        <input type="text" id="phone" />

        <label for="role">User Type:</label>
        <select id="role">
          <option value="student">Student</option>
          <option value="staff">Staff</option>
        </select>

        <label for="registeredDate">Registered Date:</label>
        <input type="date" id="registeredDate" />

        <button id="saveMemberBtn" type="button">Register / Update Member</button>
      </div>

      <div class="search-section">
        <h2>Search Members</h2>
        <div class="search-controls">
          <input type="text" id="searchId" placeholder="Search by User ID" />
          <select id="searchRole">
            <option value="">All</option>
            <option value="student">Student</option>
            <option value="staff">Staff</option>
          </select>
          <button type="button" id="searchBtn">
            <i class="fas fa-search"></i> Search
          </button>
        </div>
      </div>

      <table id="membersTable">
        <thead>
          <tr>
            <th>User ID</th>
            <th>First Name</th>
            <th>Last Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Type</th>
            <th>Registered</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>

<script>
  const membersTableBody = document.querySelector("#membersTable tbody");
  const memberIdInput = document.getElementById("memberId"); // hidden input for editing original ID
  const userIdInput = document.getElementById("userId");     // visible User ID input
  const firstNameInput = document.getElementById("firstName");
  const lastNameInput = document.getElementById("lastName");
  const emailInput = document.getElementById("email");
  const phoneInput = document.getElementById("phone");
  const roleSelect = document.getElementById("role");
  const registeredDateInput = document.getElementById("registeredDate");
  const saveMemberBtn = document.getElementById("saveMemberBtn");

  const searchIdInput = document.getElementById("searchId");
  const searchRoleSelect = document.getElementById("searchRole");
  const searchBtn = document.getElementById("searchBtn");

  function renderMembers(members) {
    membersTableBody.innerHTML = "";
    if (members.length === 0) {
      membersTableBody.innerHTML = "<tr><td colspan='8'>No members found</td></tr>";
      return;
    }
    members.forEach(m => {
      const tr = document.createElement("tr");
      tr.innerHTML = `
        <td>${m.id}</td>
        <td>${m.first_name}</td>
        <td>${m.last_name}</td>
        <td>${m.email}</td>
        <td>${m.phone}</td>
        <td>${m.role}</td>
        <td>${m.registered_date}</td>
        <td>
          <button onclick="editMember('${m.id}')">Edit</button>
          <button onclick="deleteMember('${m.id}')">Delete</button>
        </td>
      `;
      membersTableBody.appendChild(tr);
    });
  }

  async function fetchMembers() {
    const searchId = searchIdInput.value.trim();
    const searchRole = searchRoleSelect.value;
    let url = `managemembers.php?action=list`;
    if (searchId) {
      url += `&searchId=${encodeURIComponent(searchId)}`;
    }
    if (searchRole) {
      url += `&searchRole=${encodeURIComponent(searchRole)}`;
    }
    const res = await fetch(url);
    const data = await res.json();
    if (data.success) {
      renderMembers(data.data);
    } else {
      alert("Failed to fetch members");
    }
  }

  function clearForm() {
    memberIdInput.value = "";
    userIdInput.value = "";
    firstNameInput.value = "";
    lastNameInput.value = "";
    emailInput.value = "";
    phoneInput.value = "";
    roleSelect.value = "student";
    registeredDateInput.value = "";
  }

  function editMember(id) {
    // Find the member data in the current table rows or fetch again
    // We'll fetch all members again and pick the one to edit (simpler)
    fetch(`managemembers.php?action=list`)
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          const member = data.data.find(m => m.id === id);
          if (member) {
            memberIdInput.value = member.id; // store original id in hidden input
            userIdInput.value = member.id;   // show in visible input so user can edit if needed
            firstNameInput.value = member.first_name;
            lastNameInput.value = member.last_name;
            emailInput.value = member.email;
            phoneInput.value = member.phone;
            roleSelect.value = member.role;
            registeredDateInput.value = member.registered_date;
          } else {
            alert("Member not found");
          }
        } else {
          alert("Failed to load member data");
        }
      });
  }

  async function deleteMember(id) {
    if (!confirm("Are you sure you want to delete this member?")) return;

    const res = await fetch(`managemembers.php?action=delete&id=${encodeURIComponent(id)}`);
    const data = await res.json();
    if (data.success) {
      alert(data.message);
      fetchMembers();
    } else {
      alert("Delete failed: " + data.message);
    }
  }

  saveMemberBtn.addEventListener("click", async () => {
    const id = userIdInput.value.trim();
    const first_name = firstNameInput.value.trim();
    const last_name = lastNameInput.value.trim();
    const email = emailInput.value.trim();
    const phone = phoneInput.value.trim();
    const role = roleSelect.value;
    const registered_date = registeredDateInput.value;

    if (!id || !first_name || !last_name || !email || !phone || !role || !registered_date) {
      alert("All fields are required");
      return;
    }

    const isUpdate = memberIdInput.value !== "";
    const payload = {
      id: id,
      first_name,
      last_name,
      email,
      phone,
      role,
      registered_date,
    };

    // If updating, the id in the DB is memberIdInput.value
    // But user may have changed the User ID field (id), so we handle that:
    if (isUpdate && memberIdInput.value !== id) {
      // User changed the ID — so backend must handle this.
      // We'll delete the old record and add a new one for simplicity, or you can handle ID update in SQL.
      // Here, let's handle update with new ID and old ID.

      // We'll send both IDs to backend for update (need to change PHP).
      // For now, simplify: reject ID changes on update (ask user to keep the same ID).

      alert("Changing User ID on update is not supported. Please keep the same User ID.");
      return;
    }

    const action = isUpdate ? "update" : "add";

    const res = await fetch(`managemembers.php?action=${action}`, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload),
    });
    const data = await res.json();

    if (data.success) {
      alert(data.message);
      clearForm();
      fetchMembers();
    } else {
      alert("Error: " + data.message);
    }
  });

  searchBtn.addEventListener("click", fetchMembers);

  // Load members on page load
  fetchMembers();

  // Logout confirmation
  function logout() {
    return confirm("Are you sure you want to logout?");
  }
</script>
</body>
</html>
