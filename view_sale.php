<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../partials/header.php';

$id = intval($_GET['id'] ?? 0);

$stmt = $conn->prepare("
    SELECT s.*, c.name AS customer_name, c.phone, c.email
    FROM sales s
    LEFT JOIN customers c ON s.customer_id = c.id
    WHERE s.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$sale = $stmt->get_result()->fetch_assoc();

if (!$sale) {
    echo "<div class='card'><div class='alert error'>Sale not found.</div></div>";
    require_once __DIR__ . '/../../partials/footer.php';
    exit;
}

$itemsStmt = $conn->prepare("
    SELECT si.*, p.name
    FROM sale_items si
    JOIN products p ON si.product_id = p.id
    WHERE si.sale_id = ?
");
$itemsStmt->bind_param("i", $id);
$itemsStmt->execute();
$items = $itemsStmt->get_result();
?>

<div class="card">
    <h2>Bill #<?= $sale['id'] ?></h2>
    <p>Date: <?= $sale['sale_date'] ?></p>
    <p>Customer: <?= htmlspecialchars($sale['customer_name'] ?? 'Walk-in') ?></p>
    <p>Phone: <?= htmlspecialchars($sale['phone'] ?? '-') ?> | Email: <?= htmlspecialchars($sale['email'] ?? '-') ?></p>
    <a class="btn small secondary" href="print_bill.php?id=<?= $sale['id'] ?>" target="_blank">Print</a>
</div>

<div class="card">
    <table>
        <tr>
            <th>#</th>
            <th>Product</th>
            <th>Qty</th>
            <th>Price (₹)</th>
            <th>Total (₹)</th>
        </tr>
        <?php
        $i = 1;
        while ($it = $items->fetch_assoc()):
            $lineTotal = $it['quantity'] * $it['unit_price'];
        ?>
            <tr>
                <td><?= $i++ ?></td>
                <td><?= htmlspecialchars($it['name']) ?></td>
                <td><?= $it['quantity'] ?></td>
                <td><?= number_format($it['unit_price'], 2) ?></td>
                <td><?= number_format($lineTotal, 2) ?></td>
            </tr>
        <?php endwhile; ?>
        <tr>
            <th colspan="4" style="text-align:right;">Grand Total</th>
            <th>₹ <?= number_format($sale['total_amount'], 2) ?></th>
        </tr>
    </table>
</div>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
