<?php
// Database connection
$host = 'db';
$dbname = 'user_management';
$user = 'user';
$pass = 'password';

try {
    $conn = new PDO("pgsql:host=$host;dbname=$dbname", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if messages table has is_read column, if not add it
    $check_column = $conn->query("SELECT column_name 
                                FROM information_schema.columns 
                                WHERE table_name = 'messages' 
                                AND column_name = 'is_read'");
    
    if ($check_column->rowCount() == 0) {
        $conn->exec("ALTER TABLE user_management.messages ADD COLUMN is_read BOOLEAN DEFAULT FALSE");
    }
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

if (!function_exists('query_safe')) {
    function query_safe($conn, $query, $params = []) {
        try {
            $stmt = $conn->prepare($query);
            
            // Modified parameter binding to account for 1-based index
            foreach ($params as $key => $value) {
                $param = is_int($key) ? $key + 1 : $key; // Adjust for 1-based index if using positional params
                if (is_int($value)) {
                    $stmt->bindValue($param, $value, PDO::PARAM_INT);
                } else {
                    $stmt->bindValue($param, $value);
                }
            }
            
            $stmt->execute();
            return $stmt;
        } catch (PDOException $e) {
            // Log the full error for debugging
            error_log("Database error in query: $query");
            error_log("Parameters: " . print_r($params, true));
            error_log("Error message: " . $e->getMessage());
            
            // Return false or throw the exception based on your needs
            throw $e;
        }
    }
}
?>