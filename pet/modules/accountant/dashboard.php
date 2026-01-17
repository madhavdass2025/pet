<?php
require_once '../../config/db.php';
require_once '../../includes/auth_functions.php';
checkRole('Accountant');
include '../../includes/header.php';
?>

<h2>Accountant Dashboard</h2>

<div class="row mt-4">
    <div class="col-md-6">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title">Financial Reports</h5>
                <p class="card-text">View daily collections and revenue analysis.</p>
                <a href="../front_desk/reports.php" class="btn btn-primary">Go to Reports</a>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title">Credit Management</h5>
                <p class="card-text">Track outstanding balances and payments.</p>
                <a href="../front_desk/reports.php" class="btn btn-success">Manage Credits</a>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
