<?php
session_start();
require 'db.php';

if (isset($_POST['guest_login'])) {
    $_SESSION['user_id'] = 'guest';
    $_SESSION['role_id'] = 3; // Assuming 3 is your guest role ID
}

// Regular authentication check
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Function to get user role
function getUserRole($user_id, $conn) {
    $query = "SELECT user_management.roles.role_name FROM user_management.users 
              JOIN user_management.roles ON user_management.users.role_id = user_management.roles.id 
              WHERE user_management.users.id = :user_id";
    try {
        $stmt = query_safe($conn, $query, ['user_id' => $user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['role_name'] ?? null;
    } catch (PDOException $e) {
        return null;
    }
}

// Function to check if a role has a permission
function hasPermission($role, $permission, $conn) {
    $query = "SELECT COUNT(*) as count FROM user_management.role_permissions 
              JOIN user_management.roles ON user_management.role_permissions.role_id = user_management.roles.id 
              JOIN user_management.permissions ON user_management.role_permissions.permission_id = user_management.permissions.id 
              WHERE user_management.roles.role_name = :role AND user_management.permissions.permission_name = :permission";
    try {
        $stmt = query_safe($conn, $query, ['role' => $role, 'permission' => $permission]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    } catch (PDOException $e) {
        return false;
    }
}

// Get user information
$role = getUserRole($_SESSION['user_id'], $conn);

// Get username
$query = "SELECT username FROM user_management.users WHERE id = :user_id";
try {
    $stmt = query_safe($conn, $query, ['user_id' => $_SESSION['user_id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $username = $result['username'] ?? 'User';
} catch (PDOException $e) {
    $username = 'User';
}

// Count total users (for admin)
$total_users = 0;
if ($role === 'Admin') {
    try {
        $count_query = "SELECT COUNT(*) as count FROM user_management.users";
        $stmt = query_safe($conn, $count_query, []);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $total_users = $result['count'];
    } catch (PDOException $e) {
        $total_users = 0;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        /* General Styles */
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 0;
            min-height: 100vh;
            color: #333;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Header Styles */
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            margin-bottom: 30px;
        }

        .header h1 {
            color: #fff;
            margin: 0;
            font-size: 28px;
            font-weight: 600;
        }

        .user-info {
            display: flex;
            align-items: center;
        }

        .user-info img {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            margin-right: 10px;
            object-fit: cover;
            border: 2px solid #fff;
        }

        .user-info .details {
            color: #fff;
        }

        .user-info .username {
            font-weight: 500;
            font-size: 16px;
        }

        .user-info .role {
            font-size: 12px;
            opacity: 0.8;
        }

        /* Navigation */
        .nav {
            display: flex;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 5px;
            margin-bottom: 30px;
            justify-content: flex-start;
        }

        .nav a {
            color: #fff;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 8px;
            transition: background-color 0.3s;
            font-weight: 500;
            margin-right: 10px;
        }

        .nav a:hover {
            background-color: rgba(255, 255, 255, 0.2);
        }

        .nav a.active {
            background-color: rgba(255, 255, 255, 0.25);
        }
        
        /* Dashboard Cards */
        .cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .card {
            background-color: #fff;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .card h3 {
            font-size: 18px;
            margin-top: 0;
            margin-bottom: 15px;
            color: #444;
        }

        .card p {
            color: #666;
            margin-bottom: 15px;
        }

        .card .btn {
            display: inline-block;
            background-color: #667eea;
            color: #fff;
            padding: 8px 15px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            font-size: 14px;
            transition: background-color 0.3s;
        }

        .card .btn:hover {
            background-color: #5a6fd1;
        }

        /* Admin Section */
        .admin-section {
            background-color: #fff;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .admin-section h2 {
            color: #444;
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 20px;
        }

        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
        }

        .stat-card {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
        }

        .stat-card .number {
            font-size: 24px;
            font-weight: 600;
            color: #667eea;
            margin-bottom: 5px;
        }

        .stat-card .label {
            color: #666;
            font-size: 14px;
        }

        /* Role Badges */
        .role-badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 12px;
            color: white;
            margin-left: 10px;
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

        /* Responsive Styles */
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                text-align: center;
            }
            
            .user-info {
                margin-top: 15px;
            }
            
            .nav {
                justify-content: flex-start;
                overflow-x: auto;
            }
            
            .nav a {
                margin: 5px 10px 5px 0;
            }
        }
        .clickable {
            cursor: pointer;
            transition: transform 0.2s;
        }

        .clickable:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            z-index: 1000;
        }

        .modal-content {
            background-color: #fff;
            margin: 5% auto;
            padding: 20px;
            border-radius: 10px;
            width: 80%;
            max-width: 800px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }

        .close {
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .users-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        .users-table th, .users-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .users-table th {
            background-color: #f8f9fa;
            font-weight: 600;
        }

        .users-table tr:hover {
            background-color: #f5f5f5;
        }

        .data-viz-section {
            margin-top: 20px;
            text-align: right;
        }

        .viz-btn {
            background-color: #764ba2;
            padding: 10px 20px;
            border-radius: 8px;
            color: white;
            text-decoration: none;
            transition: background-color 0.3s;
        }

        .viz-btn:hover {
            background-color: #633b8c;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><?php echo ($role === 'Admin') ? 'Admin Dashboard' : 'User Dashboard'; ?></h1>
            <div class="user-info">
                <img src="default-profile.jpg" alt="Profile Picture">
                <div class="details">
                    <div class="username"><?php echo htmlspecialchars($username); ?></div>
                    <div class="role">
                        <?php echo htmlspecialchars($role); ?>
                        <span class="role-badge role-<?php echo strtolower(htmlspecialchars($role)); ?>"><?php echo htmlspecialchars($role); ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div class="nav">
            <a href="dashboard.php" class="active">Dashboard</a>
            <a href="chat.php">Chat</a>
            <a href="profile_management.php">My Profile</a>
            <?php if ($role === 'Admin'): ?>
                <a href="admin_page.php">Admin Panel</a>
            <?php endif; ?>
            <a href="group_info.php">Group Info</a>
            <a href="logout.php">Logout</a>
        </div>

        <div class="cards">
            <div class="card">
                <h3>Profile Information</h3>
                <p>Update your profile information, change your password, or update your profile picture.</p>
                <a href="profile_management.php" class="btn">Manage Profile</a>
            </div>

            <?php if ($role === 'Admin'): ?>
            <div class="card">
                <h3>User Management</h3>
                <p>Manage users, assign roles, and update permissions.</p>
                <a href="admin_page.php" class="btn">Manage Users</a>
            </div>
            <?php endif; ?>

            <div class="card">
                <h3>Account Security</h3>
                <p>Manage your account security settings and password.</p>
                <a href="profile_management.php" class="btn">Security Settings</a>
            </div>
        </div>

        <?php if ($role === 'Admin'): ?>
        <div class="admin-section">
            <h2>Admin Dashboard</h2>
            <div class="stats">
                <div class="stat-card">
                <div class="number"><?php echo $total_users; ?></div>
                <div class="label">Total Users</div>
                </div>
                <div class="stat-card">
                    <div class="number">3</div>
                    <div class="label">User Roles</div>
                </div>
                <div class="stat-card">
                    <div class="number">3</div>
                    <div class="label">Permissions</div>
                </div>
            </div>
            <div class="data-viz-section">
                <a href="data_visualization.php" class="btn viz-btn">Data Visualization</a>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <div id="usersModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal()">&times;</span>
        <h2>All Users</h2>
        <table class="users-table">
            <thead>
                <tr>
                    <th>Username</th>
                    <th>Role</th>
                    <th>Created Date</th>
                </tr>
            </thead>
            <tbody id="usersTableBody">
            </tbody>
        </table>
    </div>
</div>
    <script>
        function showUsersTable() {
            fetch('get_users.php')
                .then(response => response.json())
                .then(users => {
                    const tableBody = document.getElementById('usersTableBody');
                    tableBody.innerHTML = '';
                    users.forEach(user => {
                        tableBody.innerHTML += `
                            <tr>
                                <td>${user.username}</td>
                                <td>${user.role}</td>
                                <td>${user.created_at}</td>
                            </tr>
                        `;
                    });
                    document.getElementById('usersModal').style.display = 'block';
                });
        }

        function closeModal() {
            document.getElementById('usersModal').style.display = 'none';
        }

        // Close modal when clicking outside of it
        window.onclick = function(event) {
            const modal = document.getElementById('usersModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }
    </script>
</body>
</html>