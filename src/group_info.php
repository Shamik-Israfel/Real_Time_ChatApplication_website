<?php
session_start();
require 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Group Info - The Sapphire Eye</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
            color: #333;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        h1, h2 {
            text-align: center;
            color: #444;
            margin-bottom: 30px;
        }

        .table-container {
            overflow-x: auto;
            margin: 30px 0;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
            background: white;
        }

        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #f8f9fa;
            font-weight: 600;
        }

        .links-section {
            margin: 30px 0;
        }

        .links-section h3 {
            color: #444;
            margin: 20px 0 10px;
        }

        .links-section a {
            color: #667eea;
            text-decoration: none;
            display: block;
            margin: 5px 0;
        }

        .links-section a:hover {
            text-decoration: underline;
        }

        .back-btn {
            display: inline-block;
            padding: 10px 20px;
            background-color: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .back-btn:hover {
            background-color: #5a6fd1;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="dashboard.php" class="back-btn">Back to Dashboard</a>
        
        <h1>Real-Time Chat Application</h1>
        <h2>The Sapphire Eye</h2>

        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>ID</th>
                        <th>Email</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Sanjida Akter Shorna</td>
                        <td>2222332</td>
                        <td>2222332@iub.edu.bd</td>
                    </tr>
                    <tr>
                        <td>Nafizul islam Akash</td>
                        <td>1910304</td>
                        <td>1910304@iub.edu.bd</td>
                    </tr>
                    <tr>
                        <td>Shamik Mondal </td>
                        <td>2221145</td>
                        <td>2221145@iub.edu.bd</td>
                    </tr>
                    <tr>
                        <td>Sadman Sakib</td>
                        <td>2221929</td>
                        <td>2221929@iub.edu.bd</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="links-section">
            <h3>Notion Links</h3>
            <a href="https://www.notion.so/Sanjida-s-Individual-report-1b297cdda1b8802fb8cad409287d9fe6?pvs=4" target="_blank">Sanjida Akter Notion</a>
            <a href="https://www.notion.so/NAFIZUL-ISLAM-AKASH-7f01d6cc378f418ca61cf3732b5ccca3?pvs=3&qid=" target="_blank">Nafizul Islam Akash Notion</a>
            <a href="https://www.notion.so/Shamik-Mondal-WebApp-FeedBack-1ab6934013578010b3e0f64571f93cd8" target="_blank">Shamik Mondal Notion</a>
            <a href="https://www.notion.so/Sadman-Sakib-2221929-1b9b91d2569180629e2deb347e75296f" target="_blank">Sadman Sakib Notion</a>

            <h3>Github Repository</h3>
            <a href="https://github.com/Shamik-Israfel/The-Sapphire-Eye" target="_blank">Project Repository</a>

            <h3>Git Links</h3>
            <a href="https://github.com/SanjidaShorna" target="_blank">Sanjida</a>
            <a href="https://github.com/nafizulakash" target="_blank">Akash</a>
            <a href="https://github.com/Shamik-Israfel" target="_blank">Shamik</a>
            <a href="https://github.com/sakibsadman1" target="_blank">Sadman</a>
        </div>
    </div>
</body>
</html>