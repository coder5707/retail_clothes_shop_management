<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../partials/header.php';

$message = "";
$error = "";

/* -----------------------------
   ADD / UPDATE PRODUCT
------------------------------*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id       = $_POST['id'] ?? '';
    $name     = trim($_POST['name'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $brand    = trim($_POST['brand'] ?? '');
    $size     = trim($_POST['size'] ?? '');
    $color    = trim($_POST['color'] ?? '');
    $price    = floatval($_POST['price'] ?? 0);
    $stock    = intval($_POST['stock'] ?? 0);

    if ($name === '') {
        $error = "Product name is required.";
    } elseif ($price < 0) {
        $error = "Price cannot be negative.";
    } else {

        if ($id) {
            // ✅ UPDATE
            $stmt = $conn->prepare("
                UPDATE products 
                SET name=?, category=?, brand=?, size=?, color=?, price=?, stock=? 
                WHERE id=?
            ");

            $stmt->bind_param(
                "sssssdii",
                $name, $category, $brand, $size, $color, $price, $stock, $id
            );

        } else {
            // ✅ INSERT
            $stmt = $conn->prepare("
                INSERT INTO products 
                (name, category, brand, size, color, price, stock) 
                VALUES (?,?,?,?,?,?,?)
            ");

            $stmt->bind_param(
                "sssssdi",
                $name, $category, $brand, $size, $color, $price, $stock
            );
        }

        if ($stmt->execute()) {
            $message = $id ? "Product updated successfully." : "Product added successfully.";
        } else {
            $error = "Database error: " . $stmt->error;
        }

        $stmt->close(); // ✅ VERY IMPORTANT
    }
}

/* -----------------------------
   SOFT DELETE PRODUCT
------------------------------*/
if (isset($_GET['delete'])) {
    $del_id = intval($_GET['delete']);

    $stmt = $conn->prepare("UPDATE products SET status=0 WHERE id=?");
    $stmt->bind_param("i", $del_id);

    if ($stmt->execute()) {
        $message = "Product deleted successfully.";
    } else {
        $error = "Failed to delete product.";
    }

    $stmt->close(); // ✅ VERY IMPORTANT
}

/* -----------------------------
   FETCH ACTIVE PRODUCTS
------------------------------*/
$result = $conn->query("
    SELECT * FROM products 
    WHERE status = 1 
    ORDER BY created_at DESC
");

/* -----------------------------
   FETCH PRODUCT FOR EDIT
------------------------------*/
$editProduct = null;
if (isset($_GET['edit'])) {
    $edit_id = intval($_GET['edit']);

    $stmt = $conn->prepare("
        SELECT * FROM products 
        WHERE id=? AND status = 1
    ");
    $stmt->bind_param("i", $edit_id);
    $stmt->execute();
    $editProduct = $stmt->get_result()->fetch_assoc();
    $stmt->close(); // ✅ VERY IMPORTANT
}
?>

<div class="card">
    <h2>Products</h2>
    <?php if ($error): ?>
        <div class="alert error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($message): ?>
        <div class="alert success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
</div>

<div class="card">
    <h3><?= $editProduct ? "Edit Product" : "Add New Product" ?></h3>

    <form method="post">
        <input type="hidden" name="id" value="<?= $editProduct['id'] ?? '' ?>">

        <label>Name</label>
        <input type="text" name="name" required value="<?= htmlspecialchars($editProduct['name'] ?? '') ?>">

        <label>Category</label>
        <input type="text" name="category" value="<?= htmlspecialchars($editProduct['category'] ?? '') ?>">

        <label>Brand</label>
        <input type="text" name="brand" value="<?= htmlspecialchars($editProduct['brand'] ?? '') ?>">

        <label>Size</label>
        <input type="text" name="size" value="<?= htmlspecialchars($editProduct['size'] ?? '') ?>">

        <label>Color</label>
        <input type="text" name="color" value="<?= htmlspecialchars($editProduct['color'] ?? '') ?>">

        <label>Price (₹)</label>
        <input type="number" step="0.01" name="price" required value="<?= htmlspecialchars($editProduct['price'] ?? '') ?>">

        <label>Stock</label>
        <input type="number" name="stock" required value="<?= htmlspecialchars($editProduct['stock'] ?? 0) ?>">

        <br><br>
        <button class="btn" type="submit">
            <?= $editProduct ? "Update" : "Add" ?> Product
        </button>

        <?php if ($editProduct): ?>
            <a href="products.php" class="btn secondary">Cancel</a>
        <?php endif; ?>
    </form>
</div>

<div class="card">
    <h3>Product List</h3>
    <table>
        <tr>
            <th>ID</th>
            <th>Name</th>
            <th>Category</th>
            <th>Brand</th>
            <th>Size</th>
            <th>Color</th>
            <th>Price</th>
            <th>Stock</th>
            <th>Action</th>
        </tr>

        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td><?= htmlspecialchars($row['name']) ?></td>
                <td><?= htmlspecialchars($row['category']) ?></td>
                <td><?= htmlspecialchars($row['brand']) ?></td>
                <td><?= htmlspecialchars($row['size']) ?></td>
                <td><?= htmlspecialchars($row['color']) ?></td>
                <td>₹ <?= number_format($row['price'], 2) ?></td>
                <td><?= $row['stock'] ?></td>
                <td>
                    <a class="btn small secondary" href="products.php?edit=<?= $row['id'] ?>">Edit</a>
                    <a class="btn small danger" href="products.php?delete=<?= $row['id'] ?>" 
                       onclick="return confirm('Delete this product?')">Delete</a>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>
</div>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
