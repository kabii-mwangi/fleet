<?php
require_once 'config.php';

echo "<h2>Fleet Management Database Setup</h2>\n";

try {
    // Read and execute the main database schema
    $sql_content = file_get_contents('fleet_management_mysql.sql');
    if ($sql_content === false) {
        throw new Exception("Could not read fleet_management_mysql.sql");
    }
    
    // Split SQL content into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql_content)));
    
    echo "<p>Creating main database tables...</p>\n";
    foreach ($statements as $statement) {
        if (!empty($statement) && !str_starts_with(trim($statement), '--')) {
            try {
                $pdo->exec($statement);
            } catch (PDOException $e) {
                // Skip if table already exists
                if (strpos($e->getMessage(), 'already exists') === false) {
                    echo "<p style='color: orange;'>Warning: " . htmlspecialchars($e->getMessage()) . "</p>\n";
                }
            }
        }
    }
    
    // Read and execute the improvements (users, roles, offices)
    $improvements_content = file_get_contents('database_improvements.sql');
    if ($improvements_content === false) {
        throw new Exception("Could not read database_improvements.sql");
    }
    
    $improvement_statements = array_filter(array_map('trim', explode(';', $improvements_content)));
    
    echo "<p>Setting up user management system...</p>\n";
    foreach ($improvement_statements as $statement) {
        if (!empty($statement) && !str_starts_with(trim($statement), '--')) {
            try {
                $pdo->exec($statement);
            } catch (PDOException $e) {
                // Skip if already exists or other expected errors
                if (strpos($e->getMessage(), 'already exists') === false && 
                    strpos($e->getMessage(), 'Duplicate entry') === false) {
                    echo "<p style='color: orange;'>Warning: " . htmlspecialchars($e->getMessage()) . "</p>\n";
                }
            }
        }
    }
    
    // Verify the admin user exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = 'admin'");
    $stmt->execute();
    $adminExists = $stmt->fetchColumn() > 0;
    
    if (!$adminExists) {
        // Create the admin user manually if the INSERT didn't work
        echo "<p>Creating admin user...</p>\n";
        $adminPassword = password_hash('Admin123#', PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            INSERT INTO users (username, email, password_hash, full_name, role_id, office_id) 
            VALUES ('admin', 'admin@fleet.com', ?, 'System Administrator', 1, 1)
        ");
        $stmt->execute([$adminPassword]);
        echo "<p style='color: green;'>✓ Admin user created successfully!</p>\n";
    } else {
        echo "<p style='color: green;'>✓ Admin user already exists!</p>\n";
    }
    
    // Insert some default data if tables are empty
    echo "<p>Checking for default data...</p>\n";
    
    // Check and insert default vehicle categories
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM vehicle_categories");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $categories = [
            ['Light Vehicle', 'Cars, small vans, pickup trucks'],
            ['Heavy Vehicle', 'Trucks, buses, heavy machinery'],
            ['Motorcycle', 'Motorcycles and scooters'],
            ['Special Vehicle', 'Specialized equipment and vehicles']
        ];
        
        foreach ($categories as $category) {
            $stmt = $pdo->prepare("INSERT INTO vehicle_categories (name, description) VALUES (?, ?)");
            $stmt->execute($category);
        }
        echo "<p style='color: green;'>✓ Default vehicle categories created!</p>\n";
    }
    
    // Check and insert default departments
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM departments");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $departments = [
            ['Transport', 'Vehicle operations and logistics'],
            ['Maintenance', 'Vehicle maintenance and repairs'],
            ['Administration', 'Administrative operations']
        ];
        
        foreach ($departments as $dept) {
            $stmt = $pdo->prepare("INSERT INTO departments (name, description, office_id) VALUES (?, ?, 1)");
            $stmt->execute($dept);
        }
        echo "<p style='color: green;'>✓ Default departments created!</p>\n";
    }
    
    echo "<h3 style='color: green;'>Database setup completed successfully!</h3>\n";
    echo "<h4>Login Credentials:</h4>\n";
    echo "<p><strong>Username:</strong> admin</p>\n";
    echo "<p><strong>Password:</strong> Admin123#</p>\n";
    echo "<p><a href='index.php'>Go to Login Page</a></p>\n";
    
} catch (Exception $e) {
    echo "<h3 style='color: red;'>Setup failed!</h3>\n";
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
    echo "<p>Please check your database configuration in config.php</p>\n";
}
?>