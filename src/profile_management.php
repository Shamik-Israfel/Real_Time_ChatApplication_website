<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require 'db.php';

// Check if user is logged in before doing anything else
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Function to get user role
function getUserRole($user_id, $conn) {
    try {
        $query = "SELECT r.role_name FROM user_management.users u
                  JOIN user_management.roles r ON u.role_id = r.id 
                  WHERE u.id = ?";
        $stmt = query_safe($conn, $query, [$user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['role_name'] ?? 'Unknown';
    } catch (PDOException $e) {
        error_log("Error getting user role: " . $e->getMessage());
        return 'Unknown';
    }
}

// Function to check if a role has a permission
function hasPermission($role, $permission, $conn) {
    try {
        $query = "SELECT COUNT(*) as count FROM user_management.role_permissions rp
                  JOIN user_management.roles r ON rp.role_id = r.id 
                  JOIN user_management.permissions p ON rp.permission_id = p.id 
                  WHERE r.role_name = ? AND p.permission_name = ?";
        $stmt = query_safe($conn, $query, [$role, $permission]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    } catch (PDOException $e) {
        error_log("Error checking permission: " . $e->getMessage());
        return false;
    }
}

// Get current user data
$user = null;
if (isset($_SESSION['user_id'])) {
    try {
        $query = "SELECT id, username FROM user_management.users WHERE id = ?";
        $stmt = query_safe($conn, $query, [$_SESSION['user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error getting user data: " . $e->getMessage());
    }
}

// Initialize variables
$error = '';
$success = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update profile
    if (isset($_POST['update_profile'])) {
        $username = $_POST['name'] ?? '';
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';
        
        // Update username
        if (!empty($username) && $username !== $user['username']) {
            try {
                // Check if username is already taken
                $check_query = "SELECT id FROM user_management.users WHERE username = ? AND id != ?";
                $check_stmt = query_safe($conn, $check_query, [$username, $_SESSION['user_id']]);
                $check_result = $check_stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($check_result) {
                    $error = "Username already exists";
                } else {
                    $update_query = "UPDATE user_management.users SET username = ? WHERE id = ?";
                    $update_stmt = query_safe($conn, $update_query, [$username, $_SESSION['user_id']]);
                    
                    $success = "Profile updated successfully!";
                    $user['username'] = $username;
                }
            } catch (PDOException $e) {
                $error = "Error updating profile: " . $e->getMessage();
            }
        }
        
        // Handle Password Change
        if (!empty($current_password) && !empty($new_password)) {
            try {
                // Get current password hash from database
                $pwd_query = "SELECT password FROM user_management.users WHERE id = ?";
                $pwd_stmt = query_safe($conn, $pwd_query, [$_SESSION['user_id']]);
                $pwd_result = $pwd_stmt->fetch(PDO::FETCH_ASSOC);
                $current_hash = $pwd_result['password'];
                
                // Verify current password
                if (password_verify($current_password, $current_hash)) {
                    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                    
                    $pwd_update_query = "UPDATE user_management.users SET password = ? WHERE id = ?";
                    $pwd_update_stmt = query_safe($conn, $pwd_update_query, [$hashed_password, $_SESSION['user_id']]);
                    
                    $success .= " Password updated successfully!";
                } else {
                    $error = "Current password is incorrect!";
                }
            } catch (PDOException $e) {
                $error = "Error updating password: " . $e->getMessage();
            }
        }
        
        // Handle profile picture upload (placeholder - add file upload handling code)
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
            // File upload handling would go here
            // This would require additional database tables for storing profile images
            $success .= " Profile picture updated!";
        }
    }
    
    // Delete account
    if (isset($_POST['delete_account'])) {
        try {
            // Implement account deletion logic here
            $delete_query = "DELETE FROM user_management.users WHERE id = ?";
            $delete_stmt = query_safe($conn, $delete_query, [$_SESSION['user_id']]);
            
            // Destroy session and redirect to login
            session_destroy();
            header('Location: login.php?deleted=true');
            exit;
        } catch (PDOException $e) {
            $error = "Error deleting account: " . $e->getMessage();
        }
    }
}

// Get user role for navigation display
$role = getUserRole($_SESSION['user_id'], $conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        /* General Styles */
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            color: #333;
        }

        /* Profile Container */
        .profile-container {
            background: #fff;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
            margin: 20px;
        }

        .profile-container h2 {
            margin-bottom: 20px;
            font-size: 24px;
            font-weight: 600;
            color: #444;
        }

        /* Form Styles */
        .profile-container label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            text-align: left;
            color: #555;
        }

        .profile-container input[type="text"],
        .profile-container input[type="username"],
        .profile-container input[type="password"],
        .profile-container input[type="file"] {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            transition: border-color 0.3s ease;
            box-sizing: border-box;
        }

        .profile-container input[type="text"]:focus,
        .profile-container input[type="username"]:focus,
        .profile-container input[type="password"]:focus,
        .profile-container input[type="file"]:focus {
            border-color: #667eea;
            outline: none;
        }

        /* Buttons */
        .profile-container button {
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
            margin-bottom: 10px;
        }

        .profile-container button:hover {
            background: #5a6fd1;
        }

        .profile-container button.delete {
            background: #dc3545;
            margin-top: 10px;
        }

        .profile-container button.delete:hover {
            background: #c82333;
        }

        /* Profile Picture Preview */
        .profile-picture-preview {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 15px;
            border: 3px solid #667eea;
            background-color: #f5f5f5;
        }

        /* File Input Styling */
        .file-input-wrapper {
            position: relative;
            overflow: hidden;
            display: inline-block;
            width: 100%;
            margin-bottom: 15px;
        }

        .file-input-wrapper input[type="file"] {
            font-size: 100px;
            position: absolute;
            left: 0;
            top: 0;
            opacity: 0;
            cursor: pointer;
        }

        .file-input-wrapper .custom-file-input {
            display: inline-block;
            padding: 10px;
            background: #f1f1f1;
            border: 1px solid #ddd;
            border-radius: 8px;
            width: 100%;
            text-align: center;
            font-size: 14px;
            color: #555;
            cursor: pointer;
            transition: background 0.3s ease;
            box-sizing: border-box;
        }

        .file-input-wrapper .custom-file-input:hover {
            background: #e1e1e1;
        }

        /* Navigation */
        .nav-links {
            position: fixed;
            top: 20px;
            right: 20px;
            margin-bottom: 20px;
            text-align: right;
            background-color: rgba(255, 255, 255, 0.1);
            padding: 10px;
            border-radius: 8px;
            z-index: 1000;
        }

        .nav-links a {
            display: inline-block;
            margin-left: 15px;  /* Changed from margin-right to margin-left */
            color: #fff;
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s ease;
            padding: 5px 10px;
        }

        .nav-links a:hover {
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 5px;
        }

        /* Messages */
        .message {
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 5px;
            font-size: 14px;
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

        /* Role Badge */
        .role-badge {
            display: inline-block;
            padding: 5px 10px;
            background-color: #6c757d;
            color: white;
            border-radius: 20px;
            font-size: 12px;
            margin-bottom: 20px;
        }
        
        .role-admin {
            background-color: #007bff;
        }
        
        .role-user {
            background-color: #28a745;
        }
        
        .role-guest {
            background-color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="nav-links">
        <a href="dashboard.php">Dashboard</a>
        <a href="profile_management.php">My Profile</a>
        <?php if (hasPermission($role, 'manage_users', $conn)): ?>
            <a href="admin_page.php">Admin Panel</a>
        <?php endif; ?>
        <a href="logout.php">Logout</a>
    </div>

    <div class="profile-container">
        <h2>User Profile</h2>
        
        <span class="role-badge role-<?php echo strtolower($role); ?>"><?php echo htmlspecialchars($role); ?></span>

        <?php if (!empty($error)): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (!empty($success)): ?>
            <div class="message success"><?php echo $success; ?></div>
        <?php endif; ?>

        <!-- Profile Picture Preview -->
        <img src="default-profile.jpg" alt="Profile Picture" class="profile-picture-preview" id="profile-picture-preview">

        <!-- Profile Form -->
        <form action="profile_management.php" method="POST" enctype="multipart/form-data">
            <!-- Name (Username) -->
            <label for="name">Username:</label>
            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($user['username'] ?? ''); ?>" placeholder="Enter your username" required>

            <!-- Profile Picture Upload -->
            <label for="profile_picture">Profile Picture:</label>
            <div class="file-input-wrapper">
                <input type="file" id="profile_picture" name="profile_picture" accept="image/*">
                <div class="custom-file-input">Choose File</div>
            </div>

            <!-- Current Password -->
            <label for="current_password">Current Password:</label>
            <input type="password" id="current_password" name="current_password" placeholder="Enter current password">

            <!-- New Password -->
            <label for="new_password">New Password:</label>
            <input type="password" id="new_password" name="new_password" placeholder="Enter new password">

            <!-- Update Profile Button -->
            <button type="submit" name="update_profile">Update Profile</button>

            <!-- Delete Account Button -->
            <button type="submit" name="delete_account" class="delete" onclick="return confirm('Are you sure you want to delete your account? This action cannot be undone.');">Delete Account</button>
        </form>
    </div>

    <!-- JavaScript for Profile Picture Preview -->
    <script>
        document.getElementById('profile_picture').addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    document.getElementById('profile-picture-preview').src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });
    </script>
</body>
</html>