<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?>Financial Management System</title>

<!-- Make sure paths are correct for XAMPP -->
<link rel="stylesheet" href="<?php echo rtrim(dirname($_SERVER['PHP_SELF']), '/') . '/'; ?>assets/css/bootstrap.min.css">
<link rel="stylesheet" href="<?php echo rtrim(dirname($_SERVER['PHP_SELF']), '/') . '/'; ?>assets/css/style.css">
<link rel="icon" href="<?php echo rtrim(dirname($_SERVER['PHP_SELF']), '/') . '/'; ?>assets/img/favicon.ico" type="image/x-icon">

<!-- Add Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
<!-- Add Chart.js for forecasting -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="<?php echo rtrim(dirname($_SERVER['PHP_SELF']), '/') . '/'; ?>assets/js/jquery.min.js"></script>
<script src="<?php echo rtrim(dirname($_SERVER['PHP_SELF']), '/') . '/'; ?>assets/js/bootstrap.bundle.min.js"></script>
