<?php
session_start();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header("Location: login.php");
    exit();
}

// Include necessary files
require_once 'config/functions.php';

// Function to get all users with their access logs
function getUsersWithAccessLogs() {
    $conn = getConnection();
    
    $sql = "SELECT 
                u.id, 
                u.username, 
                u.full_name, 
                u.email, 
                u.role, 
                u.created_at,
                (SELECT MAX(login_time) FROM user_access_logs WHERE user_id = u.id) as last_login
            FROM 
                users u
            ORDER BY 
                u.role, u.username";
    
    $result = $conn->query($sql);
    $users = [];
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
    }
    
    closeConnection($conn);
    return $users;
}

// Function to get recent access logs
function getRecentAccessLogs($limit = 20) {
    $conn = getConnection();
    
    $sql = "SELECT 
                l.id,
                l.user_id,
                u.username,
                u.full_name,
                l.login_time,
                l.logout_time,
                l.ip_address,
                l.user_agent
            FROM 
                user_access_logs l
                JOIN users u ON l.user_id = u.id
            ORDER BY 
                l.login_time DESC
            LIMIT $limit";
    
    $result = $conn->query($sql);
    $logs = [];
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $logs[] = $row;
        }
    }
    
    closeConnection($conn);
    return $logs;
}

// Process form submission for changing user role
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_role') {
    $userId = $_POST['user_id'] ?? 0;
    $newRole = $_POST['new_role'] ?? '';
    
    if ($userId > 0 && !empty($newRole)) {
        $conn = getConnection();
        
        $sql = "UPDATE users SET role = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("si", $newRole, $userId);
        
        if ($stmt->execute()) {
            $success = "User role updated successfully";
        } else {
            $error = "Error updating user role: " . $conn->error;
        }
        
        closeConnection($conn);
    }
}

// Get users with access logs
$users = getUsersWithAccessLogs();

// Get recent access logs
$accessLogs = getRecentAccessLogs();

// Set page title
$pageTitle = "User Control";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'includes/head.php'; ?>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">User Access Control</h1>
                </div>
                
                <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">User Access Management</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>Username</th>
                                                <th>Full Name</th>
                                                <th>Email</th>
                                                <th>Role</th>
                                                <th>Created</th>
                                                <th>Last Login</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($users as $user): ?>
                                            <tr>
                                                <td><?php echo $user['username']; ?></td>
                                                <td><?php echo $user['full_name']; ?></td>
                                                <td><?php echo $user['email']; ?></td>
                                                <td>
                                                    <span class="badge <?php echo $user['role'] === 'Admin' ? 'bg-danger' : ($user['role'] === 'Accountant' ? 'bg-warning' : 'bg-info'); ?>">
                                                        <?php echo $user['role']; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('Y-m-d', strtotime($user['created_at'])); ?></td>
                                                <td><?php echo $user['last_login'] ? date('Y-m-d H:i', strtotime($user['last_login'])) : 'Never'; ?></td>
                                                <td>
                                                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#changeRoleModal<?php echo $user['id']; ?>">
                                                        Change Role
                                                    </button>
                                                    <a href="view-user-logs.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-info">
                                                        View Logs
                                                    </a>
                                                </td>
                                            </tr>
                                            
                                            <!-- Change Role Modal -->
                                            <div class="modal fade" id="changeRoleModal<?php echo $user['id']; ?>" tabindex="-1" aria-labelledby="changeRoleModalLabel<?php echo $user['id']; ?>" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="changeRoleModalLabel<?php echo $user['id']; ?>">Change Role for <?php echo $user['username']; ?></h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <form action="" method="POST">
                                                            <div class="modal-body">
                                                                <input type="hidden" name="action" value="change_role">
                                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                                <div class="mb-3">
                                                                    <label for="new_role<?php echo $user['id']; ?>" class="form-label">Select New Role</label>
                                                                    <select class="form-select" id="new_role<?php echo $user['id']; ?>" name="new_role" required>
                                                                        <option value="">Select Role</option>
                                                                        <option value="Admin" <?php echo $user['role'] === 'Admin' ? 'selected' : ''; ?>>Admin</option>
                                                                        <option value="Accountant" <?php echo $user['role'] === 'Accountant' ? 'selected' : ''; ?>>Accountant</option>
                                                                        <option value="Viewer" <?php echo $user['role'] === 'Viewer' ? 'selected' : ''; ?>>Viewer</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                <button type="submit" class="btn btn-primary">Save Changes</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>
                                            <?php if (empty($users)): ?>
                                            <tr>
                                                <td colspan="7" class="text-center">No users found</td>
                                            </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Recent Access Logs</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped table-hover">
                                        <thead>
                                            <tr>
                                                <th>User</th>
                                                <th>Login Time</th>
                                                <th>Logout Time</th>
                                                <th>IP Address</th>
                                                <th>Browser</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($accessLogs as $log): ?>
                                            <tr>
                                                <td><?php echo $log['full_name'] . ' (' . $log['username'] . ')'; ?></td>
                                                <td><?php echo date('Y-m-d H:i:s', strtotime($log['login_time'])); ?></td>
                                                <td><?php echo $log['logout_time'] ? date('Y-m-d H:i:s', strtotime($log['logout_time'])) : 'Active/No Logout'; ?></td>
                                                <td><?php echo $log['ip_address']; ?></td>
                                                <td><?php echo substr($log['user_agent'], 0, 50) . (strlen($log['user_agent']) > 50 ? '...' : ''); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                            <?php if (empty($accessLogs)): ?>
                                            <tr>
                                                <td colspan="5" class="text-center">No access logs found</td>
                                            </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>
