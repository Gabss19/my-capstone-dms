<nav class="navbar">
    <div class="nav-left">
        <h1>DocuManager</h1>
    </div>
    
    <div class="nav-right">
        <div class="user-info">
            <span class="user-name">
                Welcome, <strong><?php echo htmlspecialchars($_SESSION['user_name']); ?></strong>
            </span>
            
            <span class="role-badge <?= htmlspecialchars($_SESSION['role']); ?>">
                <?= strtoupper(htmlspecialchars($_SESSION['role'])); ?> 
                <?php if($_SESSION['role'] !== 'admin' && !empty($_SESSION['branch_name'])): ?>
                    (<?= htmlspecialchars($_SESSION['branch_name']); ?>)
                <?php endif; ?>
            </span>
        </div>
        
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>
</nav>