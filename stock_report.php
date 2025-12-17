<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../partials/header.php';

$result = $conn->query("
    SELECT id, name, category, brand, size, color, price, stock
    FROM products
    ORDER BY name
");
?>
<div class="card">
    <h2>Stock Report</h2>
    <p>Current stock of all products.</p>
</div>

<div class="card">
    <table>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Cat</th>
            <th>Brand</th>
            <th>Size</th>
            <th>Color</th>
            <th>Price (₹)</th>
            <th>Stock</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td><?= htmlspecialchars($row['category']) ?></td>
                <td><?= htmlspecialchars($row['brand']) ?></td>
                <td><?= htmlspecialchars($row['size']) ?></td>
                <td><?= htmlspecialchars($row['color']) ?></td>
                <td><?= number_format($row['price'], 2) ?></td>
                <td><?= $row['stock'] ?></td>
            </tr>
        <?php endwhile; ?>
    </table>
</div>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
