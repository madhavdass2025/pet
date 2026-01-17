<?php
require_once '../../config/db.php';
require_once '../../includes/auth_functions.php';
checkRole(['Admin', 'Accountant']);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['supplier_name'];
    $contact = $_POST['contact_person'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $address = $_POST['address'];

    $stmt = $mysqli->prepare("INSERT INTO suppliers (supplier_name, contact_person, phone, email, address) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $name, $contact, $phone, $email, $address);
    $stmt->execute();
    header("Location: suppliers.php?success=1");
    exit();
}

$suppliers = $mysqli->query("SELECT * FROM suppliers ORDER BY supplier_name ASC");

include '../../includes/header.php';
?>

<div class="d-flex justify-content-between">
    <h2>Supplier Management</h2>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSupplierModal">Add New Supplier</button>
</div>

<table class="table table-striped mt-4">
    <thead>
        <tr>
            <th>Name</th>
            <th>Contact</th>
            <th>Phone</th>
            <th>Email</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        <?php while($s = $suppliers->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($s['supplier_name']); ?></td>
                <td><?php echo htmlspecialchars($s['contact_person']); ?></td>
                <td><?php echo $s['phone']; ?></td>
                <td><?php echo $s['email']; ?></td>
                <td><?php echo $s['is_active'] ? 'Active' : 'Inactive'; ?></td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<div class="modal fade" id="addSupplierModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header"><h5>Add New Supplier</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <div class="mb-3"><label>Supplier Name</label><input type="text" name="supplier_name" class="form-control" required></div>
            <div class="mb-3"><label>Contact Person</label><input type="text" name="contact_person" class="form-control"></div>
            <div class="mb-3"><label>Phone</label><input type="text" name="phone" class="form-control"></div>
            <div class="mb-3"><label>Email</label><input type="email" name="email" class="form-control"></div>
            <div class="mb-3"><label>Address</label><textarea name="address" class="form-control"></textarea></div>
        </div>
        <div class="modal-footer"><button type="submit" class="btn btn-primary">Save Supplier</button></div>
      </form>
    </div>
  </div>
</div>

<?php include '../../includes/footer.php'; ?>
