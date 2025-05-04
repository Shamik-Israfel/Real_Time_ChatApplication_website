<?php
require 'access_control.php';
requirePermission('manage_users');

// Process form submission for editing user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_user') {
    $user_id = $_POST['user_id'] ?? '';
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $role_id = $_POST['role_id'] ?? '';
    
    // Validate inputs
    if (empty($user_id) || empty($username) || empty($email) || empty($role_id)) {
        $error = "All fields are required";
    } else {
        try {
            // Update user in database using prepared statement
            $query = "UPDATE user_management.users SET username = ?, email = ?, role_id = ? WHERE id = ?";
            $params = [$username, $email, $role_id, $user_id];
            $stmt = query_safe($conn, $query, $params);
            $success = "User updated successfully!";
        } catch (PDOException $e) {
            $error = "Failed to update user: " . $e->getMessage();
        }
    }
}

function getRoles($conn) {
    try {
        $query = "SELECT id, role_name FROM user_management.roles";
        $stmt = query_safe($conn, $query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching roles: " . $e->getMessage());
        return [];
    }
}

function getUsers($conn) {
    try {
        // Updated query to include the email field from the database
        $query = "SELECT u.id, u.username, u.email, u.role_id, r.role_name 
                FROM user_management.users u
                JOIN user_management.roles r ON u.role_id = r.id";
        $stmt = query_safe($conn, $query);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching users: " . $e->getMessage());
        return [];
    }
}

$users = getUsers($conn);
$roles = getRoles($conn);

echo "Welcome, Admin! You have access to manage users.";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="styles.css">
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            display: flex;
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
        }
        .sidebar {
            width: 250px;
            background: #333;
            color: white;
            padding: 20px;
            height: 100vh;
            position: fixed;
        }
        .sidebar h2 {
            text-align: center;
        }
        .sidebar ul {
            list-style: none;
            padding: 0;
        }
        .sidebar ul li {
            padding: 10px;
            margin: 10px 0;
            background: #444;
            cursor: pointer;
            border-radius: 5px;
            transition: background-color 0.3s;
        }
        .sidebar ul li:hover {
            background: #555;
        }
        .sidebar ul li:first-child {
            background: #444;
        }
        .sidebar ul li:first-child:hover {
            background: #555;
        }
        .sidebar ul li a {
            color: white;
            text-decoration: none;
            display: block;
        }
        .sidebar ul li a i {
            margin-right: 10px;
        }
        .main-content {
            margin-left: 270px;
            padding: 20px;
            width: calc(100% - 270px);
        }
        header {
            background: #007BFF;
            color: white;
            padding: 15px;
            text-align: center;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
        }
        table, th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: left;
        }
        th {
            background: #007BFF;
            color: white;
        }
        button {
            padding: 5px 10px;
            background: #28a745;
            color: white;
            border: none;
            cursor: pointer;
        }
        button:hover {
            background: #218838;
        }
        section {
            background: white;
            padding: 20px;
            margin: 20px 0;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .edit-btn {
            background: #ffc107;
            color: black;
        }
        .edit-btn:hover {
            background: #e0a800;
        }
        .popup {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: white;
            padding: 20px;
            box-shadow: 0 0 10px rgba(0,0,0,0.2);
            z-index: 1000;
            width: 300px;
        }
        .popup input, .popup select {
            display: block;
            margin-bottom: 10px;
            padding: 5px;
            width: 100%;
        }
        .popup .close-btn {
            background: #dc3545;
            margin-top: 10px;
        }
        .overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }
        .message {
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 8px;
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
    </style>
</head>
<body>
    <div class="overlay" id="overlay"></div>
    <div class="popup" id="popup">
        <h2>Edit User</h2>
        <form method="post" action="">
            <input type="hidden" id="user_id" name="user_id">
            <input type="hidden" name="action" value="edit_user">
            <input type="text" id="username" name="username" placeholder="Username">
            <input type="email" id="email" name="email" placeholder="Email">
            <select id="role_id" name="role_id">
                <?php foreach ($roles as $role): ?>
                    <option value="<?php echo $role['id']; ?>"><?php echo htmlspecialchars($role['role_name']); ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit">Save</button>
            <button type="button" class="close-btn" onclick="closePopup()">Cancel</button>
        </form>
    </div>

    <div class="sidebar">
        <h2>Admin Panel</h2>
        <ul>
            <li><a href="dashboard.php"><i class="fas fa-home"></i> Back to Dashboard</a></li>
            <li><a href="chat.php"><i class="fas fa-comments"></i> Chat</a></li>
            <li><a href="#users"><i class="fas fa-users"></i> Users</a></li>
            <li><a href="#account"><i class="fas fa-user-cog"></i> Account Management</a></li>
            <li><a href="#activity"><i class="fas fa-chart-line"></i> Activity Monitoring</a></li>
            <li><a href="#reports"><i class="fas fa-file-alt"></i> Reports</a></li>
        </ul>
    </div>
    <div class="main-content">
        <header>
            <h1>Admin Dashboard</h1>
        </header>
        
        <?php if (isset($error)): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if (isset($success)): ?>
            <div class="message success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <section id="account">
            <h2>Account Management</h2>
            <table>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Actions</th>
                </tr>
                <?php if (count($users) > 0): ?>
                    <?php foreach ($users as $user): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['role_name']); ?></td>
                            <td><button class="edit-btn" onclick="openPopup(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars(addslashes($user['username'])); ?>', '<?php echo htmlspecialchars(addslashes($user['email'])); ?>', <?php echo $user['role_id']; ?>)">Edit</button></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5">No users found</td>
                    </tr>
                <?php endif; ?>
            </table>
        </section>
        <section id="activity">
            <h2>Activity Monitoring</h2>
            <table>
                <tr>
                    <th>ID</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Timestamp</th>
                </tr>
                <tr>
                    <td>1</td>
                    <td>John Doe</td>
                    <td>Logged in</td>
                    <td>2025-03-16 12:30:45</td>
                </tr>
                <tr>
                    <td>2</td>
                    <td>Jane Smith</td>
                    <td>Sent a message</td>
                    <td>2025-03-16 12:32:10</td>
                </tr>
                <tr>
                    <td>3</td>
                    <td>John Doe</td>
                    <td>Logged out</td>
                    <td>2025-03-16 12:40:15</td>
                </tr>
            </table>
        </section>
        <section id="reports">
            <h2>Usage Reports</h2>
            <canvas id="usageChart" width="400" height="200"></canvas>
            <script>
                var ctx = document.getElementById('usageChart').getContext('2d');
                var usageChart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: ['Logins', 'Messages Sent', 'Files Uploaded', 'New Users'],
                        datasets: [{
                            label: 'Activity Count',
                            data: [45, 120, 30, 10],
                            backgroundColor: ['#007BFF', '#28a745', '#ffc107', '#dc3545']
                        }]
                    }
                });
            </script>
        </section>
    </div>
    <script>
    function openPopup(userId, username, email, roleId) {
        document.getElementById('user_id').value = userId;
        document.getElementById('username').value = username;
        document.getElementById('email').value = email;
        document.getElementById('role_id').value = roleId;
        document.getElementById('popup').style.display = 'block';
        document.getElementById('overlay').style.display = 'block';
    }
    
    function closePopup() {
        document.getElementById('popup').style.display = 'none';
        document.getElementById('overlay').style.display = 'none';
    }
    </script>
</body>
</html>