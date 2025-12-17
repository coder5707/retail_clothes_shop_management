<?php
require_once "../config/db.php";

/* ----------------------------
   FETCH ONLY PUBLISHED PRODUCTS
-----------------------------*/
$result = $conn->query("
    SELECT * FROM products 
    WHERE publish_status = 1 AND status = 1
    ORDER BY id DESC
");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Our Products</title>
    <link rel="stylesheet" href="../assets/css/style.css">

    <style>
        body {
            background: #f4f6f9;
            margin: 0;
        }

        .customer-header {
            background: #34495e;
            color: white;
            padding: 12px;
            text-align: center;
            font-size: 20px;
        }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 15px;
            padding: 20px;
        }

        .product-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 0 10px #ccc;
            padding: 10px;
            text-align: center;
        }

        .product-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
        }

        .product-card h4 {
            margin: 6px 0;
        }

        .price {
            font-size: 18px;
            font-weight: bold;
            color: green;
        }

        .category {
            color: gray;
            font-size: 14px;
        }

        .footer {
            background: #34495e;
            color: white;
            text-align: center;
            padding: 10px;
            margin-top: 10px;
        }
    </style>
</head>

<body>

<!-- =============================
     CUSTOMER PAGE HEADER
============================= -->
<div class="customer-header">
    🛍️ Welcome to Our Store - Published Products
</div>

<!-- =============================
     PRODUCT DISPLAY SECTION
============================= -->
<div class="product-grid">

    <?php if ($result->num_rows > 0): ?>
        <?php while ($p = $result->fetch_assoc()): ?>
            <div class="product-card">
                <img src="../uploads/products/<?= $p['publish_image'] ?>" alt="Product">

                <h4><?= htmlspecialchars($p['name']) ?></h4>

                <div class="category">
                    <?= htmlspecialchars($p['category']) ?>
                </div>

                <div class="price">
                    ₹ <?= number_format($p['price'], 2) ?>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <h3 style="grid-column:1/-1;text-align:center;color:red;">
            No products published yet
        </h3>
    <?php endif; ?>

</div>

<!-- =============================
     FOOTER
============================= -->
<div class="footer">
    &copy; <?= date('Y') ?> Retail Clothes Shop | Customer Page
</div>

</body>
</html>
