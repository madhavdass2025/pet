<?php
require_once '../../config/db.php';
require_once '../../includes/auth_functions.php';
checkRole('Accountant');
include '../../includes/header.php';
?>

<h2>Accountant Dashboard</h2>

<div class="row mt-4">
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title">Financial Reports</h5>
                <p class="card-text">Daily collections and reporting.</p>
                <a href="../front_desk/reports.php" class="btn btn-primary">Daily Reports</a>
                <a href="../admin/reports_detailed.php" class="btn btn-info mt-2">Detailed Reports</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title">Credit Management</h5>
                <p class="card-text">Track outstanding balances and payments.</p>
                <a href="../front_desk/reports.php" class="btn btn-success">Manage Credits</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title">Inventory & Returns</h5>
                <p class="card-text">Manage suppliers, POs and returns.</p>
                <a href="../admin/purchase_orders.php" class="btn btn-dark">Purchase Orders</a>
                <a href="../admin/sales_returns.php" class="btn btn-warning mt-2">Sales Returns</a>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
