<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once '../backend/user_mgmt_logic.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>User Management</title>
    <link rel="stylesheet" href="css/styles.css">
</head>

<body>
    <?php include '../components/navbar.php'; ?>

    <div class="content">
        <?php include '../components/sidebar.php'; ?>

        <div class="main">
            <h2>User Management</h2>

            <div class="table-controls">
                <form method="GET">
                    <input type="text" name="search" placeholder="Search users by name or email..."
                        value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                    <button type="submit">Search</button>
                </form>
            </div>

            <div class="admin-form">
                <h3>Add New User</h3>
                <form method="POST">
                    <input type="text" name="name" placeholder="Full Name" required>
                    <input type="email" name="email" placeholder="Email Address" required>
                    <input type="password" name="password" placeholder="Password" required>
                    
                    <div style="display: flex; gap: 10px; margin-top: 10px;">
                        <select name="role" style="flex: 1;">
                            <option value="staff">Staff</option>
                            <option value="manager">Manager</option>
                            <option value="admin">Admin</option>
                        </select>
                        
                        <select name="branch_id" style="flex: 1;" required>
                            <option value="" disabled selected>Select Branch</option>
                            <?php 
                            mysqli_data_seek($branches_list, 0); 
                            while ($b = mysqli_fetch_assoc($branches_list)): ?>
                                <option value="<?= $b['branch_idPK'] ?>">
                                    <?= htmlspecialchars($b['branch_name']) ?> Branch
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <button type="submit" name="add_user" class="btn-add" style="margin-top: 15px;">Add User</button>
                </form>
            </div>

            <table class="UserTable">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Branch</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($row = mysqli_fetch_assoc($users_list)): ?>
                        <tr>
                            <td><?= htmlspecialchars($row['name']) ?></td>
                            <td><?= htmlspecialchars($row['email']) ?></td>
                            <td><?= ucfirst($row['role']) ?></td>
                            <td>
                                <?= $row['role'] === 'admin' ? 'Global' : ($row['branch_name'] ? htmlspecialchars($row['branch_name']) : "None") ?>
                            </td>
                            <td>
                                <?php 
                                    $status = get_user_status($row['last_login']); 
                                    $class = ($status === 'Online') ? 'status-online' : 'status-offline';
                                ?>
                                <span class="status-pill <?= $class ?>"><?= $status ?></span>
                            </td>
                            <td>
                                <form method='POST' style='display:inline;' onsubmit='return confirm("Delete this user?");'>
                                    <input type='hidden' name='user_id' value='<?= $row['id'] ?>'>
                                    <button type='submit' name='delete_user' class='btn-delete'>Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>