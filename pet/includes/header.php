<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pet Clinic Management System</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="/pet/assets/style.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">Pet Clinic</a>
        <button class="navbar-expand-lg navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <?php if (isset($_SESSION['role'])): ?>
                    <?php if ($_SESSION['role'] == 'Front Desk'): ?>
                        <li class="nav-item"><a class="nav-link" href="/pet/modules/front_desk/dashboard.php">Dashboard</a></li>
                        <li class="nav-item"><a class="nav-link" href="/pet/modules/front_desk/registration.php">Pet Registration</a></li>
                    <?php elseif ($_SESSION['role'] == 'Doctor'): ?>
                        <li class="nav-item"><a class="nav-link" href="/pet/modules/doctor/dashboard.php">Dashboard</a></li>
                    <?php elseif ($_SESSION['role'] == 'Medical Staff'): ?>
                        <li class="nav-item"><a class="nav-link" href="/pet/modules/medical_staff/dashboard.php">Dashboard</a></li>
                    <?php elseif ($_SESSION['role'] == 'Admin'): ?>
                        <li class="nav-item"><a class="nav-link" href="/pet/modules/admin/dashboard.php">Admin Panel</a></li>
                    <?php endif; ?>
                <?php endif; ?>
            </ul>
            <?php if (isset($_SESSION['full_name'])): ?>
                <span class="navbar-text me-3">
                    Welcome, <?php echo $_SESSION['full_name']; ?> (<?php echo $_SESSION['role']; ?>)
                </span>
                <a href="/pet/logout.php" class="btn btn-outline-light btn-sm">Logout</a>
            <?php endif; ?>
        </div>
    </div>
</nav>
<div class="container mt-4">
