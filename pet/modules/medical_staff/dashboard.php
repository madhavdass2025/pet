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
                                <td><a href="lab_entry.php?order_id=<?php echo $pl['order_id']; ?>" class="btn btn-sm btn-primary">Enter Results</a></td>
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
                                <td><a href="vaccination.php?order_id=<?php echo $pv['vacc_order_id']; ?>" class="btn btn-sm btn-primary">Administer</a></td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
