<?php
require_once '../../config/db.php';
require_once '../../includes/auth_functions.php';
checkRole('Doctor');
include '../../includes/header.php';

$doctor_id = $_SESSION['user_id'];
$today = date('Y-m-d');
$status_filter = $_GET['status'] ?? '';

$query = "
    SELECT c.*, p.petnam, p.Pettyp, p.ownnam
    FROM consultations c
    JOIN pet_registration p ON c.RegNo = p.RegNo
    WHERE c.doctor_id = ? AND DATE(c.consult_date) = ?
";

if ($status_filter) {
    $query .= " AND c.status = ?";
}
$query .= " ORDER BY c.consult_id ASC";

$stmt = $mysqli->prepare($query);
if ($status_filter) {
    $stmt->bind_param("iss", $doctor_id, $today, $status_filter);
} else {
    $stmt->bind_param("is", $doctor_id, $today);
}
$stmt->execute();
$appointments = $stmt->get_result();
?>

<div class="d-flex justify-content-between align-items-center">
    <h2>Doctor Dashboard - Today's Appointments</h2>
    <form action="search.php" method="GET" class="d-flex">
        <input type="text" name="q" class="form-control me-2" placeholder="Search Patient/RegNo">
        <button type="submit" class="btn btn-outline-primary">Search</button>
    </form>
</div>

<div class="row mt-3">
    <div class="col-md-12">
        <div class="btn-group" role="group">
            <a href="dashboard.php" class="btn btn-outline-secondary <?php echo !$status_filter ? 'active' : ''; ?>">All</a>
            <a href="dashboard.php?status=scheduled" class="btn btn-outline-primary <?php echo $status_filter == 'scheduled' ? 'active' : ''; ?>">Scheduled</a>
            <a href="dashboard.php?status=in-progress" class="btn btn-outline-warning <?php echo $status_filter == 'in-progress' ? 'active' : ''; ?>">In Progress</a>
            <a href="dashboard.php?status=completed" class="btn btn-outline-success <?php echo $status_filter == 'completed' ? 'active' : ''; ?>">Completed</a>
        </div>
    </div>
</div>

<div class="card mt-3">
    <div class="card-body">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Time</th>
                    <th>Consult No</th>
                    <th>Pet Name</th>
                    <th>Type</th>
                    <th>Owner</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while($app = $appointments->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo date('H:i', strtotime($app['consult_date'])); ?></td>
                        <td><?php echo $app['consult_no']; ?></td>
                        <td><?php echo htmlspecialchars($app['petnam']); ?></td>
                        <td><?php echo htmlspecialchars($app['Pettyp']); ?></td>
                        <td><?php echo htmlspecialchars($app['ownnam']); ?></td>
                        <td>
                            <span class="badge bg-<?php echo ($app['status'] == 'completed') ? 'success' : (($app['status'] == 'in-progress') ? 'warning' : 'primary'); ?>">
                                <?php echo ucfirst($app['status']); ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($app['status'] != 'completed'): ?>
                                <a href="consultation.php?consult_id=<?php echo $app['consult_id']; ?>" class="btn btn-sm btn-primary">Start Consultation</a>
                            <?php else: ?>
                                <a href="consultation.php?consult_id=<?php echo $app['consult_id']; ?>" class="btn btn-sm btn-info">View Details</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../../includes/footer.php'; ?>
