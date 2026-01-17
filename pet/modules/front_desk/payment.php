<?php
require_once '../../config/db.php';
require_once '../../includes/auth_functions.php';
checkRole('Front Desk');

$consult_id = $_GET['consult_id'] ?? '';
$registration_id = $_GET['registration_id'] ?? ''; // In case of just reg fee

$data = null;
if ($consult_id) {
    $stmt = $mysqli->prepare("
        SELECT c.*, p.petnam, p.ownnam, p.registration_fee as reg_fee
        FROM consultations c
        JOIN pet_registration p ON c.RegNo = p.RegNo
        WHERE c.consult_id = ?
    ");
    $stmt->bind_param("i", $consult_id);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $mysqli->begin_transaction();
    try {
        $reg_no = $_POST['RegNo'];
        $consult_id = $_POST['consult_id'] ?: null;
        $reg_fee = $_POST['registration_fee'] ?: 0;
        $consult_fee = $_POST['consultation_fee'] ?: 0;
        $total_amount = $_POST['total_amount'];
        $paid_amount = $_POST['paid_amount'];
        $discount = $_POST['discount'] ?: 0;

        $cash = $_POST['cash_amount'] ?: 0;
        $card = $_POST['card_amount'] ?: 0;
        $upi = $_POST['upi_amount'] ?: 0;
        $credit = $_POST['credit_amount'] ?: 0;

        // Receipt No Generation
        $res_receipt = $mysqli->query("SELECT COUNT(*) as cnt FROM payment_transactions WHERE DATE(transaction_date) = CURDATE()");
        $receipt_count = $res_receipt->fetch_assoc()['cnt'] + 1;
        $receipt_no = "REC-" . date('Ymd') . "-" . str_pad($receipt_count, 4, '0', STR_PAD_LEFT);

        $stmt_pay = $mysqli->prepare("INSERT INTO payment_transactions (RegNo, consult_id, transaction_date, receipt_no, registration_fee, consultation_fee, subtotal, discount, total_amount, cash_amount, card_amount, upi_amount, credit_amount, paid_amount, balance_amount, collected_by) VALUES (?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $subtotal = $reg_fee + $consult_fee;
        $balance = $total_amount - $paid_amount;

        $stmt_pay->bind_param("sisdddddddddddi",
            $reg_no, $consult_id, $receipt_no, $reg_fee, $consult_fee, $subtotal, $discount, $total_amount,
            $cash, $card, $upi, $credit, $paid_amount, $balance, $_SESSION['user_id']
        );
        $stmt_pay->execute();
        $payment_id = $stmt_pay->insert_id;

        if ($credit > 0) {
            $stmt_credit = $mysqli->prepare("INSERT INTO credit_transactions (RegNo, payment_id, credit_date, credit_amount, balance, status, created_by) VALUES (?, ?, CURDATE(), ?, ?, 'pending', ?)");
            $stmt_credit->bind_param("siddi", $reg_no, $payment_id, $credit, $credit, $_SESSION['user_id']);
            $stmt_credit->execute();
        }

        // Update consultation status if applicable
        if ($consult_id) {
            $mysqli->query("UPDATE consultations SET status = 'scheduled' WHERE consult_id = $consult_id");
        }

        $mysqli->commit();
        echo "<script>alert('Payment Successful. Receipt: $receipt_no'); window.location.href='dashboard.php';</script>";
        exit();
    } catch (Exception $e) {
        $mysqli->rollback();
        echo "Error: " . $e->getMessage();
    }
}

include '../../includes/header.php';
?>

<h2>Payment Collection</h2>
<?php if ($data): ?>
    <div class="card">
        <div class="card-body">
            <h5 class="card-title">Patient: <?php echo htmlspecialchars($data['petnam']); ?> (<?php echo htmlspecialchars($data['RegNo']); ?>)</h5>
            <p>Owner: <?php echo htmlspecialchars($data['ownnam']); ?></p>
            <form method="POST">
                <input type="hidden" name="RegNo" value="<?php echo $data['RegNo']; ?>">
                <input type="hidden" name="consult_id" value="<?php echo $data['consult_id']; ?>">

                <div class="row">
                    <div class="col-md-6">
                        <table class="table">
                            <tr>
                                <th>Description</th>
                                <th>Amount</th>
                            </tr>
                            <tr>
                                <td>Registration Fee</td>
                                <td><input type="number" name="registration_fee" class="form-control" value="<?php echo $data['reg_fee']; ?>" readonly></td>
                            </tr>
                            <tr>
                                <td>Consultation Fee <?php echo $data['fee_waived'] ? '(Waived)' : ''; ?></td>
                                <td><input type="number" name="consultation_fee" class="form-control" value="<?php echo $data['consultation_fee']; ?>" readonly></td>
                            </tr>
                            <tr>
                                <th>Total</th>
                                <td><input type="number" name="total_amount" id="total_amount" class="form-control" value="<?php echo $data['reg_fee'] + $data['consultation_fee']; ?>" readonly></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h5>Payment Details</h5>
                        <div class="mb-2">
                            <label>Discount</label>
                            <input type="number" name="discount" id="discount" class="form-control" value="0" oninput="calculateTotal()">
                        </div>
                        <div class="mb-2">
                            <label>Cash Amount</label>
                            <input type="number" name="cash_amount" class="form-control pay-mode" value="0" oninput="calculatePaid()">
                        </div>
                        <div class="mb-2">
                            <label>Card Amount</label>
                            <input type="number" name="card_amount" class="form-control pay-mode" value="0" oninput="calculatePaid()">
                        </div>
                        <div class="mb-2">
                            <label>UPI Amount</label>
                            <input type="number" name="upi_amount" class="form-control pay-mode" value="0" oninput="calculatePaid()">
                        </div>
                        <div class="mb-2">
                            <label>Credit Amount</label>
                            <input type="number" name="credit_amount" class="form-control pay-mode" value="0" oninput="calculatePaid()">
                        </div>
                        <hr>
                        <div class="mb-2">
                            <label>Paid Amount</label>
                            <input type="number" name="paid_amount" id="paid_amount" class="form-control" value="0" readonly>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Collect Payment & Print Receipt</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
<?php else: ?>
    <div class="alert alert-warning">No pending initial payment found for this consultation.</div>
<?php endif; ?>

<script>
function calculateTotal() {
    let subtotal = <?php echo ($data['reg_fee'] ?? 0) + ($data['consultation_fee'] ?? 0); ?>;
    let discount = parseFloat(document.getElementById('discount').value) || 0;
    document.getElementById('total_amount').value = subtotal - discount;
    calculatePaid();
}

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
