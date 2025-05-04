<?php
ob_start();

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require 'db.php';

if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';
$debug_info = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = "Username and password are required";
    } else {
        $query = "SELECT id, password, role_id FROM user_management.users WHERE username = :username";
        try {
            $stmt = query_safe($conn, $query, ['username' => $username]);
            
            $debug_info .= "Query executed successfully.<br>";
            
            if ($stmt->rowCount() === 1) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                $debug_info .= "User found in database. ID: " . $user['id'] . ", Role ID: " . $user['role_id'] . "<br>";
                
                if (password_verify($password, $user['password'])) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['role_id'] = $user['role_id'];
                    
                    $debug_info .= "Password verified. Session variables set.<br>";
                    $debug_info .= "SESSION['user_id']: " . $_SESSION['user_id'] . "<br>";
                    $debug_info .= "SESSION['role_id']: " . $_SESSION['role_id'] . "<br>";
                    
                    header("Location: dashboard.php");
                    exit;
                } else {
                    $error = "Invalid username or password";
                    $debug_info .= "Password verification failed.<br>";
                }
            } else {
                $error = "Invalid username or password";
                $debug_info .= "User not found in database.<br>";
            }
        } catch (PDOException $e) {
            $error = "Login error";
            $debug_info .= "Database error: " . $e->getMessage() . "<br>";
        }
    }
}

$deleted = isset($_GET['deleted']) && $_GET['deleted'] === 'true';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            color: #333;
        }

        .login-container {
            background: #fff;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        .login-container h2 {
            margin-bottom: 20px;
            font-size: 28px;
            font-weight: 600;
            color: #444;
        }

        .form-group {
            margin-bottom: 20px;
            text-align: left;
        }

        .login-container label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #555;
            font-size: 14px;
        }

        .login-container input[type="text"],
        .login-container input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s ease;
            box-sizing: border-box;
        }

        .login-container input[type="text"]:focus,
        .login-container input[type="password"]:focus {
            border-color: #667eea;
            outline: none;
            box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.2);
        }

        .login-container button {
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

        .login-container button:hover {
            background: #5a6fd1;
        }

        .register-link {
            margin-top: 20px;
            font-size: 14px;
            color: #555;
        }

        .register-link a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }

        .register-link a:hover {
            text-decoration: underline;
        }

        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-size: 14px;
            text-align: left;
        }

        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .debug {
            background-color: #f8f9fa;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            font-family: monospace;
            font-size: 12px;
            text-align: left;
            overflow-x: auto;
        }

        .message {
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

        .guest-login {
            margin-top: 20px;
        }

        .guest-btn {
            width: 100%;
            padding: 12px;
            background: #764ba2;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .guest-btn:hover {
            background: #6a4191;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="brand">
            <div class="brand-logo">CA</div>
            <div class="brand-name">Chat Application</div>
        </div>
        
        <h2>Login</h2>
        
        <?php if (!empty($error)): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if ($deleted): ?>
            <div class="message success">Your account has been successfully deleted.</div>
        <?php endif; ?>
        
        <?php if (!empty($debug_info) && (isset($_GET['debug']) && $_GET['debug'] === 'true')): ?>
            <div class="debug">
                <strong>Debug Information:</strong><br>
                <?php echo $debug_info; ?>
            </div>
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
            
            <button type="submit">Login</button>
        </form>

        <!-- <div class="guest-login">
            <form method="post" action="dashboard.php">
                <input type="hidden" name="guest_login" value="true">
                <button type="submit" class="guest-btn">Login as Guest</button>
            </form>
        </div> -->
        
        <div class="register-link">
            <p>Don't have an account? <a href="register.php">Register here</a></p>
        </div>
    </div>
    <script>
        function hideMessages() {
            const messages = document.querySelectorAll('.message');
            if (messages.length > 0) {
                setTimeout(() => {
                    messages.forEach(message => {
                        message.style.transition = 'opacity 0.5s ease';
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
<?php
ob_end_flush();
?>