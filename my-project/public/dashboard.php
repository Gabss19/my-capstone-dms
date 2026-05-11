<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
// Pull in the logic (SQL queries) from the backend
include '../backend/dashboard_logic.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/styles.css">
    <title>Dashboard</title>
</head>

<body>
    <!-- Navbar -->
    <?php include '../components/navbar.php'; ?>

    <div class="content">
        <!-- Sidebar -->
        <?php include '../components/sidebar.php'; ?>

        <!-- MAIN CONTENT -->
        <div class="main">
            <h2>Dashboard Overview</h2>

            <div class="metricsTable">

                <div class="metric">
                    <div class="info">
                        <span class="label">Total Documents</span>
                        <span class="value"><?php echo number_format($total_docs); ?></span>
                    </div>
                    <div class="icon"><img src="images/mod2.png"></div>
                </div>

                <div class="metric">
                    <div class="info">
                        <span class="label">Files Shared</span>
                        <span class="value"><?php echo number_format($total_shared); ?></span>
                    </div>
                    <div class="icon"><img src="images/mod3.png"></div>
                </div>

                <div class="metric">
                    <div class="info">
                        <span class="label">Recent Uploads (24h)</span>
                        <span class="value"><?php echo number_format($recent_uploads); ?></span>
                    </div>
                    <div class="icon"><img src="images/mod4.png"></div>
                </div>
            </div>

            <!-- Recent Activity Table -->
            <table class="recentActivity">
                <thead>
                    <tr>
                        <th colspan="2">
                            <div class="header-cell">
                                <div class="label">Recent Activity</div>
                                <div class="description">Latest actions from your team</div>
                            </div>
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($activities) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($activities)): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($row['name']) ?></strong>
                                    <?= htmlspecialchars($row['action']) ?></td>
                                <td style='text-align:right; color:#888; font-size:12px;'><?= $row['timestamp'] ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan='2'>No recent activity.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Recent Documents Table -->
            <table class="recentDocuments">
                <thead>
                    <tr>
                        <th colspan="4">
                            <div class="header-cell">
                                <div class="label">Recent Documents</div>
                                <div class="description">Latest uploads</div>
                            </div>
                        </th>
                    </tr>
                    <tr>
                        <th>Document Name</th>
                        <th>Type</th>
                        <th>Date Uploaded</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (mysqli_num_rows($docs) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($docs)): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['file_name']) ?></td>
                                <td><?= strtoupper($row['file_type']) ?></td>
                                <td><?= $row['date_uploaded'] ?></td>
                                <td><span style='color:green;'>Active</span></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan='4'>No documents found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>