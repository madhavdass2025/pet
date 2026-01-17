<?php
require_once '../../config/db.php';
require_once '../../includes/auth_functions.php';
checkRole('Admin');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $med_name = $_POST['med_name'];
    $category = $_POST['med_category'];
    $form = $_POST['dosage_form'];
    $strength = $_POST['strength'];
    $price = $_POST['unit_price'];
    $stock = $_POST['stock_qty'];
    $reorder = $_POST['reorder_level'];

    $stmt = $mysqli->prepare("INSERT INTO medicine_master (med_name, med_category, dosage_form, strength, unit_price, stock_qty, reorder_level) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssdid", $med_name, $category, $form, $strength, $price, $stock, $reorder);
    $stmt->execute();
    header("Location: medicine_master.php?success=1");
    exit();
}

$medicines = $mysqli->query("SELECT * FROM medicine_master ORDER BY med_name ASC");

include '../../includes/header.php';
?>

<div class="d-flex justify-content-between">
    <h2>Medicine Master</h2>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMedModal">Add New Medicine</button>
</div>

<table class="table table-striped mt-4">
    <thead>
        <tr>
            <th>Name</th>
            <th>Category</th>
            <th>Strength</th>
            <th>Price</th>
            <th>Stock</th>
            <th>Reorder Level</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        <?php while($m = $medicines->fetch_assoc()): ?>
            <tr>
                <td><?php echo $m['med_name']; ?></td>
                <td><?php echo $m['med_category']; ?></td>
                <td><?php echo $m['strength']; ?></td>
                <td><?php echo $m['unit_price']; ?></td>
                <td><?php echo $m['stock_qty']; ?></td>
                <td><?php echo $m['reorder_level']; ?></td>
                <td><?php echo $m['is_active'] ? 'Active' : 'Inactive'; ?></td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<!-- Add Medicine Modal -->
<div class="modal fade" id="addMedModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header"><h5>Add New Medicine</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <div class="mb-2"><label>Medicine Name</label><input type="text" name="med_name" class="form-control" required></div>
            <div class="mb-2"><label>Category</label><input type="text" name="med_category" class="form-control"></div>
            <div class="mb-2"><label>Dosage Form</label><input type="text" name="dosage_form" class="form-control"></div>
            <div class="mb-2"><label>Strength</label><input type="text" name="strength" class="form-control"></div>
            <div class="mb-2"><label>Unit Price</label><input type="number" step="0.01" name="unit_price" class="form-control" required></div>
            <div class="mb-2"><label>Initial Stock</label><input type="number" name="stock_qty" class="form-control" value="0"></div>
            <div class="mb-2"><label>Reorder Level</label><input type="number" name="reorder_level" class="form-control" value="10"></div>
        </div>
        <div class="modal-footer"><button type="submit" class="btn btn-primary">Save Medicine</button></div>
      </form>
    </div>
  </div>
</div>

<?php include '../../includes/footer.php'; ?>
