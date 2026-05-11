<?php
include 'db_connect.php'; 

$user_id = (int)($_SESSION['user_id'] ?? 0);
$user_role = $_SESSION['role'] ?? 'staff';
$user_branch = (int)($_SESSION['branch_id'] ?? 0);

// --- 1. DEFINE FILTERS (Updated for Owner Rights) ---
$doc_filter = "WHERE 1=1";
$share_filter = "WHERE 1=1";

if ($user_role === 'manager') {
    // Managers see: Branch files OR their own uploads
    $doc_filter = "WHERE (branch_id = $user_branch OR uploaded_by = $user_id)";
    $share_filter = "WHERE document_id IN (SELECT id FROM documents WHERE branch_id = $user_branch)";
} elseif ($user_role === 'staff') {
    // Staff see: Only what is shared with them OR their own uploads
    $doc_filter = "WHERE (id IN (SELECT document_id FROM shared_files WHERE shared_with_user_id = $user_id OR shared_with_role = 'staff') OR uploaded_by = $user_id)";
    $share_filter = "WHERE (shared_with_user_id = $user_id OR shared_with_role = 'staff')";
}

// --- 2. FETCH METRICS ---
$total_docs = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM documents $doc_filter"))['total'];
$total_shared = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM shared_files $share_filter"))['total'];

// FIXED: Added parentheses around the doc_filter to ensure AND works correctly with the OR logic inside the filter
$recent_uploads = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM documents WHERE (" . substr($doc_filter, 6) . ") AND date_uploaded >= NOW() - INTERVAL 1 DAY"))['total'];

// --- 3. FETCH ACTIVITY LOGS ---
$log_query = "SELECT al.*, u.name FROM activity_logs al JOIN users u ON al.user_id = u.id";
if ($user_role === 'manager') {
    $log_query .= " WHERE u.branch_id = $user_branch";
} elseif ($user_role === 'staff') {
    $log_query .= " WHERE al.user_id = $user_id";
}
$activities = mysqli_query($conn, $log_query . " ORDER BY timestamp DESC LIMIT 5");

// --- 4. FETCH RECENT DOCUMENTS ---
$docs = mysqli_query($conn, "SELECT * FROM documents $doc_filter ORDER BY date_uploaded DESC LIMIT 5");
?>
