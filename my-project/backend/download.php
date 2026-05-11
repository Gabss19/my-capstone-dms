<?php
// CRITICAL: No spaces or lines before the <?php tag
ob_start(); // Start buffering
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    die("Error: Access Denied.");
}

$user_id = (int) $_SESSION['user_id'];
$user_role = $_SESSION['role'];
$user_branch = (int) ($_SESSION['branch_id'] ?? 0);

if (isset($_GET['id'])) {
    $doc_id = (int) $_GET['id'];

    $stmt = $conn->prepare("SELECT file_name, file_path, branch_id FROM documents WHERE id = ?");
    $stmt->bind_param("i", $doc_id);
    $stmt->execute();
    $file = $stmt->get_result()->fetch_assoc();

    if (!$file) { die("Error: Document not found."); }

    // Authorization Logic (keeping your logic)
    $is_authorized = false;
    if ($user_role === 'admin') {
        $is_authorized = true;
    } else {
        // Simple check: Branch match OR Shared access
        if ((int) $file['branch_id'] === $user_branch) {
            $is_authorized = true;
        } else {
            $share_stmt = $conn->prepare("SELECT id FROM shared_files WHERE document_id = ? AND (shared_with_user_id = ? OR shared_with_role = ?) AND (expires_at IS NULL OR expires_at > NOW())");
            $share_stmt->bind_param("iis", $doc_id, $user_id, $user_role);
            $share_stmt->execute();
            if ($share_stmt->get_result()->num_rows > 0) $is_authorized = true;
        }
    }

    if ($is_authorized) {
        $file_path = __DIR__ . "/../public/" . $file['file_path'];

        if (file_exists($file_path)) {
            // KILL ALL PREVIOUS OUTPUT
            while (ob_get_level()) { ob_end_clean(); }

            $mime_type = mime_content_type($file_path);
            header('Content-Description: File Transfer');
            header('Content-Type: ' . $mime_type);
            $mode = ($_GET['mode'] ?? '') === 'view' ? 'inline' : 'attachment';
            header("Content-Disposition: $mode; filename=\"" . basename($file['file_name']) . "\"");
            header('Content-Length: ' . filesize($file_path));
            header('Pragma: public');
            
            readfile($file_path);

            // Log activity after sending
            $action = "Downloaded: " . $file['file_name'];
            $log = $conn->prepare("INSERT INTO activity_logs (user_id, action, document_id) VALUES (?, ?, ?)");
            $log->bind_param("isi", $user_id, $action, $doc_id);
            $log->execute();
            exit();
        } else {
            die("File not found on server.");
        }
    } else {
        die("Access Denied.");
    }
}