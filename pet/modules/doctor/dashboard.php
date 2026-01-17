<?php
require_once '../../config/db.php';
require_once '../../includes/auth_functions.php';
checkRole('Doctor');
include '../../includes/header.php';

$doctor_id = $_SESSION['user_id'];
$today = date('Y-m-d');

$stmt = $mysqli->prepare("
    SELECT c.*, p.petnam, p.Pettyp, p.ownnam
    FROM consultations c
    JOIN pet_registration p ON c.RegNo = p.RegNo
    WHERE c.doctor_id = ? AND DATE(c.consult_date) = ?
    ORDER BY c.consult_id ASC
");
$stmt->bind_param("is", $doctor_id, $today);
$stmt->execute();
$appointments = $stmt->get_result();
?>

<h2>Doctor Dashboard - Today's Appointments</h2>

<div class="card mt-4">
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
