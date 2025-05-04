<?php
session_start();

require_once 'db.php';

$loggedIn = isset($_SESSION['loggedIn']) && $_SESSION['loggedIn'] === true;

if (isset($_POST['login'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if ($username === 'root' && $password === 'T9x!rV@5mL#8wQz&Kd3') {
        $_SESSION['loggedIn'] = true;
        $loggedIn = true;
    } else {
        $loginError = "Invalid username or password";
    }
}

if (isset($_POST['logout'])) {
    $_SESSION['loggedIn'] = false;
    $loggedIn = false;
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

$projectFiles = [
    'index.php' => 'index.php',
    'login.php' => 'login.php',
    'register.php' => 'register.php',
    'dashboard.php' => 'dashboard.php',
    'admin_page.php' => 'admin_page.php',
    'profile_management.php' => 'profile_management.php',
    'data_visualization.php' => 'data_visualization.php',
    'group_info.php' => 'group_info.php',
    'chat.php' => 'chat.php'
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $loggedIn ? 'Project Dashboard' : 'Login'; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 0;
            color: #333;
            min-height: 100vh;
        }

        .login-container {
            background: #fff;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
            margin: 50px auto;
        }

        .container {
            background: #fff;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 1200px;
            margin: 30px auto;
        }

        h2 {
            margin-bottom: 20px;
            font-size: 28px;
            font-weight: 600;
            color: #444;
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #555;
            font-size: 14px;
        }

        input[type="text"],
        input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s ease;
            box-sizing: border-box;
        }

        input[type="text"]:focus,
        input[type="password"]:focus {
            border-color: #667eea;
            outline: none;
            box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.2);
        }

        button {
            width: 100%;
            padding: 12px;
            background: #667eea;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.3s ease;
            margin-top: 10px;
        }

        button:hover {
            background: #5a6fd1;
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-size: 14px;
            text-align: left;
            opacity: 1;
            transition: opacity 0.5s ease;
        }

        .brand {
            margin-bottom: 30px;
        }

        .brand-logo {
            width: 60px;
            height: 60px;
            background-color: #667eea;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            color: white;
            font-size: 24px;
            font-weight: bold;
        }

        .brand-name {
            font-size: 20px;
            font-weight: 600;
            color: #444;
        }

        .nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }

        .nav h1 {
            margin: 0;
            font-size: 24px;
            color: #444;
        }

        .links {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .link-item {
            background: #f9f9fa;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .link-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .link-item a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            font-size: 16px;
        }

        .link-item a:hover {
            text-decoration: underline;
        }

        .section-header {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            font-size: 18px;
            font-weight: 500;
            color: #555;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            margin-bottom: 40px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            border-radius: 8px;
            overflow: hidden;
        }

        th, td {
            padding: 12px 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background-color: #f7f7f9;
            font-weight: 500;
            font-size: 14px;
            color: #555;
        }

        td {
            font-size: 14px;
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr:hover {
            background-color: #f9f9fb;
        }

        h3 {
            font-size: 18px;
            font-weight: 500;
            color: #444;
            margin: 30px 0 15px 0;
        }

        @media (max-width: 768px) {
            .container {
                padding: 20px;
                margin: 15px;
                border-radius: 10px;
            }
            
            .links {
                grid-template-columns: 1fr 1fr;
            }
        }

        @media (max-width: 480px) {
            .links {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php if (!$loggedIn): ?>
        <div class="login-container">
            <div class="brand">
                <div class="brand-logo">PD</div>
                <div class="brand-name">Project Dashboard</div>
            </div>
            
            <h2>Login</h2>
            
            <?php if (isset($loginError)): ?>
                <div class="error"><?php echo $loginError; ?></div>
            <?php endif; ?>
            
            <form method="post" action="">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" name="login">Login</button>
            </form>
        </div>
    <?php else: ?>
        <div class="container">
            <div class="nav">
                <h1>Project Dashboard</h1>
            </div>
            
            <h2>Project Pages</h2>
            
            <!-- Display all files in the src directory -->
            <div class="links">
                <?php
                // Display all src files
                foreach ($projectFiles as $path => $title) {
                    if ($path != basename($_SERVER['PHP_SELF'])) {
                        echo '<div class="link-item">';
                        echo '<a href="' . htmlspecialchars($path) . '" title="' . htmlspecialchars($title) . '">' . htmlspecialchars($title) . '</a>';
                        echo '</div>';
                    }
                }
                ?>
            </div>
            
            <h2>Database Contents</h2>
            <?php
            try {
                $tableQuery = "SELECT table_name FROM information_schema.tables WHERE table_schema = 'user_management'";
                $tableStmt = query_safe($conn, $tableQuery);
                $tables = $tableStmt->fetchAll(PDO::FETCH_COLUMN);
                
                if (count($tables) > 0) {
                    foreach ($tables as $table) {
                        echo '<h3>Table: user_management.' . htmlspecialchars($table) . '</h3>';
                        
                        $columnQuery = "SELECT column_name, data_type FROM information_schema.columns 
                                        WHERE table_schema = 'user_management' AND table_name = :table 
                                        ORDER BY ordinal_position";
                        $columnStmt = query_safe($conn, $columnQuery, [':table' => $table]);
                        $columns = $columnStmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        $dataQuery = "SELECT * FROM user_management." . $table;
                        $dataStmt = query_safe($conn, $dataQuery);
                        $rows = $dataStmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        if (count($rows) > 0) {
                            echo '<table>';
                            echo '<thead><tr>';
                            foreach ($columns as $column) {
                                echo '<th>' . htmlspecialchars($column['column_name']) . '</th>';
                            }
                            echo '</tr></thead>';
                            
                            echo '<tbody>';
                            foreach ($rows as $row) {
                                echo '<tr>';
                                foreach ($row as $key => $value) {
                                    echo '<td>' . htmlspecialchars($value ?? 'NULL') . '</td>';
                                }
                                echo '</tr>';
                            }
                            echo '</tbody>';
                            echo '</table>';
                        } else {
                            echo '<p>No data in this table.</p>';
                        }
                    }
                } else {
                    echo '<p>No tables found in the user_management schema.</p>';
                }
            } catch (PDOException $e) {
                echo '<div class="error">Error accessing database: ' . htmlspecialchars($e->getMessage()) . '</div>';
            }
            ?>
        </div>
    <?php endif; ?>
    
    <script>
        function hideMessages() {
            const messages = document.querySelectorAll('.error');
            if (messages.length > 0) {
                setTimeout(() => {
                    messages.forEach(message => {
                        message.style.opacity = '0';
                        setTimeout(() => {
                            message.style.display = 'none';
                        }, 500);
                    });
                }, 5000);
            }
        }

        document.addEventListener('DOMContentLoaded', hideMessages);
    </script>
</body>
</html>