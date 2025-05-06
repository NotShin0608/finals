<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Include necessary files
require_once 'config/functions.php';

// Get date for reports
$date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
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
                    <h1 class="h2">Reports</h1>
                </div>
                
                <div class="row mb-4">
                    <div class="col-md-6">
                        <form action="" method="GET" class="d-flex">
                            <div class="input-group">
                                <span class="input-group-text">Report Date</span>
                                <input type="date" name="date" class="form-control" value="<?php echo $date; ?>">
                                <button type="submit" class="btn btn-primary">Update</button>
                            </div>
                        </form>
                    </div>
                    <div class="col-md-6 text-end">
                        <button onclick="window.print()" class="btn btn-secondary">Print</button>
                        <a href="#" class="btn btn-danger">Export PDF</a>
                    </div>
                </div>
                
                <div class="accordion" id="reportsAccordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingOne">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                Financial Statements
                            </button>
                        </h2>
                        <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#reportsAccordion">
                            <div class="accordion-body">
                                <div class="list-group">
                                    <a href="income-statement.php?date=<?php echo $date; ?>" class="list-group-item list-group-item-action">Income Statement</a>
                                    <a href="balance-sheet.php?date=<?php echo $date; ?>" class="list-group-item list-group-item-action">Balance Sheet</a>
                                    <a href="cash-flow.php?date=<?php echo $date; ?>" class="list-group-item list-group-item-action">Cash Flow Statement</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingTwo">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                Cash Management
                            </button>
                        </h2>
                        <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#reportsAccordion">
                            <div class="accordion-body">
                                <div class="list-group">
                                    <a href="cash-receipts.php?date=<?php echo $date; ?>" class="list-group-item list-group-item-action">Cash Receipts Journal</a>
                                    <a href="cash-disbursements.php?date=<?php echo $date; ?>" class="list-group-item list-group-item-action">Cash Disbursements Journal</a>
                                    <a href="cash-position.php?date=<?php echo $date; ?>" class="list-group-item list-group-item-action">Cash Position Report</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingThree">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                Account Analysis
                            </button>
                        </h2>
                        <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#reportsAccordion">
                            <div class="accordion-body">
                                <div class="list-group">
                                    <a href="account-details.php?date=<?php echo $date; ?>" class="list-group-item list-group-item-action">Account Details</a>
                                    <a href="account-summary.php?date=<?php echo $date; ?>" class="list-group-item list-group-item-action">Account Summary</a>
                                    <a href="account-aging.php?date=<?php echo $date; ?>" class="list-group-item list-group-item-action">Aging Analysis</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingFour">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                                Revenue Analysis
                            </button>
                        </h2>
                        <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="headingFour" data-bs-parent="#reportsAccordion">
                            <div class="accordion-body">
                                <div class="list-group">
                                    <a href="revenue-by-category.php?date=<?php echo $date; ?>" class="list-group-item list-group-item-action">Revenue by Category</a>
                                    <a href="revenue-trends.php?date=<?php echo $date; ?>" class="list-group-item list-group-item-action">Revenue Trends</a>
                                    <a href="revenue-comparison.php?date=<?php echo $date; ?>" class="list-group-item list-group-item-action">Year-over-Year Comparison</a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header" id="headingFive">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFive" aria-expanded="false" aria-controls="collapseFive">
                                Audit & Compliance
                            </button>
                        </h2>
                        <div id="collapseFive" class="accordion-collapse collapse" aria-labelledby="headingFive" data-bs-parent="#reportsAccordion">
                            <div class="accordion-body">
                                <div class="list-group">
                                    <a href="audit-trail.php?date=<?php echo $date; ?>" class="list-group-item list-group-item-action">Audit Trail</a>
                                    <a href="user-activity.php?date=<?php echo $date; ?>" class="list-group-item list-group-item-action">User Activity Log</a>
                                    <a href="system-logs.php?date=<?php echo $date; ?>" class="list-group-item list-group-item-action">System Logs</a>
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
