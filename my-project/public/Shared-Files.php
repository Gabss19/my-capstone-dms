<?php
    session_start();
    if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }
    require_once '../backend/shared_logic.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Shared Files</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <?php include '../components/navbar.php'; ?>

    <div class="content">
        <?php include '../components/sidebar.php'; ?>

        <div class="main">
            <h2>Shared Files</h2>

            <div class="table-controls">
                <form method="GET">
                    <input type="text" name="search" id="sharedSearch" placeholder="Search files, recipients..." 
                           value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                    <button type="submit">Search</button>
                </form>
            </div>

            <table class="SharedFilesTable">
                <thead>
                    <tr>
                        <th>File Name</th>
                        <th>Shared With</th>
                        <th>Shared By</th>
                        <th>Date</th>
                        <th>Status / Expiry</th>
                        <th>Access</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($shared_result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($shared_result)): 
                            $is_expired = ($row['expires_at'] && strtotime($row['expires_at']) < time());
                        ?>
                            <tr style="<?= $is_expired ? 'opacity: 0.6;' : '' ?>">
                                <td><?= htmlspecialchars($row['file_name'] ?? 'Deleted File') ?></td>
                                <!-- UPDATED: Uses recipient_name (Name or Role) instead of old string -->
                                <td><?= htmlspecialchars($row['recipient_name']) ?></td>
                                <td><?= htmlspecialchars($row['shared_by_name']) ?></td>
                                <td><?= date('M d, Y', strtotime($row['date_shared'])) ?></td>
                                <td>
                                    <?php if ($is_expired): ?>
                                        <span class="status-pill" style="background:#f8d7da; color:#721c24;">Expired</span>
                                    <?php elseif ($row['expires_at']): ?>
                                        <small>Until: <?= $row['expires_at'] ?></small>
                                    <?php else: ?>
                                        <span class="status-pill">Permanent</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!$is_expired && isset($row['document_id'])): ?>
                                        <!-- SECURE: Points to download.php gatekeeper -->
                                        <a href="../backend/download.php?id=<?= $row['document_id'] ?>&mode=view" target="_blank" class="btn-edit">Open</a>
                                    <?php else: ?>
                                        <span style="color:gray;">Locked</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="6" style="text-align:center;">No shared files found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        document.getElementById('sharedSearch').addEventListener("keyup", function () {
            const filter = this.value.toLowerCase();
            const rows = document.querySelectorAll(".SharedFilesTable tbody tr");
            rows.forEach(row => {
                row.style.display = row.innerText.toLowerCase().includes(filter) ? "" : "none";
            });
        });
    </script>
</body>
</html>
