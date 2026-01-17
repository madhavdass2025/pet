<?php
require_once '../../config/db.php';
require_once '../../includes/auth_functions.php';
checkRole(['Admin', 'Accountant']);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'create_po') {
    $mysqli->begin_transaction();
    try {
        $supplier_id = $_POST['supplier_id'];
        $order_date = $_POST['order_date'];
        $created_by = $_SESSION['user_id'];

        $stmt = $mysqli->prepare("INSERT INTO purchase_orders (supplier_id, order_date, created_by) VALUES (?, ?, ?)");
        $stmt->bind_param("isi", $supplier_id, $order_date, $created_by);
        $stmt->execute();
        $po_id = $stmt->insert_id;

        $med_ids = $_POST['med_id'];
        $quantities = $_POST['quantity'];
        $costs = $_POST['unit_cost'];
        $total_amount = 0;

        $stmt_item = $mysqli->prepare("INSERT INTO purchase_items (po_id, med_id, quantity, unit_cost) VALUES (?, ?, ?, ?)");
        foreach ($med_ids as $i => $mid) {
            $qty = $quantities[$i];
            $cost = $costs[$i];
            $total_amount += ($qty * $cost);
            $stmt_item->bind_param("iiid", $po_id, $mid, $qty, $cost);
            $stmt_item->execute();
        }

        $mysqli->query("UPDATE purchase_orders SET total_amount = $total_amount WHERE po_id = $po_id");
        $mysqli->commit();
        header("Location: purchase_orders.php?success=1");
        exit();
    } catch (Exception $e) {
        $mysqli->rollback();
        echo "Error: " . $e->getMessage();
    }
}

if (isset($_GET['receive_po'])) {
    $po_id = $_GET['receive_po'];
    $mysqli->begin_transaction();
    try {
        $items = $mysqli->query("SELECT * FROM purchase_items WHERE po_id = $po_id");
        while($item = $items->fetch_assoc()){
            $mysqli->query("UPDATE medicine_master SET stock_qty = stock_qty + {$item['quantity']} WHERE med_id = {$item['med_id']}");
        }
        $mysqli->query("UPDATE purchase_orders SET status = 'received' WHERE po_id = $po_id");
        $mysqli->commit();
        header("Location: purchase_orders.php?received=1");
        exit();
    } catch (Exception $e) {
        $mysqli->rollback();
        echo "Error: " . $e->getMessage();
    }
}

$pos = $mysqli->query("
    SELECT po.*, s.supplier_name, u.username
    FROM purchase_orders po
    JOIN suppliers s ON po.supplier_id = s.supplier_id
    JOIN users u ON po.created_by = u.user_id
    ORDER BY po.order_date DESC
");

$suppliers = $mysqli->query("SELECT * FROM suppliers WHERE is_active = 1");
$medicines = $mysqli->query("SELECT * FROM medicine_master WHERE is_active = 1");

include '../../includes/header.php';
?>

<div class="d-flex justify-content-between">
    <h2>Purchase Orders</h2>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPOModal">Create New PO</button>
</div>

<table class="table table-striped mt-4">
    <thead>
        <tr>
            <th>PO ID</th>
            <th>Supplier</th>
            <th>Date</th>
            <th>Total Amount</th>
            <th>Status</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php while($p = $pos->fetch_assoc()): ?>
            <tr>
                <td><?php echo $p['po_id']; ?></td>
                <td><?php echo htmlspecialchars($p['supplier_name']); ?></td>
                <td><?php echo $p['order_date']; ?></td>
                <td><?php echo $p['total_amount']; ?></td>
                <td>
                    <span class="badge bg-<?php echo ($p['status'] == 'received') ? 'success' : 'primary'; ?>">
                        <?php echo ucfirst($p['status']); ?>
                    </span>
                </td>
                <td>
                    <?php if ($p['status'] == 'ordered'): ?>
                        <a href="?receive_po=<?php echo $p['po_id']; ?>" class="btn btn-sm btn-success" onclick="return confirm('Mark as received and update stock?')">Receive</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<div class="modal fade" id="addPOModal" tabindex="-1">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <form method="POST">
        <input type="hidden" name="action" value="create_po">
        <div class="modal-header"><h5>Create Purchase Order</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label>Supplier</label>
                    <select name="supplier_id" class="form-select" required>
                        <?php while($s = $suppliers->fetch_assoc()): ?>
                            <option value="<?php echo $s['supplier_id']; ?>"><?php echo $s['supplier_name']; ?></option>
                        <?php endwhile; $suppliers->data_seek(0); ?>
                    </select>
                </div>
                <div class="col-md-6">
                    <label>Order Date</label>
                    <input type="date" name="order_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
            </div>
            <h6>Items</h6>
            <div id="poItems">
                <div class="row mb-2 po-item">
                    <div class="col-md-5">
                        <select name="med_id[]" class="form-select" required>
                            <?php while($m = $medicines->fetch_assoc()): ?>
                                <option value="<?php echo $m['med_id']; ?>"><?php echo $m['med_name']; ?></option>
                            <?php endwhile; $medicines->data_seek(0); ?>
                        </select>
                    </div>
                    <div class="col-md-3"><input type="number" name="quantity[]" class="form-control" placeholder="Qty" required></div>
                    <div class="col-md-3"><input type="number" step="0.01" name="unit_cost[]" class="form-control" placeholder="Cost" required></div>
                    <div class="col-md-1"><button type="button" class="btn btn-danger btn-sm remove-item">x</button></div>
                </div>
            </div>
            <button type="button" class="btn btn-secondary btn-sm" id="addItem">Add More Item</button>
        </div>
        <div class="modal-footer"><button type="submit" class="btn btn-primary">Create PO</button></div>
      </form>
    </div>
  </div>
</div>

<script>
document.getElementById('addItem').addEventListener('click', function() {
    let container = document.getElementById('poItems');
    let newItem = container.querySelector('.po-item').cloneNode(true);
    newItem.querySelectorAll('input').forEach(i => i.value = '');
    container.appendChild(newItem);
});
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('remove-item')) {
        if (document.querySelectorAll('.po-item').length > 1) {
            e.target.closest('.po-item').remove();
        }
    }
});
</script>

<?php include '../../includes/footer.php'; ?>
