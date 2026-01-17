<?php
require_once '../../config/db.php';
require_once '../../includes/auth_functions.php';
checkRole('Admin');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $type = $_POST['fee_type'];
    $amount = $_POST['fee_amount'];
    $from = $_POST['effective_from'];

    $stmt = $mysqli->prepare("INSERT INTO fee_master (fee_type, fee_amount, effective_from) VALUES (?, ?, ?)");
    $stmt->bind_param("sds", $type, $amount, $from);
    $stmt->execute();
    header("Location: fee_master.php?success=1");
    exit();
}

$fees = $mysqli->query("SELECT * FROM fee_master ORDER BY effective_from DESC");

include '../../includes/header.php';
?>

<div class="d-flex justify-content-between">
    <h2>Fee Master</h2>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addFeeModal">Add New Fee</button>
</div>

<table class="table table-striped mt-4">
    <thead>
        <tr>
            <th>Type</th>
            <th>Amount</th>
            <th>Effective From</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        <?php while($f = $fees->fetch_assoc()): ?>
            <tr>
                <td><?php echo $f['fee_type']; ?></td>
                <td><?php echo $f['fee_amount']; ?></td>
                <td><?php echo $f['effective_from']; ?></td>
                <td><?php echo $f['is_active'] ? 'Active' : 'Inactive'; ?></td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<!-- Add Fee Modal -->
<div class="modal fade" id="addFeeModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header"><h5>Add New Fee</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <div class="mb-3">
                <label>Fee Type</label>
                <select name="fee_type" class="form-select" required>
                    <option value="registration">Registration</option>
                    <option value="consultation">Consultation</option>
                    <option value="vaccination">Vaccination</option>
                    <option value="other">Other</option>
                </select>
            </div>
            <div class="mb-3"><label>Amount</label><input type="number" step="0.01" name="fee_amount" class="form-control" required></div>
            <div class="mb-3"><label>Effective From</label><input type="date" name="effective_from" class="form-control" value="<?php echo date('Y-m-d'); ?>" required></div>
        </div>
        <div class="modal-footer"><button type="submit" class="btn btn-primary">Save Fee</button></div>
      </form>
    </div>
  </div>
</div>

<?php include '../../includes/footer.php'; ?>
