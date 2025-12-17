<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../partials/header.php';

// Only admin allowed
if ($_SESSION['role'] !== 'admin') {
    die("<h3>Access Denied</h3>");
}

$message = "";

/* ------------------------
   PUBLISH PRODUCT
-------------------------*/
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $product_id = intval($_POST['product_id']);

    $imageName = "";

    if (!empty($_FILES['publish_image']['name'])) {
        $imageName = time() . "_" . $_FILES['publish_image']['name'];
        move_uploaded_file(
            $_FILES['publish_image']['tmp_name'],
            "../../uploads/products/" . $imageName
        );
    }

    $stmt = $conn->prepare("
        UPDATE products 
        SET publish_status = 1, 
            publish_image = ?
        WHERE id = ?
    ");
    $stmt->bind_param("si", $imageName, $product_id);
    $stmt->execute();
    $stmt->close();

    $message = "Product published successfully!";
}

/* ------------------------
   FETCH UNPUBLISHED PRODUCTS
-------------------------*/
$result = $conn->query("
    SELECT * FROM products 
    WHERE status = 1 AND publish_status = 0
    ORDER BY id DESC
");
?>

<div class="card">
    <h2>Publish Products (Admin)</h2>

    <?php if ($message): ?>
        <div class="alert success"><?= $message ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <label>Select Product</label>
        <select name="product_id" required>
            <option value="">-- Select Product --</option>
            <?php while ($row = $result->fetch_assoc()): ?>
                <option value="<?= $row['id'] ?>">
                    <?= htmlspecialchars($row['name']) ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label>Upload Publish Photo</label>
        <input type="file" name="publish_image" required>

        <br><br>
        <button class="btn">Publish Product</button>
    </form>
</div>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
