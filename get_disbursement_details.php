<?php
session_start();
require_once 'config/functions.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die("Unauthorized access");
}

// Get disbursement ID
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get disbursement details
$details = getDisbursementDetails($id);

if (!$details) {
    die("Disbursement not found");
}
?>

<div class="row">
    <div class="col-md-6">
        <h6>Basic Information</h6>
        <table class="table table-sm">
            <tr>
                <th width="150">Voucher Number</th>
                <td><?php echo htmlspecialchars($details['voucher_number']); ?></td>
            </tr>
            <tr>
                <th>Date</th>
                <td><?php echo date('Y-m-d', strtotime($details['disbursement_date'])); ?></td>
            </tr>
            <tr>
                <th>Payee</th>
                <td><?php echo htmlspecialchars($details['payee']); ?></td>
            </tr>
            <tr>
                <th>Amount</th>
                <td><?php echo formatCurrency($details['amount']); ?></td>
            </tr>
            <tr>
                <th>Status</th>
                <td>
                    <span class="badge bg-<?php echo getStatusColor($details['status']); ?>">
                        <?php echo htmlspecialchars($details['status']); ?>
                    </span>
                </td>
            </tr>
        </table>
    </div>
    <div class="col-md-6">
        <h6>Additional Information</h6>
        <table class="table table-sm">
            <tr>
                <th width="150">Created By</th>
                <td><?php echo htmlspecialchars($details['created_by']); ?></td>
            </tr>
            <tr>
                <th>Created Date</th>
                <td><?php echo date('Y-m-d H:i:s', strtotime($details['created_at'])); ?></td>
            </tr>
            <tr>
                <th>Last Updated</th>
                <td><?php echo date('Y-m-d H:i:s', strtotime($details['updated_at'])); ?></td>
            </tr>
            <?php if ($details['status'] === 'Voided'): ?>
            <tr>
                <th>Void Reason</th>
                <td><?php echo htmlspecialchars($details['void_reason']); ?></td>
            </tr>
            <?php endif; ?>
        </table>
    </div>
</div>

<div class="row mt-4">
    <div class="col-12">
        <h6>Transaction Details</h6>
        <table class="table table-sm">
            <thead>
                <tr>
                    <th>Account</th>
                    <th>Description</th>
                    <th class="text-end">Debit</th>
                    <th class="text-end">Credit</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($details['entries'] as $entry): ?>
                <tr>
                    <td><?php echo htmlspecialchars($entry['account_name']); ?></td>
                    <td><?php echo htmlspecialchars($entry['description']); ?></td>
                    <td class="text-end"><?php echo formatCurrency($entry['debit_amount']); ?></td>
                    <td class="text-end"><?php echo formatCurrency($entry['credit_amount']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>