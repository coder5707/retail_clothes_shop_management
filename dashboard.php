<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../partials/header.php';

$message = "";

/* -----------------------------
   CLEAR ALL DATA ACTION
------------------------------*/
if (isset($_POST['clear_all'])) {

    $conn->query("SET FOREIGN_KEY_CHECKS = 0");
    $conn->query("TRUNCATE TABLE sale_items");
    $conn->query("TRUNCATE TABLE sales");
    $conn->query("TRUNCATE TABLE customers");
    $conn->query("TRUNCATE TABLE products");
    $conn->query("SET FOREIGN_KEY_CHECKS = 1");

    $message = "All data cleared successfully!";
}

/* -----------------------------
   DASHBOARD METRICS
------------------------------*/
$totalProducts = $conn->query("SELECT COUNT(*) AS c FROM products WHERE status=1")->fetch_assoc()['c'];

$rowStock = $conn->query("SELECT SUM(stock) AS s FROM products WHERE status=1")->fetch_assoc();
$totalStock = $rowStock['s'] ?? 0;

$today = date('Y-m-d');
$stmt = $conn->prepare("SELECT SUM(total_amount) AS t FROM sales WHERE DATE(sale_date)=?");
$stmt->bind_param("s", $today);
$stmt->execute();
$rowToday = $stmt->get_result()->fetch_assoc();
$todaySales = $rowToday['t'] ?? 0;
$stmt->close();

$totalSales = $conn->query("SELECT COUNT(*) AS c FROM sales")->fetch_assoc()['c'];

/* -----------------------------
   NEEDLE GAUGE LOGIC
------------------------------*/
$maxCapacity = 5000;
$stockPercent = ($totalStock > 0) ? min(100, round(($totalStock / $maxCapacity) * 100)) : 0;
?>

<div class="card">
    <h2>Dashboard</h2>
    <?php if ($message): ?>
        <div class="alert success"><?= $message ?></div>
    <?php endif; ?>
</div>

<div class="card">
    <table>
        <tr>
            <th>Total Products</th>
            <th>Total Stock</th>
            <th>Today's Sales</th>
            <th>Total Bills</th>
        </tr>
        <tr>
            <td><?= $totalProducts ?></td>
            <td><?= $totalStock ?></td>
            <td>₹ <?= number_format($todaySales, 2) ?></td>
            <td><?= $totalSales ?></td>
        </tr>
    </table>
</div>

<!-- =============================
     SPEEDOMETER GAUGE WITH NEEDLE
============================= -->
<div class="card" style="text-align:center;">
    <h3>Inventory Level</h3>

    <canvas id="inventoryGauge" height="220"></canvas>

    <h2><?= $stockPercent ?>%</h2>

    <?php if ($stockPercent < 30): ?>
        <p style="color:red;font-weight:bold;">⚠ Low Inventory Alert!</p>
    <?php endif; ?>
</div>

<div class="card">
    <h3 style="color:red;">Danger Zone</h3>
    <form method="post" onsubmit="return confirm('⚠ Permanently delete all data?');">
        <button type="submit" name="clear_all" class="btn danger">🔥 Clear All Data</button>
    </form>
</div>

<!-- =============================
     CHART.JS
============================= -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
const gaugeValue = <?= $stockPercent ?>;

// ✅ Custom Plugin to Draw Needle
const gaugeNeedle = {
    id: 'gaugeNeedle',
    afterDatasetDraw(chart) {
        const { ctx, chartArea } = chart;
        const meta = chart.getDatasetMeta(0).data[0];
        const centerX = meta.x;
        const centerY = meta.y;

        ctx.save();
        const angle = Math.PI + (Math.PI * gaugeValue / 100);
        ctx.translate(centerX, centerY);
        ctx.rotate(angle);
        ctx.beginPath();
        ctx.moveTo(0, -6);
        ctx.lineTo(chartArea.width / 2.4, 0);
        ctx.lineTo(0, 6);
        ctx.fillStyle = 'red';
        ctx.fill();
        ctx.restore();

        ctx.beginPath();
        ctx.arc(centerX, centerY, 8, 0, Math.PI * 2);
        ctx.fillStyle = 'black';
        ctx.fill();
    }
};

// ✅ Half Round Doughnut Chart
new Chart(document.getElementById('inventoryGauge'), {
    type: 'doughnut',
    data: {
        labels: ['Used', 'Remaining'],
        datasets: [{
            data: [gaugeValue, 100 - gaugeValue],
            borderWidth: 0
        }]
    },
    options: {
        rotation: -90,
        circumference: 180,
        cutout: '70%',
        responsive: true,
        plugins: {
            legend: { display: false },
            tooltip: { enabled: false }
        }
    },
    plugins: [gaugeNeedle]
});
</script>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
