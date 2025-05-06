<?php
// Check if already installed
if (file_exists('config/installed.php')) {
    header("Location: index.php");
    exit();
}

$step = isset($_GET['step']) ? (int)$_GET['step'] : 1;
$error = '';
$success = '';

// Process installation steps
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($step) {
        case 1:
            // Check server requirements
            $step = 2;
            break;
            
        case 2:
            // Database configuration
            $dbHost = $_POST['db_host'] ?? '';
            $dbUser = $_POST['db_user'] ?? '';
            $dbPass = $_POST['db_pass'] ?? '';
            $dbName = $_POST['db_name'] ?? '';
            
            if (empty($dbHost) || empty($dbUser) || empty($dbName)) {
                $error = 'Please fill in all required fields';
            } else {
                // Test database connection
                $conn = new mysqli($dbHost, $dbUser, $dbPass);
                
                if ($conn->connect_error) {
                    $error = 'Database connection failed: ' . $conn->connect_error;
                } else {
                    // Create database if it doesn't exist
                    $conn->query("CREATE DATABASE IF NOT EXISTS `$dbName`");
                    $conn->select_db($dbName);
                    
                    // Create config file
                    $configContent = "<?php
// Database configuration
define('DB_HOST', '$dbHost');
define('DB_USER', '$dbUser');
define('DB_PASS', '$dbPass');
define('DB_NAME', '$dbName');

// Create database connection
function getConnection() {
    \$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    // Check connection
    if (\$conn->connect_error) {
        die(\"Connection failed: \" . \$conn->connect_error);
    }
    
    return \$conn;
}

// Close database connection
function closeConnection(\$conn) {
    \$conn->close();
}
";
                    
                    if (file_put_contents('config/database.php', $configContent)) {
                        $step = 3;
                    } else {
                        $error = 'Could not write to config file. Please check file permissions.';
                    }
                    
                    $conn->close();
                }
            }
            break;
            
        case 3:
            // Admin user setup
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            $fullName = $_POST['full_name'] ?? '';
            $email = $_POST['email'] ?? '';
            
            if (empty($username) || empty($password) || empty($confirmPassword) || empty($fullName) || empty($email)) {
                $error = 'Please fill in all required fields';
            } elseif ($password !== $confirmPassword) {
                $error = 'Passwords do not match';
            } else {
                // Include database config
                require_once 'config/database.php';
                
                // Import database schema
                $conn = getConnection();
                $sqlFile = file_get_contents('database/financial_management.sql');
                $sqlStatements = explode(';', $sqlFile);
                
                foreach ($sqlStatements as $sql) {
                    $sql = trim($sql);
                    if (!empty($sql)) {
                        $conn->query($sql);
                    }
                }
                
                // Create admin user
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $sql = "INSERT INTO users (username, password, full_name, email, role) VALUES (?, ?, ?, ?, 'Admin')";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("ssss", $username, $hashedPassword, $fullName, $email);
                
                if ($stmt->execute()) {
                    // Create installed file
                    file_put_contents('config/installed.php', '<?php // Installation completed on ' . date('Y-m-d H:i:s'));
                    $success = 'Installation completed successfully!';
                    $step = 4;
                } else {
                    $error = 'Error creating admin user: ' . $conn->error;
                }
                
                $conn->close();
            }
            break;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Install - Financial Management System</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            padding-top: 40px;
        }
        .install-container {
            max-width: 700px;
            margin: 0 auto;
        }
        .step-indicator {
            display: flex;
            margin-bottom: 30px;
        }
        .step {
            flex: 1;
            text-align: center;
            padding: 10px;
            border-bottom: 3px solid #dee2e6;
        }
        .step.active {
            border-color: #007bff;
            font-weight: bold;
        }
        .step.completed {
            border-color: #28a745;
        }
    </style>
</head>
<body>
    <div class="container install-container">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white text-center">
                <h3>Financial Management System Installation</h3>
            </div>
            <div class="card-body">
                <div class="step-indicator">
                    <div class="step <?php echo $step >= 1 ? 'active' : ''; ?> <?php echo $step > 1 ? 'completed' : ''; ?>">
                        1. Requirements
                    </div>
                    <div class="step <?php echo $step >= 2 ? 'active' : ''; ?> <?php echo $step > 2 ? 'completed' : ''; ?>">
                        2. Database
                    </div>
                    <div class="step <?php echo $step >= 3 ? 'active' : ''; ?> <?php echo $step > 3 ? 'completed' : ''; ?>">
                        3. Admin User
                    </div>
                    <div class="step <?php echo $step >= 4 ? 'active' : ''; ?>">
                        4. Finish
                    </div>
                </div>
                
                <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if ($step == 1): ?>
                <!-- Step 1: Requirements -->
                <h4>System Requirements</h4>
                <p>Please make sure your server meets the following requirements:</p>
                
                <table class="table">
                    <tbody>
                        <tr>
                            <td>PHP Version</td>
                            <td><?php echo phpversion(); ?></td>
                            <td><?php echo phpversion() >= '7.4' ? '<span class="text-success">✓</span>' : '<span class="text-danger">✗</span>'; ?></td>
                        </tr>
                        <tr>
                            <td>MySQL Support</td>
                            <td><?php echo extension_loaded('mysqli') ? 'Available' : 'Not Available'; ?></td>
                            <td><?php echo extension_loaded('mysqli') ? '<span class="text-success">✓</span>' : '<span class="text-danger">✗</span>'; ?></td>
                        </tr>
                        <tr>
                            <td>PDO Support</td>
                            <td><?php echo extension_loaded('pdo') ? 'Available' : 'Not Available'; ?></td>
                            <td><?php echo extension_loaded('pdo') ? '<span class="text-success">✓</span>' : '<span class="text-danger">✗</span>'; ?></td>
                        </tr>
                        <tr>
                            <td>GD Library</td>
                            <td><?php echo extension_loaded('gd') ? 'Available' : 'Not Available'; ?></td>
                            <td><?php echo extension_loaded('gd') ? '<span class="text-success">✓</span>' : '<span class="text-danger">✗</span>'; ?></td>
                        </tr>
                        <tr>
                            <td>config/ Directory Writable</td>
                            <td><?php echo is_writable('config') ? 'Writable' : 'Not Writable'; ?></td>
                            <td><?php echo is_writable('config') ? '<span class="text-success">✓</span>' : '<span class="text-danger">✗</span>'; ?></td>
                        </tr>
                    </tbody>
                </table>
                
                <form action="?step=1" method="POST">
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <button type="submit" class="btn btn-primary" <?php echo (phpversion() >= '7.4' && extension_loaded('mysqli') && extension_loaded('pdo') && is_writable('config')) ? '' : 'disabled'; ?>>Continue</button>
                    </div>
                </form>
                
                <?php elseif ($step == 2): ?>
                <!-- Step 2: Database Configuration -->
                <h4>Database Configuration</h4>
                <p>Please enter your database connection details:</p>
                
                <form action="?step=2" method="POST">
                    <div class="mb-3">
                        <label for="db_host" class="form-label">Database Host *</label>
                        <input type="text" class="form-control" id="db_host" name="db_host" value="localhost" required>
                    </div>
                    <div class="mb-3">
                        <label for="db_name" class="form-label">Database Name *</label>
                        <input type="text" class="form-control" id="db_name" name="db_name" value="financial_management" required>
                    </div>
                    <div class="mb-3">
                        <label for="db_user" class="form-label">Database Username *</label>
                        <input type="text" class="form-control" id="db_user" name="db_user" value="root" required>
                    </div>
                    <div class="mb-3">
                        <label for="db_pass" class="form-label">Database Password</label>
                        <input type="password" class="form-control" id="db_pass" name="db_pass">
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="?step=1" class="btn btn-secondary me-md-2">Back</a>
                        <button type="submit" class="btn btn-primary">Continue</button>
                    </div>
                </form>
                
                <?php elseif ($step == 3): ?>
                <!-- Step 3: Admin User Setup -->
                <h4>Admin User Setup</h4>
                <p>Please create an administrator account:</p>
                
                <form action="?step=3" method="POST">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username *</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password *</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm Password *</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Full Name *</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email *</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                    </div>
                    
                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="?step=2" class="btn btn-secondary me-md-2">Back</a>
                        <button type="submit" class="btn btn-primary">Install</button>
                    </div>
                </form>
                
                <?php elseif ($step == 4): ?>
                <!-- Step 4: Finish -->
                <div class="text-center">
                    <h4>Installation Completed!</h4>
                    <p>The Financial Management System has been successfully installed.</p>
                    <p>You can now log in using the administrator account you created.</p>
                    
                    <div class="alert alert-warning">
                        <strong>Important:</strong> For security reasons, please delete the <code>install.php</code> file from your server.
                    </div>
                    
                    <div class="d-grid gap-2 col-6 mx-auto">
                        <a href="index.php" class="btn btn-primary">Go to Login</a>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <div class="card-footer text-center">
                <small class="text-muted">Financial Management System</small>
            </div>
        </div>
    </div>
</body>
</html>
