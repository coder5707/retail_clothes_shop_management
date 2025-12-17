<?php
require_once __DIR__ . '/../../config/db.php';

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
    die("Sale not found.");
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
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Print Bill #<?= $sale['id'] ?></title>
    <style>
        body { font-family: Arial, sans-serif; }
        .bill { width: 700px; margin: 0 auto; }
        h2, h3 { text-align: center; margin: 5px 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #000; padding: 4px; font-size: 13px; }
        .text-right { text-align: right; }
        .small { font-size: 12px; }
        .header { margin-bottom: 10px; }
    </style>
</head>
<body onload="window.print()">
<div class="bill">
    <h2>Retail Clothes Shop</h2>
    <h3>Invoice / Bill</h3>
    <div class="header small">
        <p>Bill No: <?= $sale['id'] ?> &nbsp; | &nbsp; Date: <?= $sale['sale_date'] ?></p>
        <p>Customer: <?= htmlspecialchars($sale['customer_name'] ?? 'Walk-in') ?></p>
        <p>Phone: <?= htmlspecialchars($sale['phone'] ?? '-') ?> | Email: <?= htmlspecialchars($sale['email'] ?? '-') ?></p>
    </div>
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
                <td class="text-right"><?= number_format($it['unit_price'], 2) ?></td>
                <td class="text-right"><?= number_format($lineTotal, 2) ?></td>
            </tr>
        <?php endwhile; ?>
        <tr>
            <th colspan="4" class="text-right">Grand Total</th>
            <th class="text-right">₹ <?= number_format($sale['total_amount'], 2) ?></th>
        </tr>
    </table>
    <p class="small">Thank you for shopping with us.</p>
</div>
</body>
</html>
