<?php
require_once '../../config/db.php';
require_once '../../includes/auth_functions.php';
checkRole('Front Desk');
include '../../includes/header.php';
?>

<div class="row">
    <div class="col-md-12">
        <h2>Front Desk Dashboard</h2>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title">Pet Registration</h5>
                <p class="card-text">Register a new pet and owner.</p>
                <a href="registration.php" class="btn btn-primary">Go to Registration</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title">Book Consultation</h5>
                <p class="card-text">Schedule an appointment with a doctor.</p>
                <a href="booking.php" class="btn btn-success">Book Appointment</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-center">
            <div class="card-body">
                <h5 class="card-title">Daily Reports</h5>
                <p class="card-text">View collections and today's status.</p>
                <a href="reports.php" class="btn btn-info">View Reports</a>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
