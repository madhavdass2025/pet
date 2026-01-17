<?php
require_once '../../config/db.php';
require_once '../../includes/auth_functions.php';
checkRole('Medical Staff');

$order_id = $_GET['order_id'] ?? '';
if (!$order_id) { header("Location: dashboard.php"); exit(); }

$stmt = $mysqli->prepare("
    SELECT lo.*, pr.petnam, pr.Pettyp, pr.petsex
    FROM lab_orders lo
    JOIN pet_registration pr ON lo.RegNo = pr.RegNo
    WHERE lo.order_id = ?
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if ($order['payment_status'] != 'paid') {
    echo "Error: Cannot enter results for unpaid lab tests.";
    exit();
}

// Fetch parameters for this test
$stmt_param = $mysqli->prepare("SELECT * FROM lab_parameters WHERE test_id = ?");
$stmt_param->bind_param("i", $order['test_id']);
$stmt_param->execute();
$params = $stmt_param->get_result();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $param_ids = $_POST['param_id'] ?? [];
    $values = $_POST['value'] ?? [];

    foreach ($param_ids as $index => $pid) {
        $val = $values[$index];
        $stmt_res = $mysqli->prepare("INSERT INTO lab_results (order_id, param_id, RegNo, result_value, tested_at, tested_by) VALUES (?, ?, ?, ?, NOW(), ?)");
        $stmt_res->bind_param("iisdi", $order_id, $pid, $order['RegNo'], $val, $_SESSION['user_id']);
        $stmt_res->execute();
    }

    $stmt_up_lo = $mysqli->prepare("UPDATE lab_orders SET test_status = 'completed' WHERE order_id = ?");
    $stmt_up_lo->bind_param("i", $order_id);
    $stmt_up_lo->execute();

    header("Location: dashboard.php");
    exit();
}

include '../../includes/header.php';
?>

<h3>Lab Result Entry: <?php echo $order['test_name']; ?></h3>
<p>Patient: <?php echo $order['petnam']; ?> (<?php echo $order['Pettyp']; ?> / <?php echo $order['petsex']; ?>)</p>

<form method="POST">
    <table class="table">
        <thead>
            <tr>
                <th>Parameter</th>
                <th>Result</th>
                <th>Unit</th>
                <th>Normal Range</th>
            </tr>
        </thead>
        <tbody>
            <?php if ($params->num_rows > 0): ?>
                <?php while($p = $params->fetch_assoc()): ?>
                    <tr id="row_<?php echo $p['param_id']; ?>">
                        <td><?php echo $p['parameter_name']; ?></td>
                        <td>
                            <input type="hidden" name="param_id[]" value="<?php echo $p['param_id']; ?>">
                            <input type="number" step="0.0001" name="value[]" class="form-control"
                                   onchange="checkAbnormal(this, <?php echo $p['min_value'] ?: 'null'; ?>, <?php echo $p['max_value'] ?: 'null'; ?>)" required>
                        </td>
                        <td><?php echo $p['unit']; ?></td>
                        <td><?php echo $p['min_value'] . ' - ' . $p['max_value']; ?></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4">
                        <div class="alert alert-warning">No parameters defined for this test in Master Data. Adding a generic result field.</div>
                        <input type="hidden" name="param_id[]" value="0">
                        <input type="text" name="value[]" class="form-control" placeholder="Enter Result Value/Notes" required>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
    <button type="submit" class="btn btn-primary">Submit Results</button>
    <a href="dashboard.php" class="btn btn-secondary">Back</a>
</form>

<script>
function checkAbnormal(input, min, max) {
    let val = parseFloat(input.value);
    let row = input.closest('tr');
    if ((min !== null && val < min) || (max !== null && val > max)) {
        row.classList.add('table-danger');
    } else {
        row.classList.remove('table-danger');
    }
}
</script>

<?php include '../../includes/footer.php'; ?>
