<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../partials/header.php';

$message = "";
$error = "";

// Add customer
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if ($name === '') {
        $error = "Name is required.";
    } else {
        $stmt = $conn->prepare("INSERT INTO customers (name, phone, email) VALUES (?,?,?)");
        $stmt->bind_param("sss", $name, $phone, $email);
        if ($stmt->execute()) {
            $message = "Customer added.";
        } else {
            $error = "Failed to add customer.";
        }
    }
}

$result = $conn->query("SELECT * FROM customers ORDER BY id DESC");
?>
<div class="card">
    <h2>Customers</h2>
    <?php if ($error): ?><div class="alert error"><?= $error ?></div><?php endif; ?>
    <?php if ($message): ?><div class="alert success"><?= $message ?></div><?php endif; ?>
</div>

<div class="card">
    <h3>Add Customer</h3>
    <form method="post">
        <label>Name</label>
        <input type="text" name="name" required>

        <label>Phone</label>
        <input type="text" name="phone">

        <label>Email</label>
        <input type="email" name="email">

        <br><br>
        <button class="btn" type="submit">Add Customer</button>
    </form>
</div>

<div class="card">
    <h3>Customer List</h3>
    <table>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Phone</th>
            <th>Email</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td><?= htmlspecialchars($row['phone']) ?></td>
                <td><?= htmlspecialchars($row['email']) ?></td>
            </tr>
        <?php endwhile; ?>
    </table>
</div>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
