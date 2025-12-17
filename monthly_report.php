<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../partials/header.php';

$year  = intval($_GET['year'] ?? date('Y'));
$month = intval($_GET['month'] ?? date('m'));

$stmt = $conn->prepare("
    SELECT DATE(sale_date) AS d, SUM(total_amount) AS total
    FROM sales
    WHERE YEAR(sale_date) = ? AND MONTH(sale_date) = ?
    GROUP BY DATE(sale_date)
    ORDER BY d
");
$stmt->bind_param("ii", $year, $month);
$stmt->execute();
$result = $stmt->get_result();

$sumStmt = $conn->prepare("
    SELECT SUM(total_amount) AS t
    FROM sales
    WHERE YEAR(sale_date) = ? AND MONTH(sale_date) = ?
");
$sumStmt->bind_param("ii", $year, $month);
$sumStmt->execute();
$sumRow = $sumStmt->get_result()->fetch_assoc();
$total = $sumRow['t'] ?? 0;
?>
<div class="card">
    <h2>Monthly Sales Report</h2>
    <form method="get">
        <label>Year</label>
        <input type="number" name="year" value="<?= $year ?>">

        <label>Month</label>
        <input type="number" min="1" max="12" name="month" value="<?= $month ?>">

        <button class="btn small" type="submit">View</button>
    </form>
</div>

<div class="card">
    <h3>Sales in <?= $month ?>/<?= $year ?></h3>
    <table>
        <tr>
            <th>Date</th>
            <th>Total (₹)</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $row['d'] ?></td>
                <td><?= number_format($row['total'], 2) ?></td>
            </tr>
        <?php endwhile; ?>
        <tr>
            <th style="text-align:right;">Month Total</th>
            <th>₹ <?= number_format($total, 2) ?></th>
        </tr>
    </table>
</div>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
