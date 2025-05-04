<?php
session_start();
require 'db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Function to get user role using PDO
function getUserRole($user_id, $conn) {
    $query = "SELECT r.role_name FROM user_management.users u
              JOIN user_management.roles r ON u.role_id = r.id 
              WHERE u.id = :user_id";
    
    $stmt = $conn->prepare($query);
    $stmt->execute(['user_id' => $user_id]);
    return $stmt->fetchColumn() ?: null;
}

// Get user role
$role = getUserRole($_SESSION['user_id'], $conn);

// Check if user is admin
if ($role !== 'Admin') {
    header('Location: dashboard.php');
    exit;
}

// Load the CSV file
$csvFile = "RafigCovid_19.csv";
$data = [];
$languageCounts = [];

// Define language keywords (basic approach)
$languageKeywords = [
    "English" => ["the", "and", "you", "is", "in"],
    "Spanish" => ["el", "la", "que", "en", "es"],
    "French" => ["le", "la", "et", "est", "en"]
];

// Keep original colors as requested
$languageColors = [
    "English" => "red",
    "Spanish" => "green",
    "French" => "yellow",
    "Other" => "blue"
];

// Open and read the CSV file
if (($handle = fopen($csvFile, "r")) !== FALSE) {
    $headers = fgetcsv($handle, 1000, ";"); // Read header row

    while (($row = fgetcsv($handle, 1000, ";")) !== FALSE) {
        // Ensure row length matches header count
        if (count($row) !== count($headers)) {
            continue; // Skip invalid rows
        }

        $rowData = array_combine($headers, $row);
        $tweetText = strtolower($rowData["Tweet"] ?? "");

        // Detect language
        $detectedLanguage = "Other";
        foreach ($languageKeywords as $lang => $keywords) {
            foreach ($keywords as $word) {
                if (strpos($tweetText, $word) !== false) {
                    $detectedLanguage = $lang;
                    break 2;
                }
            }
        }

        // Count occurrences
        if (!isset($languageCounts[$detectedLanguage])) {
            $languageCounts[$detectedLanguage] = 0;
        }
        $languageCounts[$detectedLanguage]++;
    }
    fclose($handle);
}

// Prepare data for Chart.js
$labels = json_encode(array_keys($languageCounts));
$dataValues = json_encode(array_values($languageCounts));

// Fix colors array to match the detected languages
$chartColors = [];
foreach (array_keys($languageCounts) as $lang) {
    $chartColors[] = $languageColors[$lang] ?? 'gray';
}
$colors = json_encode($chartColors);

// Get user statistics using PDO
$roleStats = [];
try {
    $query = "SELECT r.role_name, COUNT(u.id) as count 
              FROM user_management.roles r
              LEFT JOIN user_management.users u ON r.id = u.role_id
              GROUP BY r.role_name";
    
    $stmt = query_safe($conn, $query);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $roleStats[$row['role_name']] = $row['count'];
    }
} catch (PDOException $e) {
    // Handle error silently - stats will just be empty
    $roleStats = ['Admin' => 0, 'User' => 0, 'Guest' => 0];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Visualization</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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

        /* Navigation buttons */
        .back-button {
            display: inline-block;
            background-color: rgba(255, 255, 255, 0.2);
            color: #fff;
            padding: 10px 20px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 500;
            transition: background-color 0.3s;
        }

        .back-button:hover {
            background-color: rgba(255, 255, 255, 0.3);
        }

        /* Chart Container */
        .chart-container {
            background-color: #fff;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
        }

        .chart-container h2 {
            color: #444;
            margin-top: 0;
            margin-bottom: 20px;
            font-size: 20px;
        }

        /* Statistics Cards */
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background-color: #fff;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
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

        /* Color indicators for language legend */
        .language-legend {
            display: flex;
            justify-content: center;
            margin-top: 20px;
            flex-wrap: wrap;
        }

        .legend-item {
            display: flex;
            align-items: center;
            margin: 0 15px 10px;
        }

        .color-box {
            width: 15px;
            height: 15px;
            margin-right: 5px;
            border-radius: 3px;
        }

        .english-color { background-color: red; }
        .spanish-color { background-color: green; }
        .french-color { background-color: yellow; }
        .other-color { background-color: blue; }

        /* Responsive Styles */
        @media (max-width: 768px) {
            .header {
                flex-direction: column;
                text-align: center;
            }
            
            .button-container {
                margin-top: 15px;
                text-align: center;
            }
        }

        canvas {
            max-height: 400px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Data Visualization</h1>
            <a href="dashboard.php" class="back-button">Back to Dashboard</a>
        </div>

        <!-- Stats Summary -->
        <div class="stats">
            <div class="stat-card">
                <div class="number"><?php echo array_sum($languageCounts); ?></div>
                <div class="label">Total Tweets</div>
            </div>
            <div class="stat-card">
                <div class="number"><?php echo count($languageCounts); ?></div>
                <div class="label">Languages Detected</div>
            </div>
            <div class="stat-card">
                <div class="number"><?php echo isset($languageCounts['English']) ? $languageCounts['English'] : 0; ?></div>
                <div class="label">English Tweets</div>
            </div>
        </div>
        
        <!-- User Statistics -->
        <div class="chart-container">
            <h2>System User Statistics</h2>
            <div class="stats">
                <div class="stat-card">
                    <div class="number"><?php echo $roleStats['Admin'] ?? 0; ?></div>
                    <div class="label">Admin Users</div>
                </div>
                <div class="stat-card">
                    <div class="number"><?php echo $roleStats['User'] ?? 0; ?></div>
                    <div class="label">Regular Users</div>
                </div>
                <div class="stat-card">
                    <div class="number"><?php echo $roleStats['Guest'] ?? 0; ?></div>
                    <div class="label">Guest Users</div>
                </div>
            </div>
        </div>

        <!-- Chart Container -->
        <div class="chart-container">
            <h2>Tweet Language Distribution</h2>
            <canvas id="languageChart"></canvas>
            
            <!-- Language legend -->
            <div class="language-legend">
                <div class="legend-item">
                    <div class="color-box english-color"></div>
                    <span>English</span>
                </div>
                <div class="legend-item">
                    <div class="color-box spanish-color"></div>
                    <span>Spanish</span>
                </div>
                <div class="legend-item">
                    <div class="color-box french-color"></div>
                    <span>French</span>
                </div>
                <div class="legend-item">
                    <div class="color-box other-color"></div>
                    <span>Other</span>
                </div>
            </div>
        </div>
    </div>

    <script>
        var ctx = document.getElementById('languageChart').getContext('2d');
        var languageChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?php echo $labels; ?>,
                datasets: [{
                    label: 'Number of Tweets',
                    data: <?php echo $dataValues; ?>,
                    backgroundColor: <?php echo $colors; ?>,
                    borderColor: 'rgba(0, 0, 0, 0.1)',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false // Hide default legend since we created a custom one
                    }
                }
            }
        });
    </script>
</body>
</html>