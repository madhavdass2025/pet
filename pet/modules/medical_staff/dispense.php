<?php
require_once '../../config/db.php';
require_once '../../includes/auth_functions.php';
checkRole('Medical Staff');

$consult_id = $_GET['consult_id'] ?? '';
if (!$consult_id) { header("Location: dashboard.php"); exit(); }

$stmt = $mysqli->prepare("
    SELECT p.*, m.unit_price, m.stock_qty, pr.petnam, pr.RegNo
    FROM prescriptions p
    JOIN medicine_master m ON p.med_id = m.med_id
    JOIN pet_registration pr ON p.RegNo = pr.RegNo
    WHERE p.consult_id = ?
");
$stmt->bind_param("i", $consult_id);
$stmt->execute();
$prescriptions = $stmt->get_result();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $mysqli->begin_transaction();
    try {
        $prescription_ids = $_POST['prescription_id'];
        $dispensed_qtys = $_POST['dispensed_qty'];

        foreach ($prescription_ids as $index => $pid) {
            $qty = $dispensed_qtys[$index];
            if ($qty <= 0) continue;

            // Get original prescription and medicine info
            $stmt_get_p = $mysqli->prepare("SELECT p.*, m.unit_price FROM prescriptions p JOIN medicine_master m ON p.med_id = m.med_id WHERE p.prescription_id = ?");
            $stmt_get_p->bind_param("i", $pid);
            $stmt_get_p->execute();
            $p_info = $stmt_get_p->get_result()->fetch_assoc();

            $total_amount = $qty * $p_info['unit_price'];

            $stmt_disp = $mysqli->prepare("INSERT INTO medicine_dispensing (prescription_id, consult_id, RegNo, med_id, prescribed_qty, dispensed_qty, unit_price, total_amount, dispensed_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt_disp->bind_param("iissiiddi", $pid, $consult_id, $p_info['RegNo'], $p_info['med_id'], $p_info['quantity'], $qty, $p_info['unit_price'], $total_amount, $_SESSION['user_id']);
            $stmt_disp->execute();

            // Update stock
            $stmt_up_stock = $mysqli->prepare("UPDATE medicine_master SET stock_qty = stock_qty - ? WHERE med_id = ?");
            $stmt_up_stock->bind_param("ii", $qty, $p_info['med_id']);
            $stmt_up_stock->execute();
        }

        $mysqli->commit();
        echo "<script>alert('Medication Dispensed Successfully'); window.location.href='dashboard.php';</script>";
        exit();
    } catch (Exception $e) {
        $mysqli->rollback();
        echo "Error: " . $e->getMessage();
    }
}

include '../../includes/header.php';
?>

<h3>Dispense Medication</h3>
<p>Consultation ID: <?php echo $consult_id; ?></p>

<form method="POST">
    <table class="table">
        <thead>
            <tr>
                <th>Medicine</th>
                <th>Prescribed Qty</th>
                <th>Stock</th>
                <th>Unit Price</th>
                <th>Dispense Qty</th>
            </tr>
        </thead>
        <tbody>
            <?php while($p = $prescriptions->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $p['medicine_name']; ?></td>
                    <td><?php echo $p['quantity']; ?></td>
                    <td><?php echo $p['stock_qty']; ?></td>
                    <td><?php echo $p['unit_price']; ?></td>
                    <td>
                        <input type="hidden" name="prescription_id[]" value="<?php echo $p['prescription_id']; ?>">
                        <input type="number" name="dispensed_qty[]" class="form-control" max="<?php echo $p['quantity']; ?>" value="<?php echo $p['quantity']; ?>" required>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
    <button type="submit" class="btn btn-primary">Confirm Dispensing</button>
    <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
</form>

<?php include '../../includes/footer.php'; ?>
