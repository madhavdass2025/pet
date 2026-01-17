<?php
require_once '../../config/db.php';
require_once '../../includes/auth_functions.php';
checkRole('Admin');
include '../../includes/header.php';
?>

<h2>Admin Dashboard</h2>

<div class="row mt-4">
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title">Lab Master</h5>
                <p class="card-text">Configure lab tests and parameters.</p>
                <a href="lab_master.php" class="btn btn-outline-info">Manage</a>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title">Fee Master</h5>
                <p class="card-text">Manage registration and consultation fees.</p>
                <a href="fee_master.php" class="btn btn-primary">Manage</a>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title">Medicine Master</h5>
                <p class="card-text">Manage pharmacy inventory and pricing.</p>
                <a href="medicine_master.php" class="btn btn-success">Manage</a>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title">Vaccination Master</h5>
                <p class="card-text">Manage vaccines and intervals.</p>
                <a href="vaccination_master.php" class="btn btn-info">Manage</a>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title">User Management</h5>
                <p class="card-text">Create and manage staff accounts.</p>
                <a href="#" class="btn btn-dark">Manage</a>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title">Detailed Reports</h5>
                <p class="card-text">Full financial and inventory analysis.</p>
                <a href="reports_detailed.php" class="btn btn-outline-info">View</a>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title">Purchase Returns</h5>
                <p class="card-text">Manage stock returns.</p>
                <a href="purchase_returns.php" class="btn btn-outline-danger">Manage</a>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title">Sales Returns</h5>
                <p class="card-text">Manage customer returns.</p>
                <a href="sales_returns.php" class="btn btn-outline-warning">Manage</a>
            </div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title">Suppliers</h5>
                <p class="card-text">Manage inventory suppliers.</p>
                <a href="suppliers.php" class="btn btn-outline-primary">Manage</a>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title">Purchase Orders</h5>
                <p class="card-text">Order stock from suppliers.</p>
                <a href="purchase_orders.php" class="btn btn-outline-success">Manage</a>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
