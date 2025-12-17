<?php
session_start();
require_once __DIR__ . '/../config/db.php';

$error = "";
$selectedRole = $_POST['login_type'] ?? 'admin';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // CUSTOMER LOGIN (NO DATABASE CHECK)
    if ($selectedRole === 'user') {
        header("Location: ../customer/index.php");
        exit;
    }

    // ADMIN / STAFF LOGIN
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($username && $password) {
        $stmt = $conn->prepare("SELECT id, username, password, role FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($row = $res->fetch_assoc()) {
            if ($row['password'] === md5($password)) {
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['role'] = $row['role'];

                header("Location: ../modules/dashboard/dashboard.php");
                exit;
            }
        }
        $error = "Invalid username or password.";
    } else {
        $error = "Enter username and password.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Retail Shop - Login</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body class="login-body">

<div class="login-container">
    <h2>Retail Shop Login</h2>

    <?php if ($error): ?>
        <div class="alert error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <!-- ======================
         LOGIN TYPE SELECTION
    ======================= -->
    <form method="post">

        <label>Login As</label>
        <select name="login_type" required>
            <option value="admin" <?= ($selectedRole === 'admin') ? 'selected' : '' ?>>Admin</option>
            <option value="user" <?= ($selectedRole === 'user') ? 'selected' : '' ?>>Customer</option>
        </select>

        <div id="adminFields">

            <label>Username</label>
            <input type="text" name="username">

            <label>Password</label>
            <input type="password" name="password">

            <p class="hint">Default Admin: <b>admin / admin123</b></p>

        </div>

        <br>
        <button type="submit" class="btn">Login</button>

    </form>
</div>

<script>
    const selectRole = document.querySelector("select[name='login_type']");
    const adminFields = document.getElementById("adminFields");

    function toggleFields() {
        if (selectRole.value === "user") {
            adminFields.style.display = "none";
        } else {
            adminFields.style.display = "block";
        }
    }

    toggleFields();
    selectRole.addEventListener("change", toggleFields);
</script>

</body>
</html>
