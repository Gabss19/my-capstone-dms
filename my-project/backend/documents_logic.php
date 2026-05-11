<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include 'db_connect.php';

$user_id = (int) ($_SESSION['user_id'] ?? 0);
$user_role = $_SESSION['role'] ?? 'staff';
$user_branch = (int) ($_SESSION['branch_id'] ?? 0);

// --- 1. HANDLE SEARCH LOGIC ---
$search_filter = "";
if (isset($_GET['search']) && $_GET['search'] != "") {
    $search = mysqli_real_escape_string($conn, $_GET['search']);
    $search_filter = " AND (d.file_name LIKE '%$search%')";
}

// --- 2. HANDLE FILE UPLOAD (Updated for Staff & Managers) ---
if (isset($_POST['upload_document'])) {
    $original_name = $_FILES['file']['name'];
    $file_tmp = $_FILES['file']['tmp_name'];
    $file_type = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));

    $upload_dir = __DIR__ . '/../public/uploads/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    $safe_name = time() . "_" . preg_replace("/[^a-zA-Z0-9.]/", "_", $original_name);
    $target_file = "uploads/" . $safe_name;
    $absolute_path = $upload_dir . $safe_name;

    $allowed_types = ['pdf', 'docx', 'jpg', 'png', 'jpeg'];
    if (in_array($file_type, $allowed_types)) {
        if (move_uploaded_file($file_tmp, $absolute_path)) {
            // Updated to use the branch_id from the session of the person uploading
            $stmt = $conn->prepare("INSERT INTO documents (file_name, file_type, file_path, uploaded_by, branch_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssii", $original_name, $file_type, $target_file, $user_id, $user_branch);
            $stmt->execute();

            $new_doc_id = $stmt->insert_id;
            $action = "Uploaded document: $original_name";
            $log = $conn->prepare("INSERT INTO activity_logs (user_id, action, document_id) VALUES (?, ?, ?)");
            $log->bind_param("isi", $user_id, $action, $new_doc_id);
            $log->execute();

            header("Location: Documents.php?success=upload");
            exit();
        }
    }
}

// --- 3. HANDLE DELETE (Own File OR Admin) ---
if (isset($_POST['delete_doc'])) {
    $id = (int) $_POST['doc_id'];

    // Check ownership first
    $chk = $conn->prepare("SELECT uploaded_by, file_path, file_name FROM documents WHERE id = ?");
    $chk->bind_param("i", $id);
    $chk->execute();
    $doc = $chk->get_result()->fetch_assoc();

    // Permission: Admin can delete anything, others can only delete THEIR OWN uploads
    if ($user_role === 'admin' || ($doc && $doc['uploaded_by'] == $user_id)) {
        $full_path = __DIR__ . '/../public/' . $doc['file_path'];
        if (file_exists($full_path)) {
            unlink($full_path);
        }

        $del = $conn->prepare("DELETE FROM documents WHERE id = ?");
        $del->bind_param("i", $id);
        $del->execute();

        $action = "Deleted document: " . $doc['file_name'];
        $log = $conn->prepare("INSERT INTO activity_logs (user_id, action, document_id) VALUES (?, ?, ?)");
        $log->bind_param("isi", $user_id, $action, $id);
        $log->execute();

        header("Location: Documents.php?success=deleted");
        exit();
    } else {
        die("⛔ Unauthorized action: You can only delete files you uploaded.");
    }
}

// --- 4. HANDLE SHARE (Admin/Manager Only) ---
if (isset($_POST['share_document'])) {
    if (!in_array($user_role, ['admin', 'manager'])) {
        die("⛔ Access Denied: You are not allowed to share files.");
    }
    $doc_id = (int) $_POST['document_id'];
    if ($user_role === 'manager') {
        $chk = $conn->prepare("SELECT branch_id FROM documents WHERE id = ?");
        $chk->bind_param("i", $doc_id);
        $chk->execute();
        $doc = $chk->get_result()->fetch_assoc();
        if (!$doc || (int) $doc['branch_id'] !== $user_branch) {
            die("⛔ Access Denied: Managers can only share files from their own branch.");
        }
    }
    $with_id = !empty($_POST['shared_with_id']) ? (int) $_POST['shared_with_id'] : null;
    $with_role = !empty($_POST['shared_with_role']) ? $_POST['shared_with_role'] : null;
    $expires = !empty($_POST['expires_at']) ? date('Y-m-d H:i:s', strtotime($_POST['expires_at'])) : null;

    if (($with_id && $with_role) || (!$with_id && !$with_role)) {
        die("❌ Security Error: Choose exactly one recipient.");
    }

    $stmt = $conn->prepare("INSERT INTO shared_files (document_id, shared_with_user_id, shared_with_role, shared_by, expires_at) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iisis", $doc_id, $with_id, $with_role, $user_id, $expires);
    $stmt->execute();
    header("Location: Documents.php?success=shared");
    exit();
}

// --- 5. PREPARE VIEW DATA ---
$users_res = mysqli_query($conn, "SELECT id, name, role FROM users ORDER BY name");

$base_query = "SELECT d.* FROM documents d";

if ($user_role === 'admin') {
    $sql = $base_query . " WHERE 1=1" . $search_filter;
} else {
    // Everyone else sees: Their own uploads OR files shared with them/their role
    $sql = "SELECT DISTINCT d.* FROM documents d 
        LEFT JOIN shared_files s ON d.id = s.document_id 
            WHERE (d.uploaded_by = $user_id 
               OR s.shared_with_user_id = $user_id 
               OR s.shared_with_role = '$user_role') 
            AND (s.expires_at IS NULL OR s.expires_at > NOW() OR d.uploaded_by = $user_id)" . $search_filter;
}

$documents_res = mysqli_query($conn, $sql . " ORDER BY d.date_uploaded DESC");
$share_doc_id = isset($_POST['open_share']) ? (int) $_POST['open_share_id'] : null;
?>