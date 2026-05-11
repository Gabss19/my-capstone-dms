<?php
include 'db_connect.php';

// --- 1. ACCESS CONTROL ---
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo "<script>
        alert('Access Denied: You do not have permission to access User Management.');
        window.location.href = 'dashboard.php';
    </script>";
    exit();
}
// --- 2. HANDLE SEARCH (Updated with Aliases) ---
$search_filter = "";
if (isset($_GET['search']) && $_GET['search'] != "") {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
    // Using u. for the users table to avoid ambiguity
    $search_filter = " WHERE u.name LIKE '%$search%' OR u.email LIKE '%$search%'";
}

// --- 3. HANDLE ADD USER (Prepared Statements) ---
if (isset($_POST['add_user'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $role = $_POST['role'];
    $branch_id = !empty($_POST['branch_id']) ? (int)$_POST['branch_id'] : null;
    $hashed_password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, branch_id) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssi", $name, $email, $hashed_password, $role, $branch_id);

    if ($stmt->execute()) {
        header("Location: User-Management.php?success=added");
        exit();
    }
}

// --- 4. HANDLE DELETE ---
if (isset($_POST['delete_user'])) {
    $id_to_delete = (int) $_POST['user_id'];
    if ($id_to_delete == $_SESSION['user_id']) {
        echo "<script>alert('❌ Error: You cannot delete your own account!'); window.location='User-Management.php';</script>";
        exit();
    }
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $id_to_delete);
    $stmt->execute();
    header("Location: User-Management.php?success=deleted");
    exit();
}

// --- 5. FETCH USERS (Includes Branch Join and Last Login) ---
// We MUST select u.last_login for the status function to work!
$query = "SELECT u.id, u.name, u.email, u.role, u.branch_id, u.last_login, b.branch_name 
          FROM users u 
          LEFT JOIN branch b ON u.branch_id = b.branch_idPK" . $search_filter . " 
          ORDER BY u.name ASC";

$users_list = mysqli_query($conn, $query);

// Fetch branches for the dropdown menu
$branches_list = mysqli_query($conn, "SELECT * FROM branch ORDER BY branch_name ASC");

// --- 6. ACTIVE Status Function (Encapsulated Logic) ---
function get_user_status($last_login) {
    if (!$last_login) return "Offline";
    $current_time = time();
    $login_timestamp = strtotime($last_login);
    // 300 seconds = 5 minutes threshold
    return (($current_time - $login_timestamp) < 300) ? "Online" : "Offline";
}