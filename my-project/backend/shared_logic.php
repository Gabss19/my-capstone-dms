<?php
include 'db_connect.php';

$user_id = (int)($_SESSION['user_id'] ?? 0);
$user_role = $_SESSION['role'] ?? 'staff';
$user_branch = (int)($_SESSION['branch_id'] ?? 0);

// --- 1. HANDLE SHARE ACTION (Backend Enforcement) ---
if (isset($_POST['share_document'])) {
    
    // FIX 1: Block staff from sharing via backend gate
    if (!in_array($user_role, ['admin', 'manager'])) {
        die("⛔ Access Denied: You are not allowed to share files.");
    }

    $doc_id = (int)$_POST['document_id'];

    // FIX 2: Limit manager sharing by branch
    if ($user_role === 'manager') {
        $chk = $conn->prepare("SELECT branch_id FROM documents WHERE id = ?");
        $chk->bind_param("i", $doc_id);
        $chk->execute();
        $doc = $chk->get_result()->fetch_assoc();

        if (!$doc || (int)$doc['branch_id'] !== $user_branch) {
            die("⛔ Access Denied: Managers can only share files from their own branch.");
        }
    }

    $with_id = !empty($_POST['shared_with_id']) ? (int)$_POST['shared_with_id'] : null;
    $with_role = !empty($_POST['shared_with_role']) ? $_POST['shared_with_role'] : null;
    
    // FIX 3: Normalize expires_at to MySQL format
    $expires = !empty($_POST['expires_at']) 
        ? date('Y-m-d H:i:s', strtotime($_POST['expires_at'])) 
        : null;

    // ENFORCEMENT: Block if both are picked OR both are empty
    if (($with_id && $with_role) || (!$with_id && !$with_role)) {
        die("❌ Security Error: Please select exactly one recipient (User OR Role).");
    }

    $stmt = $conn->prepare("INSERT INTO shared_files (document_id, shared_with_user_id, shared_with_role, shared_by, expires_at) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iisis", $doc_id, $with_id, $with_role, $user_id, $expires);
    $stmt->execute();

    header("Location: Documents.php?success=shared");
    exit();
}

// --- 2. SEARCH LOGIC ---
$search_query = "";
if (!empty($_GET['search'])) {
    $s = mysqli_real_escape_string($conn, $_GET['search']);
    $search_query = " AND (d.file_name LIKE '%$s%' OR u_to.name LIKE '%$s%' OR u_from.name LIKE '%$s%' OR sf.shared_with_role LIKE '%$s%')";
}

// --- 3. RBAC FILTERING ---
if ($user_role === 'admin') {
    $rbac_condition = "WHERE 1=1";
} elseif ($user_role === 'manager') {
    $rbac_condition = "WHERE (d.branch_id = $user_branch OR sf.shared_with_user_id = $user_id OR sf.shared_with_role = 'manager')";
} else {
    $rbac_condition = "WHERE (sf.shared_with_user_id = $user_id OR sf.shared_with_role = 'staff')";
}

// --- 4. EXECUTE QUERY ---
$sql = "SELECT 
            sf.*, 
            d.file_name, 
            u_from.name AS shared_by_name,
            COALESCE(u_to.name, sf.shared_with_role, 'Everyone') AS recipient_name
        FROM shared_files sf
        LEFT JOIN documents d ON sf.document_id = d.id
        LEFT JOIN users u_from ON sf.shared_by = u_from.id
        LEFT JOIN users u_to ON sf.shared_with_user_id = u_to.id " 
        . $rbac_condition . $search_query . 
        " ORDER BY sf.date_shared DESC";

$shared_result = mysqli_query($conn, $sql);
