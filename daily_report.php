<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../partials/header.php';

$date = $_GET['date'] ?? date('Y-m-d');

$stmt = $conn->prepare("
    SELECT s.id, s.sale_date, s.total_amount, c.name AS customer_name
    FROM sales s
    LEFT JOIN customers c ON s.customer_id = c.id
    WHERE DATE(s.sale_date) = ?
    ORDER BY s.sale_date DESC
");
$stmt->bind_param("s", $date);
$stmt->execute();
$result = $stmt->get_result();

$sumStmt = $conn->prepare("SELECT SUM(total_amount) AS t FROM sales WHERE DATE(sale_date) = ?");
$stmt2 = $sumStmt;
$sumStmt->bind_param("s", $date);
$sumStmt->execute();
$sumRow = $sumStmt->get_result()->fetch_assoc();
$total = $sumRow['t'] ?? 0;
?>
<div class="card">
    <h2>Daily Sales Report</h2>
    <form method="get">
        <label>Select Date</label>
        <input type="date" name="date" value="<?= htmlspecialchars($date) ?>">
        <button class="btn small" type="submit">View</button>
    </form>
</div>

<div class="card">
    <h3>Sales on <?= htmlspecialchars($date) ?></h3>
    <table>
        <tr>
            <th>Bill ID</th>
            <th>Date/Time</th>
            <th>Customer</th>
            <th>Total (₹)</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= $row['sale_date'] ?></td>
                <td><?= htmlspecialchars($row['customer_name'] ?? 'Walk-in') ?></td>
                <td><?= number_format($row['total_amount'], 2) ?></td>
            </tr>
        <?php endwhile; ?>
        <tr>
            <th colspan="3" style="text-align:right;">Day Total</th>
            <th>₹ <?= number_format($total, 2) ?></th>
        </tr>
    </table>
</div>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
