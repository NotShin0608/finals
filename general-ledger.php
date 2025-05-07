<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Include necessary files
require_once 'config/functions.php';

// Initialize variables
$error = '';
$ledgerEntries = [];
$totalEntries = 0;
$totalPages = 1;

// Database connection
$conn = getConnection();

try {
    // Pagination settings
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 15;
    $offset = ($page - 1) * $limit;

    // Get search filters
$filters = [];
$whereClause = [];
$params = [];
$types = '';

// Handle date filters
if (!empty($_GET['start_date'])) {
    $whereClause[] = "t.transaction_date >= ?";
    $params[] = $_GET['start_date'];
    $types .= 's';
    $filters['start_date'] = $_GET['start_date'];
}

if (!empty($_GET['end_date'])) {
    $whereClause[] = "t.transaction_date <= ?";
    $params[] = $_GET['end_date'];
    $types .= 's';
    $filters['end_date'] = $_GET['end_date'];
}

// Handle reference filter
if (!empty($_GET['reference'])) {
    $whereClause[] = "t.reference_number LIKE ?";
    $params[] = '%' . $_GET['reference'] . '%';
    $types .= 's';
    $filters['reference'] = $_GET['reference'];
}

// Handle account filter
if (!empty($_GET['account'])) {
    $whereClause[] = "(a.account_code LIKE ? OR a.account_name LIKE ?)";
    $params[] = '%' . $_GET['account'] . '%';
    $params[] = '%' . $_GET['account'] . '%';
    $types .= 'ss';
    $filters['account'] = $_GET['account'];
}

// Handle status filter
if (!empty($_GET['status'])) {
    $whereClause[] = "t.status = ?";
    $params[] = $_GET['status'];
    $types .= 's';
    $filters['status'] = $_GET['status'];
}

    // Build the base query
    $baseQuery = "
        FROM transactions t
        JOIN transaction_details td ON t.id = td.transaction_id
        JOIN accounts a ON td.account_id = a.id
    ";

    // Add where clause if filters exist
    if (!empty($whereClause)) {
        $baseQuery .= " WHERE " . implode(" AND ", $whereClause);
    }

    // Get total count for pagination
    $countQuery = "SELECT COUNT(DISTINCT t.id) " . $baseQuery;
    $stmt = $conn->prepare($countQuery);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $totalEntries = $stmt->get_result()->fetch_row()[0];
    $totalPages = ceil($totalEntries / $limit);

    // Get ledger entries
    $query = "
        SELECT 
            t.id,
            t.reference_number,
            t.transaction_date,
            t.description,
            t.status,
            td.entry_type,
            td.particulars,
            td.debit_amount,
            td.credit_amount,
            a.account_code,
            a.account_name
        " . $baseQuery . "
        ORDER BY t.transaction_date DESC, t.id DESC
        LIMIT ? OFFSET ?
    ";

    // Add pagination parameters
    $params[] = $limit;
    $params[] = $offset;
    $types .= 'ii';

    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $ledgerEntries = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

} catch (Exception $e) {
    $error = "An error occurred while retrieving the data. Please try again later.";
    error_log($e->getMessage());
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'includes/head.php'; ?>
    <style>
        .text-danger { color: #dc3545 !important; }
        .table td { vertical-align: middle; }
        .pending-void { background-color: #fff3cd; }
        .voided { background-color: #f8d7da; }
        
        /* Print styles */
        @media print {
            .btn-toolbar, .sidebar, .navbar, .search-filters,
            .actions-column, .pagination, footer {
                display: none !important;
            }
            .main { margin-left: 0 !important; }
            .table { font-size: 12px; }
            @page { margin: 0.5cm; }
        }

        /* Search filters styling */
        .search-filters {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        /* Current Info Bar */
        .current-info-bar {
            font-family: 'Courier New', Courier, monospace;
            background-color: #f8f9fa;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
            border-left: 4px solid #0d6efd;
        }

.search-filters {
    background-color: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
    margin-bottom: 20px;
}

.search-filters .form-label {
    font-weight: 500;
    color: #495057;
}

.search-filters .form-select {
    height: 38px;
}

.form-select {
    padding-right: 2rem;
    background-position: right 0.75rem center;
}

.btn-outline-secondary {
    margin-top: 10px;
}

/* Status colors in the table */
.status-Posted {
    color: #28a745;
}

.status-Voided {
    color: #dc3545;
}

.status-Pending {
    color: #ffc107;
}

#approvalsModal .table td {
    vertical-align: middle;
}

#approvalsModal .transaction-details {
    font-size: 0.875em;
    color: #6c757d;
    margin-top: 3px;
}

#approvalsModal .btn-group-sm > .btn {
    padding: 0.25rem 0.5rem;
}

#approvalsModal .requester-info {
    display: flex;
    align-items: center;
    gap: 8px;
}

#approvalsModal .requester-info i {
    font-size: 1.2em;
}

.void-reason {
    white-space: pre-wrap;
    max-width: 300px;
}


    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
            
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">General Ledger</h1>
                    <!-- In your general-ledger.php HTML -->
<div class="btn-toolbar mb-2 mb-md-0">
    <div class="btn-group me-2">
        <button type="button" class="btn btn-outline-secondary" onclick="printLedger()">
            <i class="bi bi-printer"></i> Print
        </button>
        <?php if (isAdmin($_SESSION['user_id'])): ?>
        <button type="button" class="btn btn-info" onclick="showApprovalsModal()">
            <i class="bi bi-check-circle"></i> Approvals
            <span class="badge bg-danger pending-count">0</span>
        </button>
        <?php endif; ?>
        <a href="add-transaction.php" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> New Entry
        </a>
    </div>
</div>
                </div>

              
                <!-- Search Filters -->
<div class="card mb-4 search-filters">
    <div class="card-body">
        <form method="GET" id="searchForm" class="row g-3">
            <div class="col-md-2">
                <label class="form-label">Start Date</label>
                <input type="date" class="form-control" name="start_date" 
                       value="<?php echo htmlspecialchars($_GET['start_date'] ?? ''); ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">End Date</label>
                <input type="date" class="form-control" name="end_date" 
                       value="<?php echo htmlspecialchars($_GET['end_date'] ?? ''); ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label">Reference Number</label>
                <input type="text" class="form-control" name="reference" 
                       value="<?php echo htmlspecialchars($_GET['reference'] ?? ''); ?>" 
                       placeholder="Search by reference...">
            </div>
            <div class="col-md-2">
                <label class="form-label">Account</label>
                <input type="text" class="form-control" name="account" 
                       value="<?php echo htmlspecialchars($_GET['account'] ?? ''); ?>" 
                       placeholder="Account code or name...">
            </div>
            <div class="col-md-2">
                <label class="form-label">Status</label>
                <select class="form-select" name="status">
                    <option value="">All Status</option>
                    <option value="Posted" <?php echo (isset($_GET['status']) && $_GET['status'] === 'Posted') ? 'selected' : ''; ?>>Posted</option>
                    <option value="Voided" <?php echo (isset($_GET['status']) && $_GET['status'] === 'Voided') ? 'selected' : ''; ?>>Voided</option>
                    <option value="Pending Void" <?php echo (isset($_GET['status']) && $_GET['status'] === 'Pending Void') ? 'selected' : ''; ?>>Pending Void</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-search"></i> Search
                    </button>
                </div>
            </div>
            <div class="col-12">
                <div class="text-end">
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="clearFilters()">
                        <i class="bi bi-x-circle"></i> Clear Filters
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <!-- Ledger Entries -->
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Ledger Entries</h5>
                            <small class="text-muted">
                                Showing <?php echo $offset + 1; ?> to <?php echo min($offset + $limit, $totalEntries); ?> 
                                of <?php echo $totalEntries; ?> entries
                            </small>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Reference</th>
                                        <th>Account</th>
                                        <th>Description</th>
                                        <th class="text-end">Debit</th>
                                        <th class="text-end">Credit</th>
                                        <th class="text-center">Status</th>
                                        <th class="actions-column text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($ledgerEntries)): ?>
                                        <tr>
                                            <td colspan="8" class="text-center">No entries found</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($ledgerEntries as $entry): ?>
                                        <tr class="<?php echo $entry['status'] === 'Voided' ? 'voided' : 
                                                       ($entry['status'] === 'Pending Void' ? 'pending-void' : ''); ?>">
                                            <td><?php echo date('Y-m-d', strtotime($entry['transaction_date'])); ?></td>
                                            <td><?php echo htmlspecialchars($entry['reference_number']); ?></td>
                                            <td><?php echo htmlspecialchars($entry['account_code'] . ' - ' . $entry['account_name']); ?></td>
                                            <td><?php echo htmlspecialchars($entry['description']); ?></td>
                                            <td class="text-end">
                                                <?php echo $entry['debit_amount'] > 0 ? formatCurrency($entry['debit_amount']) : ''; ?>
                                            </td>
                                            <td class="text-end">
                                                <?php echo $entry['credit_amount'] > 0 ? formatCurrency($entry['credit_amount']) : ''; ?>
                                            </td>
                                            <td class="text-center">
                                                <?php echo htmlspecialchars($entry['status']); ?>
                                            </td>
                                            <td class="actions-column text-center">
                                                <?php if ($entry['status'] === 'Posted'): ?>
                                                    <button type="button" class="btn btn-danger btn-sm" 
                                                            onclick="voidTransaction(<?php echo $entry['id']; ?>)">
                                                        <i class="bi bi-x-circle"></i> Void
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                    <div class="card-footer">
                        <nav aria-label="Page navigation">
                            <ul class="pagination justify-content-center mb-0">
                                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                        Previous
                                    </a>
                                </li>
                                <?php for($i = 1; $i <= $totalPages; $i++): ?>
                                    <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                        Next
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                    <?php endif; ?>
                </div>

                
                <!-- Approvals Modal -->
<div class="modal fade" id="approvalsModal" tabindex="-1">
    <div class="modal-dialog modal-xl"> <!-- Changed to modal-xl for wider view -->
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-shield-check"></i> Pending Void Approvals
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Request Date</th>
                                <th>Transaction Date</th>
                                <th>Reference</th>
                                <th>Requested By</th>
                                <th>Reason for Void</th>
                                <th style="width: 150px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="approvals-list">
                            <!-- Will be populated by JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>


            </main>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script>
// Update datetime display
function updateDateTime() {
    const now = new Date();
    const formatted = now.getUTCFullYear() + '-' + 
                    String(now.getUTCMonth() + 1).padStart(2, '0') + '-' + 
                    String(now.getUTCDate()).padStart(2, '0') + ' ' + 
                    String(now.getUTCHours()).padStart(2, '0') + ':' + 
                    String(now.getUTCMinutes()).padStart(2, '0') + ':' + 
                    String(now.getUTCSeconds()).padStart(2, '0');
    document.getElementById('currentDateTime').textContent = formatted;
}

// Initialize datetime display
updateDateTime();
setInterval(updateDateTime, 1000);

// Print functionality
function printLedger() {
    window.print();
}

// Void transaction functionality
function voidTransaction(id) {
    const reason = prompt('Please enter reason for voiding this transaction:');
    if (!reason) {
        return; // Cancel if no reason provided
    }

    $.ajax({
        url: 'api/void_transaction.php',
        type: 'POST',
        data: {
            transaction_id: id,
            reason: reason
        },
        success: function(response) {
            if (response.success) {
                alert('Void request submitted successfully');
                location.reload();
            } else {
                alert(response.message || 'Error submitting void request');
            }
        },
        error: function(xhr, status, error) {
            console.error('Ajax Error:', error);
            console.error('Response:', xhr.responseText);
            alert('Error submitting void request. Please try again.');
        }
    });
}


// Update the showApprovalsModal function
function showApprovalsModal() {
    $.ajax({
        url: 'api/check_user_role.php',
        type: 'GET',
        success: function(response) {
            if (response.success && response.role === 'admin') {
                loadPendingVoids();
            } else {
                alert('Only administrators can access the approvals section.');
            }
        },
        error: function() {
            alert('Error checking user permissions.');
        }
    });
}

function voidTransaction(id) {
    $.ajax({
        url: 'api/check_user_role.php',
        type: 'GET',
        success: function(response) {
            if (response.success) {
                const role = response.role;
                const message = role === 'admin' ? 
                    'Enter reason for voiding this transaction:' :
                    'Enter reason for requesting void approval:';
                
                const reason = prompt(message);
                if (!reason) return;

                submitVoidRequest(id, reason);
            }
        },
        error: function() {
            alert('Error checking user permissions.');
        }
    });
}

function submitVoidRequest(id, reason) {
    $.ajax({
        url: 'api/void_transaction.php',
        type: 'POST',
        data: {
            transaction_id: id,
            reason: reason
        },
        success: function(response) {
            if (response.success) {
                alert(response.message);
                location.reload();
            } else {
                alert(response.message || 'Error submitting void request');
            }
        },
        error: function(xhr, status, error) {
            console.error('Ajax Error:', error);
            alert('Error submitting void request. Please try again.');
        }
    });
}

function loadPendingVoids() {
    $('#approvals-list').html('<tr><td colspan="5" class="text-center">Loading...</td></tr>');
    $('#approvalsModal').modal('show');

    $.ajax({
        url: 'api/get_pending_voids.php',
        type: 'GET',
        success: function(response) {
            if (response.success) {
                const requests = response.data;
                $('#approvals-list').empty();
                
                if (requests.length === 0) {
                    $('#approvals-list').html(`
                        <tr>
                            <td colspan="5" class="text-center">No pending void requests</td>
                        </tr>
                    `);
                } else {
                    requests.forEach(function(request) {
                        const row = `
                            <tr>
                                <td>${request.requested_date}</td>
                                <td>${request.reference_number}<br>
                                    <small class="text-muted">${request.description}</small>
                                </td>
                                <td>${request.requested_by}</td>
                                <td>${request.reason}</td>
                                <td>
                                    <button class="btn btn-success btn-sm" onclick="processVoidRequest(${request.id}, 'approve')">
                                        <i class="bi bi-check-circle"></i> Approve
                                    </button>
                                    <button class="btn btn-danger btn-sm" onclick="processVoidRequest(${request.id}, 'reject')">
                                        <i class="bi bi-x-circle"></i> Reject
                                    </button>
                                </td>
                            </tr>
                        `;
                        $('#approvals-list').append(row);
                    });
                }
                
                // Update pending count badge
                $('.pending-count').text(requests.length);
            } else {
                $('#approvals-list').html(`
                    <tr>
                        <td colspan="5" class="text-center text-danger">
                            ${response.message || 'Error loading void requests'}
                        </td>
                    </tr>
                `);
            }
        },
        error: function(xhr, status, error) {
            console.error('Ajax Error:', error);
            console.error('Response:', xhr.responseText);
            $('#approvals-list').html(`
                <tr>
                    <td colspan="5" class="text-center text-danger">
                        Error loading void requests. Please try again.
                    </td>
                </tr>
            `);
        }
    });
}

// Process void request (approve/reject)
function processVoidRequest(requestId, action) {
    const confirmMessage = action === 'approve' 
        ? 'Are you sure you want to approve this void request?' 
        : 'Are you sure you want to reject this void request?';
    
    if (!confirm(confirmMessage)) {
        return;
    }

    $.ajax({
        url: 'api/process_void_request.php',
        type: 'POST',
        data: {
            request_id: requestId,
            action: action
        },
        success: function(response) {
            if (response.success) {
                alert(`Void request ${action}ed successfully.`);
                showApprovalsModal(); // Refresh the approvals list
                location.reload(); // Refresh the main page if needed
            } else {
                alert(response.message || `Error ${action}ing void request.`);
            }
        },
        error: function() {
            alert(`Error ${action}ing void request. Please try again.`);
        }
    });
}

// Helper function to format dates
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.getUTCFullYear() + '-' + 
           String(date.getUTCMonth() + 1).padStart(2, '0') + '-' + 
           String(date.getUTCDate()).padStart(2, '0') + ' ' + 
           String(date.getUTCHours()).padStart(2, '0') + ':' + 
           String(date.getUTCMinutes()).padStart(2, '0') + ':' + 
           String(date.getUTCSeconds()).padStart(2, '0');
}

// Initialize on document ready
$(document).ready(function() {
    // Check for pending void requests on page load
    $.ajax({
        url: 'api/get_pending_voids.php',
        type: 'GET',
        success: function(response) {
            if (response.success) {
                $('.pending-count').text(response.data.length);
            }
        }
    });

    // Initialize any Bootstrap tooltips
    $('[data-bs-toggle="tooltip"]').tooltip();

    // Handle search form reset
    $('#searchForm').on('reset', function(e) {
        e.preventDefault();
        window.location.href = 'general-ledger.php';
    });
});
function processVoidRequest(requestId, action) {
    const confirmMessage = action === 'approve' 
        ? 'Are you sure you want to approve this void request?' 
        : 'Are you sure you want to reject this void request?';
    
    if (!confirm(confirmMessage)) {
        return;
    }

    $.ajax({
        url: 'api/process_void_request.php',
        type: 'POST',
        data: {
            request_id: requestId,
            action: action
        },
        success: function(response) {
            if (response.success) {
                alert(response.message);
                // Refresh the approvals list
                showApprovalsModal();
                // Reload the main page to update transaction statuses
                location.reload();
            } else {
                alert(response.message || `Error ${action}ing void request`);
            }
        },
        error: function(xhr, status, error) {
            console.error('Ajax Error:', error);
            console.error('Response:', xhr.responseText);
            alert(`Error ${action}ing void request. Please try again.`);
        }
    });
}

// Update showApprovalsModal to include more transaction details
function showApprovalsModal() {
    $('#approvals-list').html('<tr><td colspan="6" class="text-center"><i class="bi bi-hourglass"></i> Loading...</td></tr>');
    $('#approvalsModal').modal('show');

    $.ajax({
        url: 'api/get_pending_voids.php',
        type: 'GET',
        success: function(response) {
            if (response.success) {
                const requests = response.data;
                $('#approvals-list').empty();
                
                if (requests.length === 0) {
                    $('#approvals-list').html(`
                        <tr>
                            <td colspan="6" class="text-center text-muted">
                                <i class="bi bi-info-circle"></i> No pending void requests
                            </td>
                        </tr>
                    `);
                } else {
                    requests.forEach(function(request) {
    const row = `
        <tr>
            <td>
                <div><strong>${formatDateTime(request.requested_date)}</strong></div>
            </td>
            <td>${request.transaction_date}</td>
            <td>
                <div><strong>${request.reference_number}</strong></div>
                <div class="transaction-details">
                    ${request.account_info}<br>
                    ${request.description}<br>
                    Amount: ${request.formatted_amount}
                </div>
            </td>
            <td>
                <div class="requester-info">
                    <i class="bi bi-person-circle"></i>
                    <div>
                        <div><strong>${request.requested_by}</strong></div>
                        <small class="text-muted">${capitalizeFirst(request.requester_role)}</small>
                    </div>
                </div>
            </td>
            <td>
                <div class="void-reason">${request.reason}</div>
            </td>
            <td>
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-success" 
                            onclick="processVoidRequest(${request.id}, 'approve')"
                            title="Approve void request">
                        <i class="bi bi-check-circle"></i> Approve
                    </button>
                    <button class="btn btn-danger" 
                            onclick="processVoidRequest(${request.id}, 'reject')"
                            title="Reject void request">
                        <i class="bi bi-x-circle"></i> Reject
                    </button>
                </div>
            </td>
        </tr>
    `;
    $('#approvals-list').append(row);
});
                }
                
                // Update pending count badge
                $('.pending-count').text(requests.length);
            } else {
                $('#approvals-list').html(`
                    <tr>
                        <td colspan="6" class="text-center text-danger">
                            <i class="bi bi-exclamation-triangle"></i> ${response.message || 'Error loading void requests'}
                        </td>
                    </tr>
                `);
            }
        },
        error: function(xhr, status, error) {
            console.error('Ajax Error:', error);
            console.error('Response:', xhr.responseText);
            $('#approvals-list').html(`
                <tr>
                    <td colspan="6" class="text-center text-danger">
                        <i class="bi bi-exclamation-triangle"></i> Error loading void requests. Please try again.
                    </td>
                </tr>
            `);
        }
    });
}

// Helper function to format date and time
function formatDateTime(dateString) {
    const date = new Date(dateString);
    return date.getUTCFullYear() + '-' + 
           String(date.getUTCMonth() + 1).padStart(2, '0') + '-' + 
           String(date.getUTCDate()).padStart(2, '0') + ' ' + 
           String(date.getUTCHours()).padStart(2, '0') + ':' + 
           String(date.getUTCMinutes()).padStart(2, '0') + ':' + 
           String(date.getUTCSeconds()).padStart(2, '0');
}

// Helper function to capitalize first letter
function capitalizeFirst(string) {
    return string.charAt(0).toUpperCase() + string.slice(1).toLowerCase();
}

// Add this with your other JavaScript functions
function clearFilters() {
    // Clear all form inputs
    document.getElementById('searchForm').reset();
    // Redirect to the page without query parameters
    window.location.href = 'general-ledger.php';
}

// Update the existing datetime display code
function updateDateTime() {
    const now = new Date();
    const formatted = now.getUTCFullYear() + '-' + 
                     String(now.getUTCMonth() + 1).padStart(2, '0') + '-' + 
                     String(now.getUTCDate()).padStart(2, '0') + ' ' + 
                     String(now.getUTCHours()).padStart(2, '0') + ':' + 
                     String(now.getUTCMinutes()).padStart(2, '0') + ':' + 
                     String(now.getUTCSeconds()).padStart(2, '0');
    document.getElementById('currentDateTime').textContent = formatted;
}

// Initialize datetime display
updateDateTime();
setInterval(updateDateTime, 1000);

// Add some styling for the status dropdown
$(document).ready(function() {
    // Add Bootstrap Select to enhance the dropdown (optional)
    $('.form-select').select2({
        theme: 'bootstrap4',
        width: '100%'
    });
});


</script>
</body>
</html>