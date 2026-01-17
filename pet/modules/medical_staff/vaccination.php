<?php
require_once '../../config/db.php';
require_once '../../includes/auth_functions.php';
checkRole('Medical Staff');

$order_id = $_GET['order_id'] ?? '';
if (!$order_id) { header("Location: dashboard.php"); exit(); }

$stmt = $mysqli->prepare("
    SELECT vo.*, pr.petnam, vm.days_interval, vm.med_id
    FROM vaccination_orders vo
    JOIN pet_registration pr ON vo.RegNo = pr.RegNo
    JOIN vaccination_master vm ON vo.vacc_id = vm.vacc_id
    WHERE vo.vacc_order_id = ?
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if ($order['payment_status'] != 'paid') {
    echo "Error: Cannot administer vaccine for unpaid orders.";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $batch = $_POST['batch_number'];
    $admin_date = $_POST['administered_date'];
    $site = $_POST['injection_site'];

    $next_due = date('Y-m-d', strtotime($admin_date . ' + ' . $order['days_interval'] . ' days'));

    $mysqli->begin_transaction();
    try {
        $stmt_up = $mysqli->prepare("UPDATE vaccination_orders SET administered = 1, administered_date = ?, next_due_date = ?, batch_number = ?, injection_site = ?, administered_by = ? WHERE vacc_order_id = ?");
        $stmt_up->bind_param("sssssi", $admin_date, $next_due, $batch, $site, $_SESSION['user_id'], $order_id);
        $stmt_up->execute();

        $stmt_hist = $mysqli->prepare("INSERT INTO vaccination_history (RegNo, vacc_order_id, vacc_name, administered_date, next_due_date, batch_number, injection_site, administered_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt_hist->bind_param("sisssssi", $order['RegNo'], $order_id, $order['vaccine_name'], $admin_date, $next_due, $batch, $site, $_SESSION['user_id']);
        $stmt_hist->execute();

        // Update Stock if linked to inventory
        if ($order['med_id']) {
            $stmt_stock = $mysqli->prepare("UPDATE medicine_master SET stock_qty = stock_qty - 1 WHERE med_id = ?");
            $stmt_stock->bind_param("i", $order['med_id']);
            $stmt_stock->execute();
        }

        $mysqli->commit();
        header("Location: dashboard.php");
        exit();
    } catch (Exception $e) {
        $mysqli->rollback();
        echo "Error: " . $e->getMessage();
    }
}

include '../../includes/header.php';
?>

<h3>Administer Vaccination: <?php echo $order['vaccine_name']; ?></h3>
<p>Patient: <?php echo $order['petnam']; ?> (<?php echo $order['RegNo']; ?>)</p>

<form method="POST">
    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Administered Date</label>
            <input type="date" name="administered_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Batch Number</label>
            <input type="text" name="batch_number" class="form-control" required>
        </div>
        <div class="col-md-6">
            <label class="form-label">Injection Site</label>
            <input type="text" name="injection_site" class="form-control" placeholder="e.g. Left shoulder">
        </div>
        <div class="col-12">
            <button type="submit" class="btn btn-primary">Record Administration</button>
            <a href="dashboard.php" class="btn btn-secondary">Back</a>
        </div>
    </div>
</form>

<?php include '../../includes/footer.php'; ?>
