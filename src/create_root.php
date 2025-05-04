<?php
require 'db.php';

try {
    $query = "SELECT id FROM user_management.users WHERE username = 'root'";
    $stmt = query_safe($conn, $query);
    $adminExists = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$adminExists) {
        $username = 'root';
        $email = 'root@example.com';
        $password = 'T9x!rV@5mL#8wQz&Kd3';
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $role_id = 1;
        
        $query = "INSERT INTO user_management.users (username, email, password, role_id) VALUES (?, ?, ?, ?)";
        $params = [$username, $email, $hashed_password, $role_id];
        $stmt = query_safe($conn, $query, $params);
        
        echo "Root user created successfully!<br>";
        echo "Username: root<br>";
        echo "Email: root@example.com<br>";
        echo "Password: T9x!rV@5mL#8wQz&Kd3<br>";
        echo "<a href='login.php'>Go to login page</a>";
    } else {
        echo "Root user already exists.<br>";
        echo "<a href='login.php'>Go to login page</a>";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>