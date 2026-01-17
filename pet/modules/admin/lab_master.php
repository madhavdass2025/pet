<?php
require_once '../../config/db.php';
require_once '../../includes/auth_functions.php';
checkRole('Admin');

// Add Category
if (isset($_POST['add_category'])) {
    $name = $_POST['category_name'];
    $stmt = $mysqli->prepare("INSERT INTO lab_category (category_name) VALUES (?)");
    $stmt->bind_param("s", $name);
    $stmt->execute();
}

// Add Test
if (isset($_POST['add_test'])) {
    $cat_id = $_POST['cat_id'];
    $name = $_POST['test_name'];
    $fee = $_POST['fee'];
    $stmt = $mysqli->prepare("INSERT INTO lab_test_master (cat_id, test_name, fee) VALUES (?, ?, ?)");
    $stmt->bind_param("isd", $cat_id, $name, $fee);
    $stmt->execute();
}

// Add Parameter
if (isset($_POST['add_param'])) {
    $test_id = $_POST['test_id'];
    $name = $_POST['parameter_name'];
    $unit = $_POST['unit'];
    $min = $_POST['min_value'];
    $max = $_POST['max_value'];
    $stmt = $mysqli->prepare("INSERT INTO lab_parameters (test_id, parameter_name, unit, min_value, max_value) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("issdd", $test_id, $name, $unit, $min, $max);
    $stmt->execute();
}

$categories = $mysqli->query("SELECT * FROM lab_category");
$tests = $mysqli->query("SELECT t.*, c.category_name FROM lab_test_master t JOIN lab_category c ON t.cat_id = c.cat_id");
$params = $mysqli->query("SELECT p.*, t.test_name FROM lab_parameters p JOIN lab_test_master t ON p.test_id = t.test_id");

include '../../includes/header.php';
?>

<h2>Laboratory Master</h2>

<div class="row">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">Add Lab Category</div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="add_category" value="1">
                    <div class="mb-2"><label>Category Name</label><input type="text" name="category_name" class="form-control" required></div>
                    <button type="submit" class="btn btn-sm btn-primary">Add Category</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">Add Lab Test (Category with Amount)</div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="add_test" value="1">
                    <div class="mb-2">
                        <label>Category</label>
                        <select name="cat_id" class="form-select" required>
                            <?php while($c = $categories->fetch_assoc()): ?>
                                <option value="<?php echo $c['cat_id']; ?>"><?php echo $c['category_name']; ?></option>
                            <?php endwhile; $categories->data_seek(0); ?>
                        </select>
                    </div>
                    <div class="mb-2"><label>Test Name</label><input type="text" name="test_name" class="form-control" required></div>
                    <div class="mb-2"><label>Fee</label><input type="number" step="0.01" name="fee" class="form-control" required></div>
                    <button type="submit" class="btn btn-sm btn-success">Add Test</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">Add Lab Parameter (Subcategory)</div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="add_param" value="1">
                    <div class="mb-2">
                        <label>Lab Test</label>
                        <select name="test_id" class="form-select" required>
                            <?php while($t = $tests->fetch_assoc()): ?>
                                <option value="<?php echo $t['test_id']; ?>"><?php echo $t['test_name']; ?></option>
                            <?php endwhile; $tests->data_seek(0); ?>
                        </select>
                    </div>
                    <div class="mb-2"><label>Parameter Name</label><input type="text" name="parameter_name" class="form-control" required></div>
                    <div class="mb-2"><label>Unit</label><input type="text" name="unit" class="form-control"></div>
                    <div class="row">
                        <div class="col-md-6"><label>Min</label><input type="number" step="0.0001" name="min_value" class="form-control"></div>
                        <div class="col-md-6"><label>Max</label><input type="number" step="0.0001" name="max_value" class="form-control"></div>
                    </div>
                    <button type="submit" class="btn btn-sm btn-info mt-2">Add Parameter</button>
                </form>
            </div>
        </div>
    </div>
</div>

<hr>
<h5>Existing Lab Tests & Parameters</h5>
<table class="table table-sm table-striped">
    <thead>
        <tr>
            <th>Category</th>
            <th>Test (Fee)</th>
            <th>Parameters (Range / Unit)</th>
        </tr>
    </thead>
    <tbody>
        <?php while($t = $tests->fetch_assoc()): ?>
            <tr>
                <td><?php echo $t['category_name']; ?></td>
                <td><?php echo $t['test_name']; ?> (<?php echo $t['fee']; ?>)</td>
                <td>
                    <ul class="mb-0">
                        <?php
                        $t_params = $mysqli->query("SELECT * FROM lab_parameters WHERE test_id = {$t['test_id']}");
                        while($tp = $t_params->fetch_assoc()): ?>
                            <li><?php echo $tp['parameter_name']; ?>: <?php echo $tp['min_value']; ?> - <?php echo $tp['max_value']; ?> <?php echo $tp['unit']; ?></li>
                        <?php endwhile; ?>
                    </ul>
                </td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<?php include '../../includes/footer.php'; ?>
