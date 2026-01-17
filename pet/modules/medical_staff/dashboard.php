<?php
require_once '../../config/db.php';
require_once '../../includes/auth_functions.php';
checkRole('Medical Staff');
include '../../includes/header.php';

$pending_dispensing = $mysqli->query("
    SELECT DISTINCT p.consult_id, pr.petnam, pr.RegNo, pr.ownnam, c.consult_no
    FROM prescriptions p
    JOIN pet_registration pr ON p.RegNo = pr.RegNo
    JOIN consultations c ON p.consult_id = c.consult_id
    LEFT JOIN medicine_dispensing md ON p.prescription_id = md.prescription_id
    WHERE md.dispense_id IS NULL
");

$pending_labs = $mysqli->query("
    SELECT lo.*, pr.petnam, pr.RegNo
    FROM lab_orders lo
    JOIN pet_registration pr ON lo.RegNo = pr.RegNo
    WHERE lo.test_status != 'completed'
");

$pending_vaccines = $mysqli->query("
    SELECT vo.*, pr.petnam
    FROM vaccination_orders vo
    JOIN pet_registration pr ON vo.RegNo = pr.RegNo
    WHERE vo.administered = 0
");
?>

<h2>Medical Staff Dashboard</h2>

<div class="row mt-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-warning">Pending Medication Dispensing</div>
            <div class="card-body">
                <table class="table table-sm">
                    <thead><tr><th>Consult No</th><th>Pet</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php while($pd = $pending_dispensing->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $pd['consult_no']; ?></td>
                                <td><?php echo $pd['petnam']; ?> (<?php echo $pd['RegNo']; ?>)</td>
                                <td><a href="dispense.php?consult_id=<?php echo $pd['consult_id']; ?>" class="btn btn-sm btn-primary">Dispense</a></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header bg-info text-white">Pending Lab Tests</div>
            <div class="card-body">
                <table class="table table-sm">
                    <thead><tr><th>Test</th><th>Pet</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php while($pl = $pending_labs->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $pl['test_name']; ?></td>
                                <td><?php echo $pl['petnam']; ?></td>
                                <td>
                                    <span class="badge bg-<?php echo ($pl['payment_status'] == 'paid') ? 'success' : 'danger'; ?>">
                                        <?php echo ucfirst($pl['payment_status']); ?>
                                    </span>
                                    <?php if ($pl['payment_status'] == 'paid'): ?>
                                        <a href="lab_entry.php?order_id=<?php echo $pl['order_id']; ?>" class="btn btn-sm btn-primary">Enter Results</a>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-secondary" disabled title="Payment Pending">Enter Results</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-12 mt-4">
        <div class="card">
            <div class="card-header bg-success text-white">Pending Vaccinations</div>
            <div class="card-body">
                <table class="table table-sm">
                    <thead><tr><th>Vaccine</th><th>Pet</th><th>Action</th></tr></thead>
                    <tbody>
                        <?php while($pv = $pending_vaccines->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $pv['vaccine_name']; ?></td>
                                <td><?php echo $pv['petnam']; ?></td>
                                <td>
                                    <span class="badge bg-<?php echo ($pv['payment_status'] == 'paid') ? 'success' : 'danger'; ?>">
                                        <?php echo ucfirst($pv['payment_status']); ?>
                                    </span>
                                    <?php if ($pv['payment_status'] == 'paid'): ?>
                                        <a href="vaccination.php?order_id=<?php echo $pv['vacc_order_id']; ?>" class="btn btn-sm btn-primary">Administer</a>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-secondary" disabled title="Payment Pending">Administer</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-12 mt-4">
        <div class="card">
            <div class="card-header bg-dark text-white d-flex justify-content-between">
                <span>Additional Service Charges (Nursing, Assistant, Disposables)</span>
                <button class="btn btn-sm btn-light" data-bs-toggle="modal" data-bs-target="#addChargeModal">Add Charge</button>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <thead><tr><th>Consult No</th><th>Type</th><th>Description</th><th>Amount</th><th>By</th></tr></thead>
                    <tbody>
                        <?php
                        $charges = $mysqli->query("
                            SELECT asc.*, c.consult_no, u.username
                            FROM additional_service_charges asc
                            JOIN consultations c ON asc.consult_id = c.consult_id
                            JOIN users u ON asc.recorded_by = u.user_id
                            ORDER BY asc.recorded_at DESC LIMIT 10
                        ");
                        while($ch = $charges->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo $ch['consult_no']; ?></td>
                                <td><?php echo $ch['charge_type']; ?></td>
                                <td><?php echo $ch['description']; ?></td>
                                <td><?php echo $ch['amount']; ?></td>
                                <td><?php echo $ch['username']; ?></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add Charge Modal -->
<div class="modal fade" id="addChargeModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="add_charge.php" method="POST">
        <div class="modal-header"><h5>Add Additional Charge</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
        <div class="modal-body">
            <div class="mb-3">
                <label class="form-label">Consultation No / ID</label>
                <select name="consult_id" class="form-select" required>
                    <?php
                    $recent_consults = $mysqli->query("SELECT consult_id, consult_no FROM consultations ORDER BY consult_id DESC LIMIT 20");
                    while($rc = $recent_consults->fetch_assoc()): ?>
                        <option value="<?php echo $rc['consult_id']; ?>"><?php echo $rc['consult_no']; ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Charge Type</label>
                <select name="charge_type" class="form-select" required>
                    <option value="Nursing Charge">Nursing Charge</option>
                    <option value="Assistant Charge">Assistant Charge</option>
                    <option value="Disposable Charge">Disposable Charge</option>
                    <option value="Other">Other</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label">Description</label>
                <input type="text" name="description" class="form-control" placeholder="e.g. IV set, Nursing care">
            </div>
            <div class="mb-3">
                <label class="form-label">Amount</label>
                <input type="number" step="0.01" name="amount" class="form-control" required>
            </div>
        </div>
        <div class="modal-footer"><button type="submit" class="btn btn-primary">Save Charge</button></div>
      </form>
    </div>
  </div>
</div>

<?php include '../../includes/footer.php'; ?>
