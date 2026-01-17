<?php
require_once '../../config/db.php';
require_once '../../includes/auth_functions.php';
checkRole('Medical Staff');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $consult_id = $_POST['consult_id'];
    $charge_type = $_POST['charge_type'];
    $description = $_POST['description'];
    $amount = $_POST['amount'];
    $user_id = $_SESSION['user_id'];

    $stmt = $mysqli->prepare("INSERT INTO additional_service_charges (consult_id, charge_type, description, amount, recorded_by) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("isssi", $consult_id, $charge_type, $description, $amount, $user_id);

    if ($stmt->execute()) {
        logActivity($mysqli, "Added additional charge: $charge_type ($amount)", "additional_service_charges", $stmt->insert_id);
        header("Location: dashboard.php?success=1");
    } else {
        header("Location: dashboard.php?error=1");
    }
    exit();
}
?>
