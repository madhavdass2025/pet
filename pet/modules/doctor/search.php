<?php
require_once '../../config/db.php';
require_once '../../includes/auth_functions.php';
checkRole('Doctor');
include '../../includes/header.php';

$q = $_GET['q'] ?? '';
$results = [];

if ($q) {
    $search = "%$q%";
    $stmt = $mysqli->prepare("SELECT * FROM pet_registration WHERE petnam LIKE ? OR ownnam LIKE ? OR RegNo LIKE ? OR ownmob LIKE ?");
    $stmt->bind_param("ssss", $search, $search, $search, $search);
    $stmt->execute();
    $results = $stmt->get_result();
}
?>

<h2>Patient Search</h2>
<form method="GET" class="row g-3 mt-3">
    <div class="col-md-8">
        <input type="text" name="q" class="form-control" placeholder="Enter Pet Name, Owner Name, RegNo or Mobile" value="<?php echo htmlspecialchars($q); ?>">
    </div>
    <div class="col-md-4">
        <button type="submit" class="btn btn-primary">Search</button>
    </div>
</form>

<?php if ($q): ?>
    <div class="mt-4">
        <h5>Results for "<?php echo htmlspecialchars($q); ?>"</h5>
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>RegNo</th>
                    <th>Pet Name</th>
                    <th>Type</th>
                    <th>Owner</th>
                    <th>Mobile</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while($r = $results->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $r['RegNo']; ?></td>
                        <td><?php echo htmlspecialchars($r['petnam']); ?></td>
                        <td><?php echo htmlspecialchars($r['Pettyp']); ?></td>
                        <td><?php echo htmlspecialchars($r['ownnam']); ?></td>
                        <td><?php echo $r['ownmob']; ?></td>
                        <td>
                            <a href="view_case_sheet.php?reg_no=<?php echo $r['RegNo']; ?>" class="btn btn-sm btn-info">View Case History</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
                <?php if ($results->num_rows == 0): ?>
                    <tr><td colspan="6" class="text-center">No patients found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php include '../../includes/footer.php'; ?>
