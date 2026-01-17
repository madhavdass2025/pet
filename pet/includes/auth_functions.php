<?php
session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function checkRole($roles) {
    if (!isLoggedIn()) {
        header("Location: /pet/index.php");
        exit();
    }

    if (!in_array($_SESSION['role'], (array)$roles)) {
        echo "Access Denied: You do not have permission to access this page.";
        exit();
    }
}

function logActivity($mysqli, $action, $table_name = null, $record_id = null, $old_val = null, $new_val = null) {
    $user_id = $_SESSION['user_id'] ?? null;
    $stmt = $mysqli->prepare("INSERT INTO audit_log (user_id, action, table_name, record_id, old_value, new_value) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ississ", $user_id, $action, $table_name, $record_id, $old_val, $new_val);
    $stmt->execute();
}

function redirectByRole($role) {
    switch ($role) {
        case 'Admin':
            header("Location: /pet/modules/admin/dashboard.php");
            break;
        case 'Front Desk':
            header("Location: /pet/modules/front_desk/dashboard.php");
            break;
        case 'Doctor':
            header("Location: /pet/modules/doctor/dashboard.php");
            break;
        case 'Medical Staff':
            header("Location: /pet/modules/medical_staff/dashboard.php");
            break;
        case 'Accountant':
            header("Location: /pet/modules/accountant/dashboard.php");
            break;
        default:
            header("Location: /pet/index.php");
            break;
    }
    exit();
}
?>
