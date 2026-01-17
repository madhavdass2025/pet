<?php
require_once '../../config/db.php';
require_once '../../includes/auth_functions.php';
checkRole('Front Desk');

$consult_id = $_GET['consult_id'] ?? '';
if (!$consult_id) { header("Location: dashboard.php"); exit(); }

$stmt = $mysqli->prepare("
    SELECT c.*, p.*, d.diagnosis_notes, d.treatment_plan
    FROM consultations c
    JOIN pet_registration p ON c.RegNo = p.RegNo
    LEFT JOIN diagnosis d ON c.consult_id = d.consult_id
    WHERE c.consult_id = ?
");
$stmt->bind_param("i", $consult_id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

$stmt_p = $mysqli->prepare("SELECT * FROM prescriptions WHERE consult_id = ?");
$stmt_p->bind_param("i", $consult_id);
$stmt_p->execute();
$prescriptions = $stmt_p->get_result();

$stmt_d = $mysqli->prepare("SELECT * FROM medicine_dispensing WHERE consult_id = ?");
$stmt_d->bind_param("i", $consult_id);
$stmt_d->execute();
$dispensed = $stmt_d->get_result();

$stmt_l = $mysqli->prepare("SELECT * FROM lab_orders WHERE consult_id = ?");
$stmt_l->bind_param("i", $consult_id);
$stmt_l->execute();
$labs = $stmt_l->get_result();

include '../../includes/header.php';
?>

<div class="row">
    <div class="col-md-12">
        <div class="d-flex justify-content-between">
            <h2>Case Sheet: <?php echo $data['consult_no']; ?></h2>
            <button onclick="window.print()" class="btn btn-secondary d-print-none">Print Case Sheet</button>
        </div>
        <hr>
        <div class="row">
            <div class="col-md-6">
                <h5>Pet Details</h5>
                <p><strong>Name:</strong> <?php echo $data['petnam']; ?> (<?php echo $data['RegNo']; ?>)</p>
                <p><strong>Type:</strong> <?php echo $data['Pettyp']; ?> | <strong>Breed:</strong> <?php echo $data['petbred']; ?></p>
                <p><strong>Age:</strong> <?php echo $data['petage']; ?> | <strong>Weight:</strong> <?php echo $data['current_weight']; ?> kg</p>
            </div>
            <div class="col-md-6 text-end">
                <h5>Owner Details</h5>
                <p><strong>Name:</strong> <?php echo $data['ownnam']; ?></p>
                <p><strong>Mobile:</strong> <?php echo $data['ownmob']; ?></p>
                <p><strong>Address:</strong> <?php echo $data['ownadd1']; ?>, <?php echo $data['ownloc']; ?></p>
            </div>
        </div>
        <hr>
        <h5>Clinical Findings & Diagnosis</h5>
        <p><strong>Chief Complaint:</strong> <?php echo nl2br($data['chief_complaint']); ?></p>
        <p><strong>Diagnosis:</strong> <?php echo nl2br($data['diagnosis_notes'] ?? 'N/A'); ?></p>
        <p><strong>Treatment Plan:</strong> <?php echo nl2br($data['treatment_plan'] ?? 'N/A'); ?></p>
        <hr>
        <div class="row">
            <div class="col-md-6">
                <h5>Medicines Prescribed</h5>
                <ul>
                    <?php while($p = $prescriptions->fetch_assoc()): ?>
                        <li><?php echo $p['medicine_name']; ?> - <?php echo $p['dosage']; ?> (<?php echo $p['frequency']; ?> for <?php echo $p['duration']; ?>) - Qty: <?php echo $p['quantity']; ?></li>
                    <?php endwhile; ?>
                </ul>
            </div>
            <div class="col-md-6">
                <h5>Medicines Dispensed</h5>
                <ul>
                    <?php while($d = $dispensed->fetch_assoc()): ?>
                        <li><?php echo $d['medicine_name']; ?> - Qty: <?php echo $d['dispensed_qty']; ?> - Amount: <?php echo $d['total_amount']; ?></li>
                    <?php endwhile; ?>
                </ul>
            </div>
        </div>
        <hr>
        <h5>Lab Tests</h5>
        <ul>
            <?php while($l = $labs->fetch_assoc()): ?>
                <li><?php echo $l['test_name']; ?> - Status: <?php echo $l['test_status']; ?> - Fee: <?php echo $l['test_fee']; ?></li>
            <?php endwhile; ?>
        </ul>
        <hr>
        <div class="text-center mt-4">
            <a href="final_payment.php?consult_id=<?php echo $consult_id; ?>" class="btn btn-primary d-print-none">Proceed to Final Payment</a>
            <a href="dashboard.php" class="btn btn-secondary d-print-none">Back to Dashboard</a>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
