<?php
require_once '../../config/db.php';
require_once '../../includes/auth_functions.php';
checkRole(['Front Desk', 'Accountant', 'Admin']);
include '../../includes/header.php';

$today = date('Y-m-d');
$report = $mysqli->query("
    SELECT
        DATE(transaction_date) as t_date,
        COUNT(payment_id) as total_transactions,
        SUM(cash_amount) as total_cash,
        SUM(card_amount) as total_card,
        SUM(upi_amount) as total_upi,
        SUM(credit_amount) as total_credit,
        SUM(total_amount) as grand_total
    FROM payment_transactions
    WHERE DATE(transaction_date) = '$today'
    GROUP BY DATE(transaction_date)
")->fetch_assoc();

$credits = $mysqli->query("
    SELECT ct.*, pr.petnam, pr.ownnam, pr.ownmob
    FROM credit_transactions ct
    JOIN pet_registration pr ON ct.RegNo = pr.RegNo
    WHERE ct.status = 'pending'
");

$vacc_due = $mysqli->query("
    SELECT vh.RegNo, pr.petnam, pr.ownnam, pr.ownmob, vh.vacc_name, vh.next_due_date, DATEDIFF(vh.next_due_date, CURDATE()) as days_remaining
    FROM vaccination_history vh
    JOIN pet_registration pr ON vh.RegNo = pr.RegNo
    WHERE vh.next_due_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
    ORDER BY vh.next_due_date
");

$review_list = $mysqli->query("
    SELECT d.RegNo, pr.petnam, pr.ownnam, pr.ownmob, d.next_review_date, DATEDIFF(d.next_review_date, CURDATE()) as days_to_review
    FROM diagnosis d
    JOIN pet_registration pr ON d.RegNo = pr.RegNo
    WHERE d.next_review_date IS NOT NULL AND d.next_review_date >= CURDATE()
    ORDER BY d.next_review_date
");
?>

<h2>Daily Collection Report (<?php echo $today; ?>)</h2>

<?php if ($report): ?>
    <div class="row mt-4">
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body"><h5>Total Cash</h5><h3><?php echo number_format($report['total_cash'], 2); ?></h3></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body"><h5>Total UPI</h5><h3><?php echo number_format($report['total_upi'], 2); ?></h3></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body"><h5>Total Card</h5><h3><?php echo number_format($report['total_card'], 2); ?></h3></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-danger text-white">
                <div class="card-body"><h5>Total Credit</h5><h3><?php echo number_format($report['total_credit'], 2); ?></h3></div>
            </div>
        </div>
    </div>
    <div class="alert alert-dark mt-3">
        <h4>Grand Total: <?php echo number_format($report['grand_total'], 2); ?> (Transactions: <?php echo $report['total_transactions']; ?>)</h4>
    </div>
<?php else: ?>
    <div class="alert alert-warning">No transactions recorded today.</div>
<?php endif; ?>

<h2 class="mt-5">Pending Credits</h2>
<table class="table table-striped">
    <thead>
        <tr>
            <th>Date</th>
            <th>Pet/Owner</th>
            <th>Mobile</th>
            <th>Amount</th>
            <th>Balance</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>
        <?php while($c = $credits->fetch_assoc()): ?>
            <tr>
                <td><?php echo $c['credit_date']; ?></td>
                <td><?php echo $c['petnam']; ?> / <?php echo $c['ownnam']; ?></td>
                <td><?php echo $c['ownmob']; ?></td>
                <td><?php echo $c['credit_amount']; ?></td>
                <td><?php echo $c['balance']; ?></td>
                <td><button class="btn btn-sm btn-outline-success">Pay Now</button></td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<h2 class="mt-5">Vaccination Due (Next 30 Days)</h2>
<table class="table table-striped">
    <thead>
        <tr>
            <th>Pet</th>
            <th>Owner</th>
            <th>Mobile</th>
            <th>Vaccine</th>
            <th>Due Date</th>
            <th>Days Remaining</th>
        </tr>
    </thead>
    <tbody>
        <?php while($v = $vacc_due->fetch_assoc()): ?>
            <tr>
                <td><?php echo $v['petnam']; ?></td>
                <td><?php echo $v['ownnam']; ?></td>
                <td><?php echo $v['ownmob']; ?></td>
                <td><?php echo $v['vacc_name']; ?></td>
                <td><?php echo $v['next_due_date']; ?></td>
                <td><?php echo $v['days_remaining']; ?></td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<h2 class="mt-5">Review Call List</h2>
<table class="table table-striped">
    <thead>
        <tr>
            <th>Pet</th>
            <th>Owner</th>
            <th>Mobile</th>
            <th>Review Date</th>
            <th>Days Remaining</th>
        </tr>
    </thead>
    <tbody>
        <?php while($r = $review_list->fetch_assoc()): ?>
            <tr>
                <td><?php echo $r['petnam']; ?></td>
                <td><?php echo $r['ownnam']; ?></td>
                <td><?php echo $r['ownmob']; ?></td>
                <td><?php echo $r['next_review_date']; ?></td>
                <td><?php echo $r['days_to_review']; ?></td>
            </tr>
        <?php endwhile; ?>
    </tbody>
</table>

<?php include '../../includes/footer.php'; ?>
