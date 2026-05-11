<?php
    session_start();
    if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }
    require_once '../backend/activity_logic.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Activity Logs</title>
    <link rel="stylesheet" href="css/styles.css">
</head>
<body>
    <?php include '../components/navbar.php'; ?>

    <div class="content">
        <?php include '../components/sidebar.php'; ?>

        <div class="main">
            <h2>Activity Logs</h2>

            <!-- 1. SEARCH BAR -->
            <div class="table-controls">
                <form method="GET">
                    <input type="text" name="search" id="logSearch" placeholder="Search by user or action..." 
                           value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                    <button type="submit">Search</button>
                </form>
            </div>

            <table class="ActivityLogsTable">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Action</th>
                        <th>Date & Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($logs_result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($logs_result)): ?>
                            <tr>
                                <td><strong><?= ($row['user_name'] ? htmlspecialchars($row['user_name']) : 'Unknown') ?></strong></td>
                                <td><?= htmlspecialchars($row['action']) ?></td>
                                <td><?= $row['timestamp'] ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr><td colspan="3" style="text-align:center;">No activity found.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Live Filter JS -->
    <script>
        document.getElementById('logSearch').addEventListener("keyup", function () {
            const filter = this.value.toLowerCase();
            const rows = document.querySelectorAll(".ActivityLogsTable tbody tr");
            rows.forEach(row => {
                row.style.display = row.innerText.toLowerCase().includes(filter) ? "" : "none";
            });
        });
    </script>
</body>
</html>
