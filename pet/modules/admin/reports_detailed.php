<?php
require_once '../../config/db.php';
require_once '../../includes/auth_functions.php';
checkRole(['Admin', 'Accountant']);

$month = $_GET['month'] ?? date('Y-m');

// 1. Monthly Revenue
$rev_stmt = $mysqli->prepare("SELECT SUM(total_amount) as rev FROM payment_transactions WHERE DATE_FORMAT(transaction_date, '%Y-%m') = ?");
$rev_stmt->bind_param("s", $month);
$rev_stmt->execute();
$monthly_rev = $rev_stmt->get_result()->fetch_assoc()['rev'] ?: 0;

// 2. Service-wise Breakdown
$stmt_svc = $mysqli->prepare("
    SELECT
        SUM(registration_fee) as reg,
        SUM(consultation_fee) as cons,
        SUM(medicine_charges) as med,
        SUM(lab_charges) as lab,
        SUM(vaccination_charges) as vacc,
        SUM(xray_charges) as xray,
        SUM(surgery_charges) as surg,
        SUM(other_charges) as other
    FROM payment_transactions
    WHERE DATE_FORMAT(transaction_date, '%Y-%m') = ?
");
$stmt_svc->bind_param("s", $month);
$stmt_svc->execute();
$services = $stmt_svc->get_result()->fetch_assoc();

// 3. Doctor-wise stats
$stmt_doc = $mysqli->prepare("
    SELECT u.full_name, COUNT(c.consult_id) as count, SUM(c.consultation_fee) as total_fees
    FROM users u
    LEFT JOIN consultations c ON u.user_id = c.doctor_id AND DATE_FORMAT(c.consult_date, '%Y-%m') = ?
    WHERE u.role = 'Doctor'
    GROUP BY u.user_id
");
$stmt_doc->bind_param("s", $month);
$stmt_doc->execute();
$doc_stats = $stmt_doc->get_result();

// 4. Low Stock Alert
$low_stock = $mysqli->query("SELECT * FROM medicine_master WHERE stock_qty <= reorder_level AND is_active = 1");

include '../../includes/header.php';
?>

<h2>Detailed Reports for <?php echo $month; ?></h2>

<form method="GET" class="mb-4">
    <label>Select Month:</label>
    <input type="month" name="month" value="<?php echo $month; ?>" onchange="this.form.submit()">
</form>

<div class="row">
    <div class="col-md-4">
        <div class="card bg-success text-white">
            <div class="card-body text-center"><h5>Monthly Total Revenue</h5><h3><?php echo number_format($monthly_rev, 2); ?></h3></div>
        </div>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">Service-wise Revenue Breakdown</div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr><td>Registration</td><td><?php echo number_format($services['reg'] ?? 0, 2); ?></td></tr>
                    <tr><td>Consultation</td><td><?php echo number_format($services['cons'] ?? 0, 2); ?></td></tr>
                    <tr><td>Medicine</td><td><?php echo number_format($services['med'] ?? 0, 2); ?></td></tr>
                    <tr><td>Lab</td><td><?php echo number_format($services['lab'] ?? 0, 2); ?></td></tr>
                    <tr><td>Vaccination</td><td><?php echo number_format($services['vacc'] ?? 0, 2); ?></td></tr>
                    <tr><td>Imaging (X-Ray/Scan)</td><td><?php echo number_format($services['xray'] ?? 0, 2); ?></td></tr>
                    <tr><td>Surgery/Procedures</td><td><?php echo number_format($services['surg'] ?? 0, 2); ?></td></tr>
                    <tr><td>Other Charges</td><td><?php echo number_format($services['other'] ?? 0, 2); ?></td></tr>
                </table>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header text-danger">Low Stock Alerts</div>
            <div class="card-body">
                <table class="table table-sm">
                    <thead><tr><th>Item</th><th>Stock</th><th>Reorder</th></tr></thead>
                    <tbody>
                        <?php while($ls = $low_stock->fetch_assoc()): ?>
                            <tr><td><?php echo $ls['med_name']; ?></td><td><?php echo $ls['stock_qty']; ?></td><td><?php echo $ls['reorder_level']; ?></td></tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header">Doctor Consultation Statistics</div>
    <div class="card-body">
        <table class="table">
            <thead><tr><th>Doctor</th><th>Appts Count</th><th>Total Consultation Fees</th></tr></thead>
            <tbody>
                <?php while($ds = $doc_stats->fetch_assoc()): ?>
                    <tr><td><?php echo $ds['full_name']; ?></td><td><?php echo $ds['count']; ?></td><td><?php echo number_format($ds['total_fees'], 2); ?></td></tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
