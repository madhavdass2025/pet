<?php
require_once '../../config/db.php';
require_once '../../includes/auth_functions.php';
checkRole(['Admin', 'Accountant', 'Front Desk']);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $mysqli->begin_transaction();
    try {
        $payment_id = $_POST['payment_id'];
        $return_date = $_POST['return_date'];
        $reason = $_POST['reason'];
        $refund = $_POST['total_refund'];
        $user_id = $_SESSION['user_id'];

        $stmt = $mysqli->prepare("INSERT INTO sales_returns (payment_id, return_date, reason, total_refund, created_by) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issdi", $payment_id, $return_date, $reason, $refund, $user_id);
        $stmt->execute();

        $mysqli->commit();
        header("Location: sales_returns.php?success=1");
        exit();
    } catch (Exception $e) {
        $mysqli->rollback();
        echo "Error: " . $e->getMessage();
    }
}

$returns = $mysqli->query("
    SELECT sr.*, pt.receipt_no, pt.RegNo
    FROM sales_returns sr
    JOIN payment_transactions pt ON sr.payment_id = pt.payment_id
    ORDER BY sr.return_date DESC
");

include '../../includes/header.php';
?>

<div class="d-flex justify-content-between">
    <h2>Sales Returns</h2>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addReturnModal">Record Return</button>
</div>

<table class="table table-striped mt-4">
    <thead>
        <tr>
            <th>Return ID</th>
            <th>Receipt No</th>
            <th>RegNo</th>
            <th>Date</th>
            <th>Refund</th>
            <th>Reason</th>
        </tr>
    </thead>
    <tbody>
        <?php while($r = $returns->fetch_assoc()): ?>
            <tr>
                <td><?php echo $r['return_id']; ?></td>
                <td><?php echo $r['receipt_no']; ?></td>
                <td><?php echo $r['RegNo']; ?></td>
                <td><?php echo $r['return_date']; ?></td>
                <td><?php echo $r['total_refund']; ?></td>
                <td><?php echo htmlspecialchars($r['reason']); ?></td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<div class="modal fade" id="addReturnModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header"><h5>Record Sales Return</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <div class="mb-3">
                <label>Receipt No</label>
                <select name="payment_id" class="form-select" required>
                    <?php
                    $pts = $mysqli->query("SELECT payment_id, receipt_no, total_amount FROM payment_transactions ORDER BY transaction_date DESC LIMIT 50");
                    while($pt = $pts->fetch_assoc()): ?>
                        <option value="<?php echo $pt['payment_id']; ?>"><?php echo $pt['receipt_no']; ?> (<?php echo $pt['total_amount']; ?>)</option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="mb-3"><label>Return Date</label><input type="date" name="return_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required></div>
            <div class="mb-3"><label>Refund Amount</label><input type="number" step="0.01" name="total_refund" class="form-control" required></div>
            <div class="mb-3"><label>Reason</label><textarea name="reason" class="form-control"></textarea></div>
        </div>
        <div class="modal-footer"><button type="submit" class="btn btn-primary">Save Return</button></div>
      </form>
    </div>
  </div>
</div>

<?php include '../../includes/footer.php'; ?>
