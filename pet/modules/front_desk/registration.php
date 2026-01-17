<?php
require_once '../../config/db.php';
require_once '../../includes/auth_functions.php';
checkRole('Front Desk');

$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $mysqli->begin_transaction();
    try {
        $year = date('Y');
        $prefix = $year . '-';

        // RegNo Generation Logic
        $res = $mysqli->query("SELECT RegNo FROM pet_registration WHERE RegNo LIKE '$prefix%' ORDER BY RegNo DESC LIMIT 1");
        if ($res->num_rows > 0) {
            $last_reg = $res->fetch_assoc()['RegNo'];
            $last_num = (int)substr($last_reg, 5);
            $new_num = $last_num + 1;
        } else {
            $new_num = 1000;
        }
        $reg_no = $prefix . $new_num;

        // Fetch registration fee
        $fee_res = $mysqli->query("SELECT fee_amount FROM fee_master WHERE fee_type = 'registration' AND is_active = 1 LIMIT 1");
        $reg_fee = ($fee_res->num_rows > 0) ? $fee_res->fetch_assoc()['fee_amount'] : 0.00;

        $stmt = $mysqli->prepare("INSERT INTO pet_registration (RegDt, RegNo, Pettyp, petnam, petclr, petsex, petbred, petage, petwt, pet_microchip, pet_dob, ownnam, ownadd1, ownadd2, ownloc, ownpin, ownmob, ownres, ownemail, ownalt_phone, registration_fee, created_by) VALUES (NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->bind_param("ssssssssssssssssssssi",
            $reg_no, $_POST['Pettyp'], $_POST['petnam'], $_POST['petclr'], $_POST['petsex'],
            $_POST['petbred'], $_POST['petage'], $_POST['petwt'], $_POST['pet_microchip'],
            $_POST['pet_dob'], $_POST['ownnam'], $_POST['ownadd1'], $_POST['ownadd2'],
            $_POST['ownloc'], $_POST['ownpin'], $_POST['ownmob'], $_POST['ownres'],
            $_POST['ownemail'], $_POST['ownalt_phone'], $reg_fee, $_SESSION['user_id']
        );

        $stmt->execute();
        $reg_id = $stmt->insert_id;
        logActivity($mysqli, "Registered new pet: $reg_no", "pet_registration", $reg_id);
        $mysqli->commit();

        header("Location: booking.php?reg_no=" . $reg_no);
        exit();
    } catch (Exception $e) {
        $mysqli->rollback();
        $message = "Error: " . $e->getMessage();
    }
}

include '../../includes/header.php';
?>

<h2>Pet Registration</h2>
<?php if ($message): ?>
    <div class="alert alert-danger"><?php echo $message; ?></div>
<?php endif; ?>

<form method="POST" class="row g-3">
    <div class="col-md-12"><h4>Pet Details</h4></div>
    <div class="col-md-3">
        <label class="form-label">Pet Type</label>
        <select name="Pettyp" class="form-select" required>
            <option value="Dog">Dog</option>
            <option value="Cat">Cat</option>
            <option value="Bird">Bird</option>
            <option value="Other">Other</option>
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label">Pet Name</label>
        <input type="text" name="petnam" class="form-control" required>
    </div>
    <div class="col-md-3">
        <label class="form-label">Color</label>
        <input type="text" name="petclr" class="form-control" required>
    </div>
    <div class="col-md-3">
        <label class="form-label">Gender</label>
        <select name="petsex" class="form-select" required>
            <option value="Male">Male</option>
            <option value="Female">Female</option>
        </select>
    </div>
    <div class="col-md-3">
        <label class="form-label">Breed</label>
        <input type="text" name="petbred" class="form-control" required>
    </div>
    <div class="col-md-3">
        <label class="form-label">Age</label>
        <input type="text" name="petage" class="form-control" required>
    </div>
    <div class="col-md-3">
        <label class="form-label">Weight (kg)</label>
        <input type="number" step="0.01" name="petwt" class="form-control" required>
    </div>
    <div class="col-md-3">
        <label class="form-label">Microchip No.</label>
        <input type="text" name="pet_microchip" class="form-control">
    </div>
    <div class="col-md-3">
        <label class="form-label">Date of Birth</label>
        <input type="date" name="pet_dob" class="form-control">
    </div>

    <div class="col-md-12 mt-4"><h4>Owner Details</h4></div>
    <div class="col-md-4">
        <label class="form-label">Owner Name</label>
        <input type="text" name="ownnam" class="form-control" required>
    </div>
    <div class="col-md-4">
        <label class="form-label">Mobile</label>
        <input type="text" name="ownmob" class="form-control" required>
    </div>
    <div class="col-md-4">
        <label class="form-label">Email</label>
        <input type="email" name="ownemail" class="form-control" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">Address 1</label>
        <input type="text" name="ownadd1" class="form-control" required>
    </div>
    <div class="col-md-6">
        <label class="form-label">Address 2</label>
        <input type="text" name="ownadd2" class="form-control" required>
    </div>
    <div class="col-md-4">
        <label class="form-label">Location</label>
        <input type="text" name="ownloc" class="form-control" required>
    </div>
    <div class="col-md-4">
        <label class="form-label">Pincode</label>
        <input type="text" name="ownpin" class="form-control" required>
    </div>
    <div class="col-md-4">
        <label class="form-label">Residence Phone</label>
        <input type="text" name="ownres" class="form-control">
    </div>
    <div class="col-md-4">
        <label class="form-label">Alternate Phone</label>
        <input type="text" name="ownalt_phone" class="form-control">
    </div>

    <div class="col-12 mt-4">
        <button type="submit" class="btn btn-primary">Register and Book Consultation</button>
    </div>
</form>

<?php include '../../includes/footer.php'; ?>
