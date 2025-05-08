<?php
// Get user data
$userId = $_SESSION['user_id'];
$userData = getUserData($userId);
require_once 'config/functions.php';

?>

<header class="navbar navbar-expand-md navbar-light sticky-top bg-white">
    <div class="container-fluid">
        <button class="navbar-toggler" type="button" id="sidebarToggle">
            <span class="navbar-toggler-icon"></span>
        </button>
        <img src="assets/img/logo.png" class="navbar-brand" style="width: 50px; height: 50px;">
        <a class="navbar-brand" href="index.php">Financial Management System</a>
        
        <div class="w-100"></div>
        <div class="navbar-nav">
            <div class="nav-item text-nowrap d-flex align-items-center">
                <a class="nav-link px-3" href="#" data-bs-toggle="modal" data-bs-target="#profileModal">
                    <?php if (!empty($userData['profile_picture'])): ?>
                        <img src="<?php echo htmlspecialchars($userData['profile_picture']); ?>" 
                             alt="Profile" 
                             class="rounded-circle me-2" 
                             style="width: 32px; height: 32px; object-fit: cover;">
                    <?php else: ?>
                        <i class="bi bi-person-circle me-2"></i>
                    <?php endif; ?>
                    <?php echo htmlspecialchars($userData['username']); ?>
                </a>
                <a class="nav-link px-3" href="#" onclick="confirmLogout()">
                    <i class="bi bi-box-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
</header>

<!-- Profile Modal -->
<div class="modal fade" id="profileModal" tabindex="-1" aria-labelledby="profileModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="profileModalLabel">User Profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="profileForm" action="update_profile.php" method="POST" enctype="multipart/form-data">
                    <div class="text-center mb-4">
                        <div class="position-relative d-inline-block">
                            <?php if (!empty($userData['profile_picture'])): ?>
                                <img src="<?php echo htmlspecialchars($userData['profile_picture']); ?>" 
                                     alt="Profile Picture" 
                                     class="rounded-circle profile-picture"
                                     style="width: 150px; height: 150px; object-fit: cover;">
                            <?php else: ?>
                                <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center"
                                     style="width: 150px; height: 150px;">
                                    <i class="bi bi-person-fill text-white" style="font-size: 4rem;"></i>
                                </div>
                            <?php endif; ?>
                            <label for="profilePicture" class="position-absolute bottom-0 end-0 bg-primary rounded-circle p-2 cursor-pointer">
                                <i class="bi bi-camera-fill text-white"></i>
                                <input type="file" id="profilePicture" name="profile_picture" class="d-none" accept="image/*">
                            </label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="fullName" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="fullName" name="full_name" 
                               value="<?php echo htmlspecialchars($userData['full_name'] ?? ''); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo htmlspecialchars($userData['email'] ?? ''); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label for="role" class="form-label">Role</label>
                        <input type="text" class="form-control" id="role" 
                               value="<?php echo htmlspecialchars($userData['role'] ?? ''); ?>" readonly>
                    </div>

                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" 
                               value="<?php echo htmlspecialchars($userData['username']); ?>" readonly>
                    </div>

                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Logout Confirmation Modal -->
<div class="modal fade" id="logoutModal" tabindex="-1" aria-labelledby="logoutModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="logoutModalLabel">Confirm Logout</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to logout?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="logout.php" class="btn btn-primary">Logout</a>
            </div>
        </div>
    </div>
</div>

<script>
function confirmLogout() {
    const logoutModal = new bootstrap.Modal(document.getElementById('logoutModal'));
    logoutModal.show();
}
</script>