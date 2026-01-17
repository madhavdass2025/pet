<?php
require_once '../../config/db.php';
require_once '../../includes/auth_functions.php';
checkRole('Front Desk');

$consult_id = $_GET['consult_id'] ?? '';
if (!$consult_id) { header("Location: dashboard.php"); exit(); }

$stmt = $mysqli->prepare("
    SELECT c.*, p.petnam, p.ownnam
    FROM consultations c
    JOIN pet_registration p ON c.RegNo = p.RegNo
    WHERE c.consult_id = ?
");
$stmt->bind_param("i", $consult_id);
$stmt->execute();
$data = $stmt->get_result()->fetch_assoc();

// Calculate totals
$stmt_med = $mysqli->prepare("SELECT SUM(total_amount) as total FROM medicine_dispensing WHERE consult_id = ?");
$stmt_med->bind_param("i", $consult_id);
$stmt_med->execute();
$med_charges = $stmt_med->get_result()->fetch_assoc()['total'] ?: 0;

$stmt_lab = $mysqli->prepare("SELECT SUM(test_fee) as total FROM lab_orders WHERE consult_id = ? AND payment_status = 'pending'");
$stmt_lab->bind_param("i", $consult_id);
$stmt_lab->execute();
$lab_charges = $stmt_lab->get_result()->fetch_assoc()['total'] ?: 0;

$stmt_vacc = $mysqli->prepare("SELECT SUM(fee) as total FROM vaccination_orders WHERE consult_id = ? AND payment_status = 'pending'");
$stmt_vacc->bind_param("i", $consult_id);
$stmt_vacc->execute();
$vacc_charges = $stmt_vacc->get_result()->fetch_assoc()['total'] ?: 0;

$stmt_imag = $mysqli->prepare("SELECT SUM(fee) as total FROM imaging_orders WHERE consult_id = ? AND payment_status = 'pending'");
$stmt_imag->bind_param("i", $consult_id);
$stmt_imag->execute();
$imag_charges = $stmt_imag->get_result()->fetch_assoc()['total'] ?: 0;

$stmt_surg = $mysqli->prepare("SELECT SUM(fee) as total FROM surgery_orders WHERE consult_id = ? AND payment_status = 'pending'");
$stmt_surg->bind_param("i", $consult_id);
$stmt_surg->execute();
$surg_charges = $stmt_surg->get_result()->fetch_assoc()['total'] ?: 0;

$total_due = $med_charges + $lab_charges + $vacc_charges + $imag_charges + $surg_charges;

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $mysqli->begin_transaction();
    try {
        $reg_no = $data['RegNo'];
        $paid_amount = $_POST['paid_amount'];
        $cash = $_POST['cash_amount'] ?: 0;
        $card = $_POST['card_amount'] ?: 0;
        $upi = $_POST['upi_amount'] ?: 0;
        $credit = $_POST['credit_amount'] ?: 0;

        $res_receipt = $mysqli->query("SELECT COUNT(*) as cnt FROM payment_transactions WHERE DATE(transaction_date) = CURDATE()");
        $receipt_count = $res_receipt->fetch_assoc()['cnt'] + 1;
        $receipt_no = "REC-" . date('Ymd') . "-" . str_pad($receipt_count, 4, '0', STR_PAD_LEFT);

        $stmt_pay = $mysqli->prepare("INSERT INTO payment_transactions (RegNo, consult_id, transaction_date, receipt_no, medicine_charges, lab_charges, vaccination_charges, xray_charges, surgery_charges, subtotal, total_amount, cash_amount, card_amount, upi_amount, credit_amount, paid_amount, balance_amount, collected_by) VALUES (?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $balance = $total_due - $paid_amount;
        $stmt_pay->bind_param("sisddddddddddddddi",
            $reg_no, $consult_id, $receipt_no, $med_charges, $lab_charges, $vacc_charges, $imag_charges, $surg_charges, $total_due, $total_due,
            $cash, $card, $upi, $credit, $paid_amount, $balance, $_SESSION['user_id']
        );
        $stmt_pay->execute();
        $payment_id = $stmt_pay->insert_id;

        if ($credit > 0) {
            $stmt_credit = $mysqli->prepare("INSERT INTO credit_transactions (RegNo, payment_id, credit_date, credit_amount, balance, status, created_by) VALUES (?, ?, CURDATE(), ?, ?, 'pending', ?)");
            $stmt_credit->bind_param("siddi", $reg_no, $payment_id, $credit, $credit, $_SESSION['user_id']);
            $stmt_credit->execute();
        }

        // Update payment status for all services
        $mysqli->query("UPDATE lab_orders SET payment_status = 'paid' WHERE consult_id = $consult_id");
        $mysqli->query("UPDATE vaccination_orders SET payment_status = 'paid' WHERE consult_id = $consult_id");
        $mysqli->query("UPDATE imaging_orders SET payment_status = 'paid' WHERE consult_id = $consult_id");
        $mysqli->query("UPDATE surgery_orders SET payment_status = 'paid' WHERE consult_id = $consult_id");

        $mysqli->commit();
        echo "<script>alert('Final Payment Successful. Receipt: $receipt_no'); window.location.href='dashboard.php';</script>";
        exit();
    } catch (Exception $e) {
        $mysqli->rollback();
        echo "Error: " . $e->getMessage();
    }
}

include '../../includes/header.php';
?>

<h3>Final Payment Collection</h3>
<p>Patient: <?php echo $data['petnam']; ?> (<?php echo $data['RegNo']; ?>)</p>

<div class="card">
    <div class="card-body">
        <form method="POST">
            <div class="row">
                <div class="col-md-6">
                    <h5>Charges Breakdown</h5>
                    <table class="table">
                        <tr><td>Medicine Charges</td><td><?php echo number_format($med_charges, 2); ?></td></tr>
                        <tr><td>Lab Charges</td><td><?php echo number_format($lab_charges, 2); ?></td></tr>
                        <tr><td>Vaccination Charges</td><td><?php echo number_format($vacc_charges, 2); ?></td></tr>
                        <tr><td>Imaging Charges</td><td><?php echo number_format($imag_charges, 2); ?></td></tr>
                        <tr><td>Surgery Charges</td><td><?php echo number_format($surg_charges, 2); ?></td></tr>
                        <tr class="table-dark"><td>Total Due</td><td><?php echo number_format($total_due, 2); ?></td></tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <h5>Payment Modes</h5>
                    <div class="mb-2"><label>Cash</label><input type="number" name="cash_amount" class="form-control pay-mode" value="0" oninput="calculatePaid()"></div>
                    <div class="mb-2"><label>Card</label><input type="number" name="card_amount" class="form-control pay-mode" value="0" oninput="calculatePaid()"></div>
                    <div class="mb-2"><label>UPI</label><input type="number" name="upi_amount" class="form-control pay-mode" value="0" oninput="calculatePaid()"></div>
                    <div class="mb-2"><label>Credit</label><input type="number" name="credit_amount" class="form-control pay-mode" value="0" oninput="calculatePaid()"></div>
                    <hr>
                    <div class="mb-2"><label>Total Paid</label><input type="number" name="paid_amount" id="paid_amount" class="form-control" value="0" readonly></div>
                    <button type="submit" class="btn btn-primary w-100">Collect Final Payment</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
function calculatePaid() {
    let modes = document.querySelectorAll('.pay-mode');
    let total = 0;
    modes.forEach(m => {
        total += parseFloat(m.value) || 0;
    });
    document.getElementById('paid_amount').value = total;
}
</script>

<?php include '../../includes/footer.php'; ?>
