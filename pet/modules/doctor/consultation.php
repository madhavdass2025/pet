<?php
require_once '../../config/db.php';
require_once '../../includes/auth_functions.php';
checkRole('Doctor');

$consult_id = $_GET['consult_id'] ?? '';
if (!$consult_id) {
    header("Location: dashboard.php");
    exit();
}

// Fetch consultation and pet details
$stmt = $mysqli->prepare("
    SELECT c.*, p.*
    FROM consultations c
    JOIN pet_registration p ON c.RegNo = p.RegNo
    WHERE c.consult_id = ?
");
$stmt->bind_param("i", $consult_id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

if (!$data) {
    echo "Consultation not found.";
    exit();
}

// Update status to in-progress if it was scheduled
if ($data['status'] == 'scheduled') {
    $stmt_up_s = $mysqli->prepare("UPDATE consultations SET status = 'in-progress' WHERE consult_id = ?");
    $stmt_up_s->bind_param("i", $consult_id);
    $stmt_up_s->execute();
}

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action == 'save_vitals') {
        $stmt_v = $mysqli->prepare("INSERT INTO vitals (consult_id, RegNo, temperature, heart_rate, respiratory_rate, weight, body_condition_score, mucous_membrane, capillary_refill_time, hydration_status, recorded_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt_v->bind_param("isdddidsssi", $consult_id, $data['RegNo'], $_POST['temperature'], $_POST['heart_rate'], $_POST['respiratory_rate'], $_POST['weight'], $_POST['bcs'], $_POST['mucous_membrane'], $_POST['crt'], $_POST['hydration'], $_SESSION['user_id']);
        $stmt_v->execute();
        logActivity($mysqli, "Recorded vitals for consult $consult_id", "vitals", $stmt_v->insert_id);
    } elseif ($action == 'save_diagnosis') {
        $stmt_d = $mysqli->prepare("INSERT INTO diagnosis (consult_id, RegNo, diagnosis_notes, differential_diagnosis, treatment_plan, special_instructions, next_review_date) VALUES (?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE diagnosis_notes=VALUES(diagnosis_notes), treatment_plan=VALUES(treatment_plan)");
        $stmt_d->bind_param("issssss", $consult_id, $data['RegNo'], $_POST['diagnosis_notes'], $_POST['differential_diagnosis'], $_POST['treatment_plan'], $_POST['special_instructions'], $_POST['next_review_date']);
        $stmt_d->execute();
        logActivity($mysqli, "Updated diagnosis for consult $consult_id", "diagnosis", $stmt_d->insert_id ?: $consult_id);
    } elseif ($action == 'add_prescription') {
        $stmt_p = $mysqli->prepare("INSERT INTO prescriptions (consult_id, RegNo, med_id, medicine_name, quantity, dosage, frequency, duration, route, instructions) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        // Get med name from med_id
        $med_id = $_POST['med_id'];
        $stmt_med = $mysqli->prepare("SELECT med_name FROM medicine_master WHERE med_id = ?");
        $stmt_med->bind_param("i", $med_id);
        $stmt_med->execute();
        $med_name = $stmt_med->get_result()->fetch_assoc()['med_name'];

        $stmt_p->bind_param("isssisssss", $consult_id, $data['RegNo'], $med_id, $med_name, $_POST['quantity'], $_POST['dosage'], $_POST['frequency'], $_POST['duration'], $_POST['route'], $_POST['instructions']);
        $stmt_p->execute();
    } elseif ($action == 'add_lab_order') {
        $test_id = $_POST['test_id'];
        $stmt_t = $mysqli->prepare("SELECT test_name, fee FROM lab_test_master WHERE test_id = ?");
        $stmt_t->bind_param("i", $test_id);
        $stmt_t->execute();
        $test_data = $stmt_t->get_result()->fetch_assoc();

        $stmt_l = $mysqli->prepare("INSERT INTO lab_orders (consult_id, RegNo, test_id, test_name, test_fee, ordered_by) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt_l->bind_param("isisdi", $consult_id, $data['RegNo'], $test_id, $test_data['test_name'], $test_data['fee'], $_SESSION['user_id']);
        $stmt_l->execute();
    } elseif ($action == 'add_vaccine_order') {
        $vacc_id = $_POST['vacc_id'];
        $stmt_v_mast = $mysqli->prepare("SELECT vacc_name, fee_amount FROM vaccination_master v JOIN fee_master f ON f.fee_type = 'vaccination' WHERE v.vacc_id = ? LIMIT 1");
        // Simplified fee fetching for vaccine
        $stmt_v_mast = $mysqli->prepare("SELECT vacc_name FROM vaccination_master WHERE vacc_id = ?");
        $stmt_v_mast->bind_param("i", $vacc_id);
        $stmt_v_mast->execute();
        $vacc_data = $stmt_v_mast->get_result()->fetch_assoc();

        $fee = 200.00; // Default if not found in fee_master

        $stmt_vo = $mysqli->prepare("INSERT INTO vaccination_orders (consult_id, RegNo, vacc_id, vaccine_name, fee, ordered_by) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt_vo->bind_param("isisdi", $consult_id, $data['RegNo'], $vacc_id, $vacc_data['vacc_name'], $fee, $_SESSION['user_id']);
        $stmt_vo->execute();
    } elseif ($action == 'complete_consultation') {
        $stmt_comp = $mysqli->prepare("UPDATE consultations SET status = 'completed' WHERE consult_id = ?");
        $stmt_comp->bind_param("i", $consult_id);
        $stmt_comp->execute();
        header("Location: dashboard.php");
        exit();
    }
    // Refresh page to show updated info
    header("Location: consultation.php?consult_id=$consult_id");
    exit();
}

// Fetch current info
$stmt_v = $mysqli->prepare("SELECT * FROM vitals WHERE consult_id = ? ORDER BY recorded_at DESC LIMIT 1");
$stmt_v->bind_param("i", $consult_id);
$stmt_v->execute();
$vitals = $stmt_v->get_result()->fetch_assoc();

$stmt_d = $mysqli->prepare("SELECT * FROM diagnosis WHERE consult_id = ?");
$stmt_d->bind_param("i", $consult_id);
$stmt_d->execute();
$diagnosis = $stmt_d->get_result()->fetch_assoc();

$stmt_p_list = $mysqli->prepare("SELECT * FROM prescriptions WHERE consult_id = ?");
$stmt_p_list->bind_param("i", $consult_id);
$stmt_p_list->execute();
$prescriptions = $stmt_p_list->get_result();

$stmt_lo_list = $mysqli->prepare("SELECT * FROM lab_orders WHERE consult_id = ?");
$stmt_lo_list->bind_param("i", $consult_id);
$stmt_lo_list->execute();
$lab_orders = $stmt_lo_list->get_result();

$stmt_h = $mysqli->prepare("SELECT c.consult_date, d.diagnosis_notes FROM consultations c LEFT JOIN diagnosis d ON c.consult_id = d.consult_id WHERE c.RegNo = ? AND c.consult_id != ? ORDER BY c.consult_date DESC");
$stmt_h->bind_param("si", $data['RegNo'], $consult_id);
$stmt_h->execute();
$history = $stmt_h->get_result();

$medicines = $mysqli->query("SELECT * FROM medicine_master WHERE is_active = 1");
$lab_tests = $mysqli->query("SELECT * FROM lab_test_master");
$vaccines = $mysqli->query("SELECT * FROM vaccination_master WHERE is_active = 1");
$vacc_orders = $mysqli->prepare("SELECT * FROM vaccination_orders WHERE consult_id = ?");
$vacc_orders->bind_param("i", $consult_id);
$vacc_orders->execute();
$vacc_orders = $vacc_orders->get_result();

include '../../includes/header.php';
?>

<div class="row">
    <div class="col-md-8">
        <h3>Consultation: <?php echo $data['petnam']; ?> (<?php echo $data['RegNo']; ?>)</h3>
        <p>Owner: <?php echo $data['ownnam']; ?> | Age: <?php echo $data['petage']; ?> | Breed: <?php echo $data['petbred']; ?></p>
    </div>
    <div class="col-md-4 text-end">
        <form method="POST">
            <input type="hidden" name="action" value="complete_consultation">
            <button type="submit" class="btn btn-success">Mark as Completed</button>
            <a href="dashboard.php" class="btn btn-secondary">Back</a>
        </form>
    </div>
</div>

<ul class="nav nav-tabs mt-3" id="consultationTabs" role="tablist">
    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#history">History</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#vitals">Vitals</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#diagnosis">Diagnosis</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#prescriptions">Prescriptions</button></li>
    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#orders">Lab & Orders</button></li>
</ul>

<div class="tab-content border p-3 bg-white">
    <!-- History Tab -->
    <div class="tab-pane fade show active" id="history">
        <h5>Previous Visits</h5>
        <table class="table">
            <thead><tr><th>Date</th><th>Diagnosis</th></tr></thead>
            <tbody>
                <?php while($h = $history->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo date('Y-m-d', strtotime($h['consult_date'])); ?></td>
                        <td><?php echo nl2br(htmlspecialchars($h['diagnosis_notes'] ?? 'N/A')); ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- Vitals Tab -->
    <div class="tab-pane fade" id="vitals">
        <form method="POST">
            <input type="hidden" name="action" value="save_vitals">
            <div class="row g-3">
                <div class="col-md-3"><label class="form-label">Temp (°C)</label><input type="number" step="0.1" name="temperature" class="form-control" value="<?php echo $vitals['temperature'] ?? ''; ?>"></div>
                <div class="col-md-3"><label class="form-label">Heart Rate</label><input type="number" name="heart_rate" class="form-control" value="<?php echo $vitals['heart_rate'] ?? ''; ?>"></div>
                <div class="col-md-3"><label class="form-label">Resp. Rate</label><input type="number" name="respiratory_rate" class="form-control" value="<?php echo $vitals['respiratory_rate'] ?? ''; ?>"></div>
                <div class="col-md-3"><label class="form-label">Weight (kg)</label><input type="number" step="0.01" name="weight" class="form-control" value="<?php echo $vitals['weight'] ?? $data['current_weight']; ?>"></div>
                <div class="col-md-3"><label class="form-label">BCS (1-9)</label><input type="number" name="bcs" class="form-control" value="<?php echo $vitals['body_condition_score'] ?? ''; ?>"></div>
                <div class="col-md-3"><label class="form-label">Mucous Membrane</label><input type="text" name="mucous_membrane" class="form-control" value="<?php echo $vitals['mucous_membrane'] ?? ''; ?>"></div>
                <div class="col-md-3"><label class="form-label">CRT</label><input type="text" name="crt" class="form-control" value="<?php echo $vitals['capillary_refill_time'] ?? ''; ?>"></div>
                <div class="col-md-3"><label class="form-label">Hydration</label><input type="text" name="hydration" class="form-control" value="<?php echo $vitals['hydration_status'] ?? ''; ?>"></div>
                <div class="col-12"><button type="submit" class="btn btn-primary">Save Vitals</button></div>
            </div>
        </form>
    </div>

    <!-- Diagnosis Tab -->
    <div class="tab-pane fade" id="diagnosis">
        <form method="POST">
            <input type="hidden" name="action" value="save_diagnosis">
            <div class="mb-3"><label class="form-label">Diagnosis Notes</label><textarea name="diagnosis_notes" class="form-control" rows="3"><?php echo $diagnosis['diagnosis_notes'] ?? ''; ?></textarea></div>
            <div class="mb-3"><label class="form-label">Differential Diagnosis</label><textarea name="differential_diagnosis" class="form-control" rows="2"><?php echo $diagnosis['differential_diagnosis'] ?? ''; ?></textarea></div>
            <div class="mb-3"><label class="form-label">Treatment Plan</label><textarea name="treatment_plan" class="form-control" rows="3"><?php echo $diagnosis['treatment_plan'] ?? ''; ?></textarea></div>
            <div class="mb-3"><label class="form-label">Special Instructions</label><textarea name="special_instructions" class="form-control" rows="2"><?php echo $diagnosis['special_instructions'] ?? ''; ?></textarea></div>
            <div class="mb-3"><label class="form-label">Next Review Date</label><input type="date" name="next_review_date" class="form-control" value="<?php echo $diagnosis['next_review_date'] ?? ''; ?>"></div>
            <button type="submit" class="btn btn-primary">Save Diagnosis</button>
        </form>
    </div>

    <!-- Prescriptions Tab -->
    <div class="tab-pane fade" id="prescriptions">
        <h5>Add Medicine</h5>
        <form method="POST" class="row g-2 mb-4">
            <input type="hidden" name="action" value="add_prescription">
            <div class="col-md-4">
                <select name="med_id" class="form-select" required>
                    <option value="">Select Medicine</option>
                    <?php while($m = $medicines->fetch_assoc()): ?>
                        <option value="<?php echo $m['med_id']; ?>"><?php echo $m['med_name']; ?> (<?php echo $m['strength']; ?>)</option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-2"><input type="number" name="quantity" class="form-control" placeholder="Qty" required></div>
            <div class="col-md-2"><input type="text" name="dosage" class="form-control" placeholder="Dosage (e.g. 1 tab)" required></div>
            <div class="col-md-2"><input type="text" name="frequency" class="form-control" placeholder="Freq (e.g. BID)" required></div>
            <div class="col-md-2"><input type="text" name="duration" class="form-control" placeholder="Duration (e.g. 5 days)" required></div>
            <div class="col-md-2 mt-2"><input type="text" name="route" class="form-control" placeholder="Route (e.g. Oral)"></div>
            <div class="col-md-8 mt-2"><input type="text" name="instructions" class="form-control" placeholder="Instructions"></div>
            <div class="col-md-2 mt-2"><button type="submit" class="btn btn-sm btn-success w-100">Add</button></div>
        </form>
        <table class="table table-sm">
            <thead><tr><th>Medicine</th><th>Qty</th><th>Dosage</th><th>Freq</th><th>Dur</th><th>Action</th></tr></thead>
            <tbody>
                <?php
                $prescriptions->data_seek(0);
                while($p = $prescriptions->fetch_assoc()):
                ?>
                    <tr>
                        <td><?php echo $p['medicine_name']; ?></td>
                        <td><?php echo $p['quantity']; ?></td>
                        <td><?php echo $p['dosage']; ?></td>
                        <td><?php echo $p['frequency']; ?></td>
                        <td><?php echo $p['duration']; ?></td>
                        <td><button class="btn btn-sm btn-danger">Remove</button></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- Orders Tab -->
    <div class="tab-pane fade" id="orders">
        <h5>Lab Tests</h5>
        <form method="POST" class="row g-2 mb-4">
            <input type="hidden" name="action" value="add_lab_order">
            <div class="col-md-8">
                <select name="test_id" class="form-select" required>
                    <option value="">Select Lab Test</option>
                    <?php while($lt = $lab_tests->fetch_assoc()): ?>
                        <option value="<?php echo $lt['test_id']; ?>"><?php echo $lt['test_name']; ?> (<?php echo $lt['fee']; ?>)</option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-4"><button type="submit" class="btn btn-success w-100">Order Test</button></div>
        </form>
        <table class="table table-sm">
            <thead><tr><th>Test Name</th><th>Status</th><th>Fee</th></tr></thead>
            <tbody>
                <?php while($lo = $lab_orders->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $lo['test_name']; ?></td>
                        <td><?php echo $lo['test_status']; ?></td>
                        <td><?php echo $lo['test_fee']; ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <h5 class="mt-4">Vaccinations</h5>
        <form method="POST" class="row g-2 mb-4">
            <input type="hidden" name="action" value="add_vaccine_order">
            <div class="col-md-8">
                <select name="vacc_id" class="form-select" required>
                    <option value="">Select Vaccine</option>
                    <?php while($v = $vaccines->fetch_assoc()): ?>
                        <option value="<?php echo $v['vacc_id']; ?>"><?php echo $v['vacc_name']; ?> (<?php echo $v['pet_type']; ?>)</option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-4"><button type="submit" class="btn btn-success w-100">Order Vaccine</button></div>
        </form>
        <table class="table table-sm">
            <thead><tr><th>Vaccine</th><th>Status</th><th>Fee</th></tr></thead>
            <tbody>
                <?php while($vo = $vacc_orders->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $vo['vaccine_name']; ?></td>
                        <td><?php echo $vo['administered'] ? 'Administered' : 'Pending'; ?></td>
                        <td><?php echo $vo['fee']; ?></td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
