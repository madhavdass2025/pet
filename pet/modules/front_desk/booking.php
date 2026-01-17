<?php
require_once '../../config/db.php';
require_once '../../includes/auth_functions.php';
checkRole('Front Desk');

$reg_no = $_GET['reg_no'] ?? '';
$pet_details = null;
$message = "";

if ($reg_no) {
    $stmt = $mysqli->prepare("SELECT petnam, ownnam, Pettyp, petwt FROM pet_registration WHERE RegNo = ?");
    $stmt->bind_param("s", $reg_no);
    $stmt->execute();
    $pet_details = $stmt->get_result()->fetch_assoc();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $reg_no = $_POST['RegNo'];
    $doctor_id = $_POST['doctor_id'];
    $current_weight = $_POST['current_weight'];
    $is_pet_present = isset($_POST['is_pet_present']) ? 1 : 0;
    $chief_complaint = $_POST['chief_complaint'];

    // Fee Logic
    $fee_waived = false;
    $consultation_fee = 0;

    // Check last consultation payment (simplified: check last consultation date)
    $stmt_check = $mysqli->prepare("SELECT MAX(consult_date) as last_consult FROM consultations WHERE RegNo = ? AND status = 'completed'");
    $stmt_check->bind_param("s", $reg_no);
    $stmt_check->execute();
    $last_consult = $stmt_check->get_result()->fetch_assoc()['last_consult'];

    if ($last_consult && (strtotime(date('Y-m-d')) - strtotime(date('Y-m-d', strtotime($last_consult)))) <= (7 * 24 * 60 * 60)) {
        $fee_waived = true;
        $consultation_fee = 0;
    } else {
        $fee_res = $mysqli->query("SELECT fee_amount FROM fee_master WHERE fee_type = 'consultation' AND is_active = 1 LIMIT 1");
        $consultation_fee = ($fee_res->num_rows > 0) ? $fee_res->fetch_assoc()['fee_amount'] : 0.00;
    }

    // Daily Serial Number
    $today = date('Y-m-d');
    $res_count = $mysqli->query("SELECT COUNT(*) as cnt FROM consultations WHERE DATE(consult_date) = '$today'");
    $daily_count = $res_count->fetch_assoc()['cnt'] + 1;
    $consult_no = date('Ymd') . '-' . str_pad($daily_count, 3, '0', STR_PAD_LEFT);

    $stmt_ins = $mysqli->prepare("INSERT INTO consultations (RegNo, consult_date, consult_no, doctor_id, current_weight, is_pet_present, chief_complaint, consultation_fee, fee_waived, created_by) VALUES (?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt_ins->bind_param("ssidssidi", $reg_no, $consult_no, $doctor_id, $current_weight, $is_pet_present, $chief_complaint, $consultation_fee, $fee_waived, $_SESSION['user_id']);

    if ($stmt_ins->execute()) {
        $consult_id = $stmt_ins->insert_id;
        header("Location: payment.php?consult_id=" . $consult_id);
        exit();
    } else {
        $message = "Error: " . $mysqli->error;
    }
}

$doctors = $mysqli->query("SELECT user_id, full_name FROM users WHERE role = 'Doctor' AND is_active = 1");

include '../../includes/header.php';
?>

<h2>Book Consultation</h2>
<?php if ($message): ?>
    <div class="alert alert-danger"><?php echo $message; ?></div>
<?php endif; ?>

<form method="POST" class="row g-3">
    <div class="col-md-4">
        <label class="form-label">Registration Number (RegNo)</label>
        <input type="text" name="RegNo" class="form-control" value="<?php echo htmlspecialchars($reg_no); ?>" required>
    </div>
    <?php if ($pet_details): ?>
        <div class="col-md-8">
            <div class="alert alert-info">
                <strong>Pet:</strong> <?php echo htmlspecialchars($pet_details['petnam']); ?> (<?php echo htmlspecialchars($pet_details['Pettyp']); ?>) |
                <strong>Owner:</strong> <?php echo htmlspecialchars($pet_details['ownnam']); ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="col-md-4">
        <label class="form-label">Assign Doctor</label>
        <select name="doctor_id" class="form-select" required>
            <option value="">Select Doctor</option>
            <?php while($doc = $doctors->fetch_assoc()): ?>
                <option value="<?php echo $doc['user_id']; ?>"><?php echo htmlspecialchars($doc['full_name']); ?></option>
            <?php endwhile; ?>
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">Current Weight (kg)</label>
        <input type="number" step="0.01" name="current_weight" class="form-control" value="<?php echo $pet_details['petwt'] ?? ''; ?>" required>
    </div>
    <div class="col-md-4 py-4">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="is_pet_present" id="is_pet_present" checked>
            <label class="form-check-label" for="is_pet_present">Pet is Present</label>
        </div>
    </div>
    <div class="col-md-12">
        <label class="form-label">Chief Complaint</label>
        <textarea name="chief_complaint" class="form-control" rows="3" required></textarea>
    </div>

    <div class="col-12">
        <button type="submit" class="btn btn-success">Save and Proceed to Payment</button>
    </div>
</form>

<?php include '../../includes/footer.php'; ?>
