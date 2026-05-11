<?php
include 'db_connect.php';

$user_id = (int)($_SESSION['user_id'] ?? 0);
$user_role = $_SESSION['role'] ?? 'staff';
$user_branch = (int)($_SESSION['branch_id'] ?? 0);

// --- 1. DEFINE RBAC FILTER ---
// Admin: No filter (sees all)
$rbac_filter = "WHERE 1=1";

if ($user_role === 'manager') {
    // Manager: Only see logs for users in their branch
    $rbac_filter = "WHERE u.branch_id = $user_branch";
} elseif ($user_role === 'staff') {
    // Staff: Only see their own logs
    $rbac_filter = "WHERE al.user_id = $user_id";
}

// --- 2. HANDLE SEARCH LOGIC (Appended to RBAC) ---
if (isset($_GET['search']) && $_GET['search'] != "") {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
    $rbac_filter .= " AND (u.name LIKE '%$search%' OR al.action LIKE '%$search%')";
}

// --- 3. FETCH DATA ---
$sql = "SELECT al.*, u.name as user_name 
        FROM activity_logs al
        LEFT JOIN users u ON al.user_id = u.id "
        . $rbac_filter . 
        " ORDER BY al.timestamp DESC";

$logs_result = mysqli_query($conn, $sql);
?>
