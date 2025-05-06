<nav id="sidebarMenu" class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
    <div class="position-sticky pt-3">
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : ''; ?>" href="index.php">
                    <i class="bi bi-speedometer2 me-1"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'general-ledger.php' ? 'active' : ''; ?>" href="general-ledger.php">
                    <i class="bi bi-journal-text me-1"></i> General Ledger
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'cash-disbursement.php' ? 'active' : ''; ?>" href="cash-disbursement.php">
                    <i class="bi bi-cash-stack me-1"></i> Cash Disbursement
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'chart-of-accounts.php' ? 'active' : ''; ?>" href="chart-of-accounts.php">
                    <i class="bi bi-list-check me-1"></i> Chart of Accounts
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'trial-balance.php' ? 'active' : ''; ?>" href="trial-balance.php">
                    <i class="bi bi-calculator me-1"></i> Trial Balance
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : ''; ?>" href="reports.php">
                    <i class="bi bi-file-earmark-text me-1"></i> Reports
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'forecast.php' ? 'active' : ''; ?>" href="forecast.php">
                    <i class="bi bi-graph-up me-1"></i> Budget Forecast
                </a>
            </li>
        </ul>
        
        <?php if (isset($_SESSION['role']) && $_SESSION['role'] == 'Admin'): ?>
        <h6 class="sidebar-heading d-flex justify-content-between align-items-center px-3 mt-4 mb-1 text-muted">
            <span>Administration</span>
        </h6>
        <ul class="nav flex-column mb-2">
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'user-control.php' ? 'active' : ''; ?>" href="user-control.php">
                    <i class="bi bi-people me-1"></i> User Control
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : ''; ?>" href="settings.php">
                    <i class="bi bi-gear me-1"></i> System Settings
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?php echo basename($_SERVER['PHP_SELF']) == 'backup.php' ? 'active' : ''; ?>" href="backup.php">
                    <i class="bi bi-cloud-arrow-up me-1"></i> Backup & Restore
                </a>
            </li>
        </ul>
        <?php endif; ?>
    </div>
</nav>
