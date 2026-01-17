<?php
require_once '../../config/db.php';
require_once '../../includes/auth_functions.php';
checkRole('Admin');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['vacc_name'];
    $type = $_POST['pet_type'];
    $interval = $_POST['days_interval'];

    $stmt = $mysqli->prepare("INSERT INTO vaccination_master (vacc_name, pet_type, dosage_number, days_interval) VALUES (?, ?, 1, ?)");
    $stmt->bind_param("ssi", $name, $type, $interval);
    $stmt->execute();
    header("Location: vaccination_master.php?success=1");
    exit();
}

$vaccines = $mysqli->query("SELECT * FROM vaccination_master ORDER BY vacc_name ASC");

include '../../includes/header.php';
?>

<div class="d-flex justify-content-between">
    <h2>Vaccination Master</h2>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addVaccModal">Add New Vaccine</button>
</div>

<table class="table table-striped mt-4">
    <thead>
        <tr>
            <th>Name</th>
            <th>Pet Type</th>
            <th>Interval (Days)</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        <?php while($v = $vaccines->fetch_assoc()): ?>
            <tr>
                <td><?php echo $v['vacc_name']; ?></td>
                <td><?php echo $v['pet_type']; ?></td>
                <td><?php echo $v['days_interval']; ?></td>
                <td><?php echo $v['is_active'] ? 'Active' : 'Inactive'; ?></td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<div class="modal fade" id="addVaccModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST">
        <div class="modal-header"><h5>Add New Vaccine</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <div class="mb-3"><label>Vaccine Name</label><input type="text" name="vacc_name" class="form-control" required></div>
            <div class="mb-3"><label>Pet Type</label><input type="text" name="pet_type" class="form-control" placeholder="Dog, Cat, etc." required></div>
            <div class="mb-3"><label>Days Interval</label><input type="number" name="days_interval" class="form-control" placeholder="e.g. 21" required></div>
        </div>
        <div class="modal-footer"><button type="submit" class="btn btn-primary">Save Vaccine</button></div>
      </form>
    </div>
  </div>
</div>

<?php include '../../includes/footer.php'; ?>
