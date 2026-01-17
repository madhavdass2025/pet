<?php
require_once 'config/db.php';
require_once 'includes/auth_functions.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $mysqli->prepare("SELECT user_id, username, password, full_name, role FROM users WHERE username = ? AND is_active = 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($user = $result->fetch_assoc()) {
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];

            // Log activity (audit trail)
            $stmt_log = $mysqli->prepare("INSERT INTO audit_log (user_id, action) VALUES (?, 'Logged in')");
            $stmt_log->bind_param("i", $user['user_id']);
            $stmt_log->execute();

            redirectByRole($user['role']);
        } else {
            header("Location: index.php?error=Invalid password");
        }
    } else {
        header("Location: index.php?error=User not found");
    }
    exit();
}
?>
