<?php
require_once '../../config/db.php';
require_once '../../includes/auth_functions.php';
checkRole('Doctor');
include '../../includes/header.php';

$reg_no = $_GET['reg_no'] ?? '';
if (!$reg_no) { header("Location: search.php"); exit(); }

$stmt = $mysqli->prepare("SELECT * FROM pet_registration WHERE RegNo = ?");
$stmt->bind_param("s", $reg_no);
$stmt->execute();
$pet = $stmt->get_result()->fetch_assoc();

if (!$pet) { echo "Patient not found."; exit(); }

$stmt_c = $mysqli->prepare("SELECT * FROM consultations WHERE RegNo = ? ORDER BY consult_date DESC");
$stmt_c->bind_param("s", $reg_no);
$stmt_c->execute();
$consultations = $stmt_c->get_result();
?>

<div class="d-flex justify-content-between align-items-center">
    <h2>Medical History: <?php echo htmlspecialchars($pet['petnam']); ?> (<?php echo $pet['RegNo']; ?>)</h2>
    <a href="search.php" class="btn btn-secondary">Back to Search</a>
</div>

<div class="card mt-3">
    <div class="card-body">
        <div class="row">
            <div class="col-md-4"><strong>Owner:</strong> <?php echo htmlspecialchars($pet['ownnam']); ?></div>
            <div class="col-md-4"><strong>Mobile:</strong> <?php echo $pet['ownmob']; ?></div>
            <div class="col-md-4"><strong>Pet Type/Breed:</strong> <?php echo htmlspecialchars($pet['Pettyp']); ?> / <?php echo htmlspecialchars($pet['petbred']); ?></div>
        </div>
    </div>
</div>

<h4 class="mt-4">Previous Consultations</h4>
<?php while($c = $consultations->fetch_assoc()):
    $cid = $c['consult_id'];

    $stmt_v = $mysqli->prepare("SELECT * FROM vitals WHERE consult_id = ?");
    $stmt_v->bind_param("i", $cid);
    $stmt_v->execute();
    $v = $stmt_v->get_result()->fetch_assoc();

    $stmt_d = $mysqli->prepare("SELECT * FROM diagnosis WHERE consult_id = ?");
    $stmt_d->bind_param("i", $cid);
    $stmt_d->execute();
    $d = $stmt_d->get_result()->fetch_assoc();

    $stmt_p = $mysqli->prepare("SELECT * FROM prescriptions WHERE consult_id = ?");
    $stmt_p->bind_param("i", $cid);
    $stmt_p->execute();
    $p_list = $stmt_p->get_result();
?>
    <div class="card mt-3">
        <div class="card-header d-flex justify-content-between">
            <strong>Date: <?php echo date('d-M-Y H:i', strtotime($c['consult_date'])); ?> (<?php echo $c['consult_no']; ?>)</strong>
            <span>Status: <span class="badge bg-info"><?php echo ucfirst($c['status']); ?></span></span>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6>Vitals</h6>
                    <?php if ($v): ?>
                        <small>Temp: <?php echo $v['temperature']; ?>°C | HR: <?php echo $v['heart_rate']; ?> | RR: <?php echo $v['respiratory_rate']; ?> | Wt: <?php echo $v['weight']; ?>kg</small>
                    <?php else: ?>
                        <small>No vitals recorded.</small>
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <h6>Diagnosis</h6>
                    <p><?php echo nl2br(htmlspecialchars($d['diagnosis_notes'] ?? 'No notes.')); ?></p>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-md-12">
                    <h6>Prescriptions</h6>
                    <?php if ($p_list->num_rows > 0): ?>
                        <ul class="mb-0">
                            <?php while($p = $p_list->fetch_assoc()): ?>
                                <li><small><?php echo $p['medicine_name']; ?> - <?php echo $p['dosage']; ?> (<?php echo $p['frequency']; ?> for <?php echo $p['duration']; ?>)</small></li>
                            <?php endwhile; ?>
                        </ul>
                    <?php else: ?>
                        <small>No medicines prescribed.</small>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php endwhile; ?>

<?php include '../../includes/footer.php'; ?>
