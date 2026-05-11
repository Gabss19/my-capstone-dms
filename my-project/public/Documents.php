<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
require_once '../backend/documents_logic.php';

// Fallback role safety
$current_role = $_SESSION['role'] ?? 'staff';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documents Management</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/styles.css">
</head>

<body>
    <?php include '../components/navbar.php'; ?>

    <div class="content">
        <?php include '../components/sidebar.php'; ?>

        <div class="main">
            <h2>Documents</h2>

            <div class="table-controls">
                <form method="GET" class="search-form">
                    <div class="search-wrapper">
                        <i class="fa-solid fa-magnifying-glass"></i>
                        <input type="text" name="search" id="docSearch" placeholder="Search files..."
                            value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                    </div>
                </form>
            </div>

            <?php if ($current_role === 'admin' || $current_role === 'manager' || $current_role === 'staff'): ?>
                <div class="admin-form">
                    <h3><i class="fa-solid fa-cloud-arrow-up"></i> Upload New Document</h3>
                    <form action="" method="POST" enctype="multipart/form-data">
                        <input type="file" name="file" required>
                        <button type="submit" name="upload_document" class="btn-add">Upload</button>
                    </form>
                </div>
            <?php endif; ?>

            <?php if (isset($share_doc_id) && $share_doc_id && ($current_role === 'admin' || $current_role === 'manager')): ?>
                <div class="admin-form share-modal-style">
                    <h3><i class="fa-solid fa-share-nodes"></i> Share Document ID: <?= $share_doc_id ?></h3>
                    <p class="form-help">Choose either a specific user OR an entire role to share with.</p>
                    <form method="POST">
                        <input type="hidden" name="document_id" value="<?= $share_doc_id ?>">

                        <div class="share-options">
                            <div class="option-group">
                                <label><strong>Option A:</strong> Specific User</label>
                                <select name="shared_with_id" id="shareUser">
                                    <option value="">-- Select User --</option>
                                    <?php mysqli_data_seek($users_res, 0); ?>
                                    <?php while ($u = mysqli_fetch_assoc($users_res)): ?>
                                        <option value="<?= $u['id'] ?>">
                                            <?= htmlspecialchars($u['name']) ?> (<?= strtoupper($u['role']) ?>)
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="option-group">
                                <label><strong>Option B:</strong> Entire Role</label>
                                <select name="shared_with_role" id="shareRole">
                                    <option value="">-- Select Role --</option>
                                    <option value="admin">Admins</option>
                                    <option value="manager">Managers</option>
                                    <option value="staff">Staff</option>
                                </select>
                            </div>
                        </div>

                        <div style="margin-top: 15px;">
                            <label>Expiry (Optional):</label>
                            <input type="datetime-local" name="expires_at">
                        </div>

                        <div style="margin-top:20px;">
                            <button type="submit" name="share_document" class="btn-update">Confirm Share</button>
                            <a href="Documents.php" class="btn-cancel">Cancel</a>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <table class="DocumentsTable">
                <thead>
                    <tr>
                        <th>File Name</th>
                        <th>Date</th>
                        <th>Type</th>
                        <th style="text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($documents_res) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($documents_res)): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($row['file_name']) ?></strong></td>
                                <td><?= date('M d, Y', strtotime($row['date_uploaded'])) ?></td>
                                <td><span class="status-pill"><?= strtoupper($row['file_type']) ?></span></td>
                                <td class="action-cell">
                                    <a href="../backend/download.php?id=<?= $row['id'] ?>&mode=view" target="_blank"
                                       class="icon-btn btn-view" title="View">
                                       <i class="fa-solid fa-eye"></i>
                                    </a>

                                    <a href="../backend/download.php?id=<?= $row['id'] ?>" 
                                       class="icon-btn btn-download" title="Download">
                                       <i class="fa-solid fa-download"></i>
                                    </a>

                                    <?php
                                    $can_share = ($current_role === 'admin' || ($current_role === 'manager' && $row['branch_id'] == $_SESSION['branch_id']));
                                    if ($can_share): ?>
                                        <form method="POST" style="display:inline;">
                                            <input type="hidden" name="open_share_id" value="<?= $row['id'] ?>">
                                            <button type="submit" name="open_share" class="icon-btn btn-share" title="Share">
                                                <i class="fa-solid fa-share-nodes"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <?php
                                    $is_owner = (isset($row['uploaded_by']) && $row['uploaded_by'] == $_SESSION['user_id']);
                                    if ($current_role === 'admin' || $is_owner): ?>
                                        <form method="POST" style="display:inline;"
                                              onsubmit="return confirm('Delete this document permanently?');">
                                            <input type="hidden" name="doc_id" value="<?= $row['id'] ?>">
                                            <button type="submit" name="delete_doc" class="icon-btn btn-delete" title="Delete">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="4" style="text-align:center;">No documents available.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const searchInput = document.getElementById('docSearch');
            if (searchInput) {
                searchInput.addEventListener("keyup", function () {
                    const filter = this.value.toLowerCase();
                    const rows = document.querySelectorAll(".DocumentsTable tbody tr");
                    rows.forEach(row => {
                        const text = row.textContent.toLowerCase();
                        row.style.display = text.includes(filter) ? "" : "none";
                    });
                });
            }

            const shareUser = document.getElementById('shareUser');
            const shareRole = document.getElementById('shareRole');
            if (shareUser && shareRole) {
                const toggle = (el, other) => {
                    if (el.value !== "") {
                        other.value = "";
                        other.style.opacity = "0.5";
                        other.disabled = true;
                    } else {
                        other.style.opacity = "1";
                        other.disabled = false;
                    }
                };
                shareUser.addEventListener('change', () => toggle(shareUser, shareRole));
                shareRole.addEventListener('change', () => toggle(shareRole, shareUser));
            }
        });
    </script>
</body>
</html>