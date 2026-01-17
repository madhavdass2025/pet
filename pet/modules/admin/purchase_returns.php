<?php
require_once '../../config/db.php';
require_once '../../includes/auth_functions.php';
checkRole(['Admin', 'Accountant']);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $mysqli->begin_transaction();
    try {
        $po_id = $_POST['po_id'];
        $return_date = $_POST['return_date'];
        $reason = $_POST['reason'];
        $refund = $_POST['total_refund'];
        $user_id = $_SESSION['user_id'];

        $stmt = $mysqli->prepare("INSERT INTO purchase_returns (po_id, return_date, reason, total_refund, created_by) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issdi", $po_id, $return_date, $reason, $refund, $user_id);
        $stmt->execute();

        // In a real system, you'd specify WHICH items are returned and deduct stock.
        // For simplicity, we'll just record the return here.

        $mysqli->commit();
        header("Location: purchase_returns.php?success=1");
        exit();
    } catch (Exception $e) {
        $mysqli->rollback();
        echo "Error: " . $e->getMessage();
    }
}

$returns = $mysqli->query("
    SELECT pr.*, s.supplier_name
    FROM purchase_returns pr
    JOIN purchase_orders po ON pr.po_id = po.po_id
    JOIN suppliers s ON po.supplier_id = s.supplier_id
    ORDER BY pr.return_date DESC
");

include '../../includes/header.php';
?>

<div class="d-flex justify-content-between">
    <h2>Purchase Returns</h2>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addReturnModal">Record Return</button>
</div>

<table class="table table-striped mt-4">
    <thead>
        <tr>
            <th>Return ID</th>
            <th>PO ID</th>
            <th>Supplier</th>
            <th>Date</th>
            <th>Refund</th>
            <th>Reason</th>
        </tr>
    </thead>
    <tbody>
        <?php while($r = $returns->fetch_assoc()): ?>
            <tr>
                <td><?php echo $r['return_id']; ?></td>
                <td><?php echo $r['po_id']; ?></td>
                <td><?php echo htmlspecialchars($r['supplier_name']); ?></td>
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
        <div class="modal-header"><h5>Record Purchase Return</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <div class="mb-3">
                <label>Purchase Order</label>
                <select name="po_id" class="form-select" required>
                    <?php
                    $pos = $mysqli->query("SELECT po_id, order_date FROM purchase_orders WHERE status = 'received'");
                    while($p = $pos->fetch_assoc()): ?>
                        <option value="<?php echo $p['po_id']; ?>">PO #<?php echo $p['po_id']; ?> (<?php echo $p['order_date']; ?>)</option>
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
