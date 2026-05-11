<?php
include 'db_connect.php';

$error = "";

if (isset($_POST['login'])) {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];

    // 1. SECURE SEARCH: Joined with branch table to get the name
    $stmt = $conn->prepare("
        SELECT u.id, u.name, u.password, u.role, u.branch_id, b.branch_name 
        FROM users u 
        LEFT JOIN branch b ON u.branch_id = b.branch_idPK 
        WHERE u.email = ?
    ");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['branch_id'] = $user['branch_id'];

            // This is the missing piece for your navbar!
            $_SESSION['branch_name'] = $user['branch_name'] ?? 'No Branch';

            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Invalid email or password";
        }
    }
}
?>