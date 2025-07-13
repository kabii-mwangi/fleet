<nav class="navbar">
    <div class="nav-container">
        <div class="nav-brand">
            <a href="dashboard.php">🚗 Fleet Manager</a>
        </div>
        
        <ul class="nav-menu">
            <li><a href="dashboard.php" class="nav-link">Dashboard</a></li>
            <?php if (hasPermission('vehicles_view')): ?>
                <li><a href="vehicles.php" class="nav-link">Vehicles</a></li>
            <?php endif; ?>
            <?php if (hasPermission('fuel_logs_view')): ?>
                <li><a href="fuel-logs.php" class="nav-link">Fuel Logs</a></li>
            <?php endif; ?>
            <?php if (hasPermission('employees_view')): ?>
                <li><a href="employees.php" class="nav-link">Employees</a></li>
            <?php endif; ?>
            <?php if (hasPermission('departments_view')): ?>
                <li><a href="departments.php" class="nav-link">Departments</a></li>
            <?php endif; ?>
            <?php if (hasPermission('reports_view')): ?>
                <li><a href="reports.php" class="nav-link">Reports</a></li>
            <?php endif; ?>
            <?php if (hasPermission('users_view')): ?>
                <li><a href="users.php" class="nav-link">Users</a></li>
            <?php endif; ?>
            <li><a href="logout.php" class="nav-link logout">Logout</a></li>
        </ul>
        
        <div class="nav-user">
            <div class="user-info">
                <span class="user-name"><?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username'] ?? 'User'); ?></span>
                <span class="user-role"><?php echo htmlspecialchars($_SESSION['role_name'] ?? 'Admin'); ?></span>
                <span class="user-office"><?php echo htmlspecialchars($_SESSION['office_name'] ?? 'HQ'); ?></span>
            </div>
        </div>
    </div>
</nav>

<style>
.nav-user .user-info {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    font-size: 0.85rem;
}

.user-name {
    font-weight: 600;
    color: #333;
}

.user-role {
    color: #666;
    font-size: 0.8rem;
}

.user-office {
    color: #888;
    font-size: 0.75rem;
}

@media (max-width: 768px) {
    .nav-user .user-info {
        align-items: center;
    }
    
    .nav-user .user-info span {
        display: block;
    }
}
</style>