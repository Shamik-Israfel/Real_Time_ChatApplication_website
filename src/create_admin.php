<?php
require 'db.php';

try {
    $query = "SELECT id FROM user_management.users WHERE username = 'admin'";
    $stmt = query_safe($conn, $query);
    $adminExists = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$adminExists) {
        $username = 'admin';
        $email = 'admin@example.com';
        $password = 'admin123';
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $role_id = 1;
        
        $query = "INSERT INTO user_management.users (username, email, password, role_id) VALUES (?, ?, ?, ?)";
        $params = [$username, $email, $hashed_password, $role_id];
        $stmt = query_safe($conn, $query, $params);
        
        echo "Admin user created successfully!<br>";
        echo "Username: admin<br>";
        echo "Email: admin@example.com<br>";
        echo "Password: admin123<br>";
        echo "<a href='login.php'>Go to login page</a>";
    } else {
        echo "Admin user already exists.<br>";
        echo "<a href='login.php'>Go to login page</a>";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>