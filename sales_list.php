<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../partials/header.php';

$result = $conn->query("
    SELECT s.id, s.sale_date, s.total_amount, c.name AS customer_name
    FROM sales s
    LEFT JOIN customers c ON s.customer_id = c.id
    ORDER BY s.sale_date DESC
");
?>
<div class="card">
    <h2>Sales History</h2>
</div>

<div class="card">
    <table>
        <tr>
            <th>Bill ID</th>
            <th>Date/Time</th>
            <th>Customer</th>
            <th>Total (₹)</th>
            <th>Action</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= $row['sale_date'] ?></td>
                <td><?= htmlspecialchars($row['customer_name'] ?? 'Walk-in') ?></td>
                <td><?= number_format($row['total_amount'], 2) ?></td>
                <td><a class="btn small secondary" href="view_sale.php?id=<?= $row['id'] ?>">View</a></td>
            </tr>
        <?php endwhile; ?>
    </table>
</div>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
