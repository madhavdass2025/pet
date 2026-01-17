<?php
require_once '../../config/db.php';
require_once '../../includes/auth_functions.php';
checkRole('Medical Staff');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $consult_id = $_POST['consult_id'];
    $charge_type = $_POST['charge_type'];
    $description = $_POST['description'];
    $amount = $_POST['amount'];
    $med_id = $_POST['med_id'] ?: null;
    $quantity = $_POST['quantity'] ?: 1;
    $user_id = $_SESSION['user_id'];

    $mysqli->begin_transaction();
    try {
        $stmt = $mysqli->prepare("INSERT INTO additional_service_charges (consult_id, charge_type, med_id, quantity, description, amount, recorded_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("isiisdi", $consult_id, $charge_type, $med_id, $quantity, $description, $amount, $user_id);
        $stmt->execute();
        $charge_id = $stmt->insert_id;

        if ($med_id) {
            $stmt_stock = $mysqli->prepare("UPDATE medicine_master SET stock_qty = stock_qty - ? WHERE med_id = ?");
            $stmt_stock->bind_param("ii", $quantity, $med_id);
            $stmt_stock->execute();
        }

        logActivity($mysqli, "Added additional charge: $charge_type ($amount)", "additional_service_charges", $charge_id);
        $mysqli->commit();
        header("Location: dashboard.php?success=1");
    } catch (Exception $e) {
        $mysqli->rollback();
        header("Location: dashboard.php?error=1");
    }
    exit();
}
?>
