<?php
require_once 'config.php';

echo "<h2>Fleet Management System - Admin Setup</h2>\n";

// Check if tables exist
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    $userTableExists = $stmt->rowCount() > 0;
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'roles'");
    $roleTableExists = $stmt->rowCount() > 0;
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'offices'");
    $officeTableExists = $stmt->rowCount() > 0;
    
    if (!$userTableExists || !$roleTableExists || !$officeTableExists) {
        echo "<p style='color: red;'><strong>Database migration required!</strong></p>";
        echo "<p>Please run the database migration first:</p>";
        echo "<pre>mysql -u maggie_mwas -p maggie_fleet < database_improvements.sql</pre>";
        echo "<p>Or execute the contents of database_improvements.sql in your MySQL admin panel.</p>";
        exit;
    }
    
    echo "<p style='color: green;'>✓ Database tables exist</p>";
    
    // Generate password hash
    $passwordHash = password_hash('Admin123#', PASSWORD_DEFAULT);
    echo "<p>Generated password hash for 'Admin123#': <code>$passwordHash</code></p>";
    
    // Check if admin user exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute(['admin']);
    $existingAdmin = $stmt->fetch();
    
    if ($existingAdmin) {
        echo "<p style='color: orange;'>⚠ Admin user already exists</p>";
        echo "<p>Existing admin details:</p>";
        echo "<ul>";
        echo "<li>Username: " . htmlspecialchars($existingAdmin['username']) . "</li>";
        echo "<li>Email: " . htmlspecialchars($existingAdmin['email']) . "</li>";
        echo "<li>Full Name: " . htmlspecialchars($existingAdmin['full_name']) . "</li>";
        echo "<li>Status: " . htmlspecialchars($existingAdmin['status']) . "</li>";
        echo "</ul>";
        
        // Update password
        $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE username = ?");
        $stmt->execute([$passwordHash, 'admin']);
        echo "<p style='color: green;'>✓ Password updated for existing admin user</p>";
        
    } else {
        // Check if roles and offices exist
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM roles");
        $roleCount = $stmt->fetch()['count'];
        
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM offices");
        $officeCount = $stmt->fetch()['count'];
        
        if ($roleCount == 0 || $officeCount == 0) {
            echo "<p style='color: red;'>Missing roles or offices data. Please run the complete database migration.</p>";
            exit;
        }
        
        // Create admin user
        $stmt = $pdo->prepare("
            INSERT INTO users (username, email, password_hash, full_name, role_id, office_id, status) 
            VALUES (?, ?, ?, ?, ?, ?, 'active')
        ");
        $stmt->execute([
            'admin',
            'admin@fleet.com',
            $passwordHash,
            'System Administrator',
            1, // Super Admin role
            1  // HQ office
        ]);
        
        echo "<p style='color: green;'>✓ Admin user created successfully!</p>";
    }
    
    echo "<h3>Login Credentials:</h3>";
    echo "<ul>";
    echo "<li><strong>Username:</strong> admin (lowercase)</li>";
    echo "<li><strong>Password:</strong> Admin123#</li>";
    echo "</ul>";
    
    echo "<p><a href='index.php'>← Go to Login Page</a></p>";
    
} catch(PDOException $e) {
    echo "<p style='color: red;'>Database error: " . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Please check your database configuration in config.php</p>";
}
?>