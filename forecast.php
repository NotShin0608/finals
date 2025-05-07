<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Include necessary files
require_once 'config/functions.php';
require_once 'config/api_config.php';
require_once 'classes/DeepSeekClient.php';

// Get selected year (default to current year if not specified)
$selectedYear = isset($_GET['year']) ? intval($_GET['year']) : date('Y');
$showForecast = isset($_POST['generate_forecast']);

// Initialize API client
$deepseekClient = new DeepSeekClient();
$forecastData = null;
$aiFeedback = null;

if ($showForecast) {
    try {
        $historicalData = getHistoricalData();
        $availableYears = getAvailableYears();
        
        try {
            $forecastData = $deepseekClient->generateForecast($historicalData, $selectedYear);
            $aiFeedback = $deepseekClient->generateBudgetAnalysis($historicalData, $selectedYear);
            $forecastSource = 'ai';
            $apiStatus = 'online';
        } catch (Exception $e) {
            $forecastData = generateTraditionalForecast($historicalData);
            $aiFeedback = [
                'feedback' => "Using traditional statistical analysis. The system will provide basic insights based on historical data."
            ];
            $forecastSource = 'traditional';
            $apiStatus = 'offline';
            error_log("DeepSeek API Error: " . $e->getMessage());
        }
    } catch (Exception $e) {
        die("Error: " . $e->getMessage());
    }
}

$pageTitle = "Budget Forecast for " . $selectedYear;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'includes/head.php'; ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .year-selector {
            margin-bottom: 20px;
        }
        .api-status {
            font-size: 0.8em;
            margin-left: 10px;
        }
        .api-status.online {
            color: green;
        }
        .api-status.offline {
            color: red;
        }
        .forecast-source {
            font-size: 0.9em;
            color: #666;
            margin-top: 10px;
        }
        .analysis-card {
            border-left: 4px solid #0d6efd;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }
        .analysis-paragraph {
            margin-bottom: 1rem;
            line-height: 1.6;
            color: #2c3e50;
        }
        .analysis-paragraph:last-child {
            margin-bottom: 0;
        }
        .badge {
            padding: 0.5em 1em;
        }
        #forecastChart {
            min-height: 400px;
        }
        .loading {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
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
                    <h1 class="h2">
                        Budget Forecast
                        <?php if ($showForecast): ?>
                        <span class="api-status <?php echo $apiStatus; ?>">
                            <i class="bi bi-robot"></i> 
                            <?php echo $apiStatus === 'online' ? 'AI-Powered' : 'Traditional'; ?>
                        </span>
                        <?php endif; ?>
                    </h1>
                    
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <select class="form-select form-select-sm year-selector" id="yearSelector">
                                <?php
                                $currentYear = date('Y');
                                for ($year = $currentYear + 5; $year >= $currentYear - 2; $year--) {
                                    $selected = ($year == $selectedYear) ? 'selected' : '';
                                    echo "<option value='$year' $selected>$year</option>";
                                }
                                ?>
                            </select>
                        </div>
                        
                        <form method="post" class="me-2" id="forecastForm">
                            <input type="hidden" name="year" id="selectedYearInput" value="<?php echo $selectedYear; ?>">
                            <button type="submit" name="generate_forecast" class="btn btn-primary btn-sm">
                                <i class="bi bi-graph-up"></i> Generate Forecast
                            </button>
                        </form>
                        
                        <div class="btn-group">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                                <i class="bi bi-printer"></i> Print
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="exportBtn">
                                <i class="bi bi-file-earmark-excel"></i> Export
                            </button>
                        </div>
                    </div>
                </div>
                
                <?php if ($showForecast && $forecastData): ?>
                <div class="row">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">Budget Forecast for <?php echo $selectedYear; ?></h5>
                            </div>
                            <div class="card-body">
                                <canvas id="forecastChart"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card analysis-card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-graph-up-arrow"></i> AI Budget Analysis
                                </h5>
                                <span class="badge <?php echo $apiStatus === 'online' ? 'bg-success' : 'bg-secondary'; ?>">
                                    <?php echo $apiStatus === 'online' ? 'AI-Powered' : 'Statistical Analysis'; ?>
                                </span>
                            </div>
                            <div class="card-body">
                                <div class="analysis-content">
                                    <?php if (!empty($aiFeedback['feedback'])): ?>
                                        <?php 
                                        $paragraphs = explode("\n", $aiFeedback['feedback']);
                                        foreach ($paragraphs as $paragraph):
                                            if (trim($paragraph)):
                                        ?>
                                            <p class="analysis-paragraph">
                                                <?php echo htmlspecialchars($paragraph); ?>
                                            </p>
                                        <?php 
                                            endif;
                                        endforeach;
                                        ?>
                                    <?php else: ?>
                                        <p class="text-muted">
                                            <i class="bi bi-info-circle"></i>
                                            Analysis not available at this time. Please try regenerating the forecast.
                                        </p>
                                    <?php endif; ?>
                                </div>
                                
                                <?php if ($apiStatus === 'online'): ?>
                                <div class="mt-3 text-end">
                                    <small class="text-muted">
                                        <i class="bi bi-clock"></i> Analysis generated on <?php echo date('Y-m-d H:i:s'); ?>
                                    </small>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <div class="text-center mt-5">
                    <i class="bi bi-graph-up" style="font-size: 48px;"></i>
                    <h4 class="mt-3">Generate Your Budget Forecast</h4>
                    <p class="text-muted">Select a year and click "Generate Forecast" to see AI-powered budget predictions</p>
                </div>
                <?php endif; ?>
            </main>
        </div>
    </div>
    
    <div id="loadingOverlay" class="loading d-none">
        <div class="text-center">
            <div class="spinner-border text-primary mb-2" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <div>Generating AI Forecast...</div>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const yearSelector = document.getElementById('yearSelector');
            const selectedYearInput = document.getElementById('selectedYearInput');
            const forecastForm = document.getElementById('forecastForm');
            const loadingOverlay = document.getElementById('loadingOverlay');
            
            yearSelector.addEventListener('change', function() {
                selectedYearInput.value = this.value;
            });
            
            forecastForm.addEventListener('submit', function() {
                loadingOverlay.classList.remove('d-none');
            });
            
            <?php if ($showForecast && $forecastData): ?>
            const ctx = document.getElementById('forecastChart').getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: <?php echo json_encode($forecastData['months']); ?>,
                    datasets: [
                        {
                            label: 'Actual',
                            data: <?php echo json_encode($forecastData['actual']); ?>,
                            borderColor: 'rgba(54, 162, 235, 1)',
                            backgroundColor: 'rgba(54, 162, 235, 0.2)',
                            borderWidth: 2,
                            pointRadius: 4,
                            tension: 0.1
                        },
                        {
                            label: 'Forecast',
                            data: <?php echo json_encode($forecastData['forecast']); ?>,
                            borderColor: 'rgba(255, 99, 132, 1)',
                            backgroundColor: 'rgba(255, 99, 132, 0.2)',
                            borderWidth: 2,
                            pointRadius: 4,
                            tension: 0.1
                        },
                        {
                            label: 'Lower Bound',
                            data: <?php echo json_encode($forecastData['lower_bound']); ?>,
                            borderColor: 'rgba(255, 99, 132, 0.3)',
                            backgroundColor: 'transparent',
                            borderWidth: 1,
                            borderDash: [5, 5],
                            pointRadius: 0,
                            fill: false
                        },
                        {
                            label: 'Upper Bound',
                            data: <?php echo json_encode($forecastData['upper_bound']); ?>,
                            borderColor: 'rgba(255, 99, 132, 0.3)',
                            backgroundColor: 'rgba(255, 99, 132, 0.1)',
                            borderWidth: 1,
                            borderDash: [5, 5],
                            pointRadius: 0,
                            fill: '-1'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Monthly Budget Forecast with Confidence Intervals'
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            callbacks: {
                                label: function(context) {
                                    let label = context.dataset.label || '';
                                    if (label) {
                                        label += ': ';
                                    }
                                    if (context.parsed.y !== null) {
                                        label += new Intl.NumberFormat('en-US', { 
                                            style: 'currency', 
                                            currency: 'PHP' 
                                        }).format(context.parsed.y);
                                    }
                                    return label;
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return new Intl.NumberFormat('en-US', {
                                        style: 'currency',
                                        currency: 'PHP',
                                        maximumSignificantDigits: 3
                                    }).format(value);
                                }
                            }
                        }
                    }
                }
            });
            <?php endif; ?>
        });
    </script>
</body>
</html>