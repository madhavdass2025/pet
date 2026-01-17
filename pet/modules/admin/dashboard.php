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
                <h5 class="card-title">Lab Master</h5>
                <p class="card-text">Configure lab tests and parameters.</p>
                <a href="#" class="btn btn-info">Manage</a>
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
</div>

<?php include '../../includes/footer.php'; ?>
