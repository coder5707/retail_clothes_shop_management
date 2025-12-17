<?php
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../partials/header.php';

$message = "";
$error = "";

// Fetch products for dropdown
$productsRes = $conn->query("SELECT id, name, price, stock FROM products ORDER BY name");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customer_name  = trim($_POST['customer_name'] ?? '');
    $customer_phone = trim($_POST['customer_phone'] ?? '');
    $customer_email = trim($_POST['customer_email'] ?? '');

    $product_ids = $_POST['product_id'] ?? [];
    $quantities  = $_POST['quantity'] ?? [];
    $prices      = $_POST['price'] ?? [];

    if (count($product_ids) == 0) {
        $error = "Add at least one product.";
    } else {
        // Insert customer (optional)
        $customer_id = null;
        if ($customer_name || $customer_phone || $customer_email) {
            $stmt = $conn->prepare("INSERT INTO customers (name, phone, email) VALUES (?,?,?)");
            $stmt->bind_param("sss", $customer_name, $customer_phone, $customer_email);
            $stmt->execute();
            $customer_id = $stmt->insert_id;
        }

        // Calculate total
        $total = 0;
        for ($i = 0; $i < count($product_ids); $i++) {
            $qty = (int)($quantities[$i] ?? 0);
            $prc = (float)($prices[$i] ?? 0);
            if ($qty > 0 && $prc >= 0) {
                $total += $qty * $prc;
            }
        }

        if ($total <= 0) {
            $error = "Total cannot be zero.";
        } else {
            // Insert into sales
            $stmt = $conn->prepare("INSERT INTO sales (customer_id, total_amount) VALUES (?, ?)");
            if ($customer_id) {
                $stmt->bind_param("id", $customer_id, $total);
            } else {
                $null = null;
                $stmt->bind_param("id", $null, $total);
            }
            $stmt->execute();
            $sale_id = $stmt->insert_id;

            // Insert items and update stock
            for ($i = 0; $i < count($product_ids); $i++) {
                $pid = (int)($product_ids[$i] ?? 0);
                $qty = (int)($quantities[$i] ?? 0);
                $prc = (float)($prices[$i] ?? 0);

                if ($qty <= 0 || !$pid) continue;

                // Check stock
                $st = $conn->prepare("SELECT stock FROM products WHERE id=?");
                $st->bind_param("i", $pid);
                $st->execute();
                $stockRow = $st->get_result()->fetch_assoc();
                $available = $stockRow ? (int)$stockRow['stock'] : 0;

                if ($qty > $available) {
                    $error = "Insufficient stock for product ID $pid. Sale saved partially.";
                    $qty = $available;
                }

                if ($qty > 0) {
                    $stmtItem = $conn->prepare("INSERT INTO sale_items (sale_id, product_id, quantity, unit_price) VALUES (?,?,?,?)");
                    $stmtItem->bind_param("iiid", $sale_id, $pid, $qty, $prc);
                    $stmtItem->execute();

                    // reduce stock
                    $newStock = $available - $qty;
                    $up = $conn->prepare("UPDATE products SET stock=? WHERE id=?");
                    $up->bind_param("ii", $newStock, $pid);
                    $up->execute();
                }
            }

            if (!$error) {
                $message = "Sale created successfully. <a href='view_sale.php?id=$sale_id'>View Bill</a>";
            }
        }
    }
}
?>

<div class="card">
    <h2>New Sale (Billing)</h2>
    <?php if ($error): ?>
        <div class="alert error"><?= $error ?></div>
    <?php endif; ?>
    <?php if ($message): ?>
        <div class="alert success"><?= $message ?></div>
    <?php endif; ?>

    <form method="post" id="saleForm">
        <h3>Customer Details (optional)</h3>
        <label>Customer Name</label>
        <input type="text" name="customer_name">

        <label>Customer Phone</label>
        <input type="text" name="customer_phone">

        <label>Customer Email</label>
        <input type="email" name="customer_email">

        <h3>Items</h3>
        <table id="itemsTable">
            <thead>
            <tr>
                <th>Product</th>
                <th>Price (₹)</th>
                <th>Qty</th>
                <th>Line Total</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            <tr>
                <td>
                    <select name="product_id[]" onchange="updatePrice(this)">
                        <option value="">--Select--</option>
                        <?php
                        $productsRes->data_seek(0);
                        while ($p = $productsRes->fetch_assoc()): ?>
                            <option data-price="<?= $p['price'] ?>" value="<?= $p['id'] ?>">
                                <?= htmlspecialchars($p['name']) ?> (Stock: <?= $p['stock'] ?>)
                            </option>
                        <?php endwhile; ?>
                    </select>
                </td>
                <td><input type="number" step="0.01" name="price[]" value="0" oninput="calcLineTotal(this)"></td>
                <td><input type="number" name="quantity[]" value="1" min="1" oninput="calcLineTotal(this)"></td>
                <td><span class="line-total">0.00</span></td>
                <td><button type="button" class="btn small danger" onclick="removeRow(this)">X</button></td>
            </tr>
            </tbody>
        </table>

        <button type="button" class="btn secondary" onclick="addRow()">+ Add Item</button>

        <h3>Total: ₹ <span id="grandTotal">0.00</span></h3>

        <button type="submit" class="btn">Save Sale</button>
    </form>
</div>

<script>
function updatePrice(selectElement) {
    const price = selectElement.options[selectElement.selectedIndex].getAttribute('data-price') || 0;
    const row = selectElement.closest('tr');
    row.querySelector('input[name="price[]"]').value = parseFloat(price).toFixed(2);
    calcLineTotal(row.querySelector('input[name="price[]"]'));
}

function calcLineTotal(inputElement) {
    const row = inputElement.closest('tr');
    const price = parseFloat(row.querySelector('input[name="price[]"]').value) || 0;
    const qty = parseInt(row.querySelector('input[name="quantity[]"]').value) || 0;
    const lineTotal = price * qty;
    row.querySelector('.line-total').innerText = lineTotal.toFixed(2);
    calcGrandTotal();
}

function calcGrandTotal() {
    let total = 0;
    document.querySelectorAll('.line-total').forEach(function (elem) {
        total += parseFloat(elem.innerText) || 0;
    });
    document.getElementById('grandTotal').innerText = total.toFixed(2);
}

function addRow() {
    const tbody = document.querySelector('#itemsTable tbody');
    const firstRow = tbody.querySelector('tr');
    const newRow = firstRow.cloneNode(true);

    newRow.querySelector('select').selectedIndex = 0;
    newRow.querySelector('input[name="price[]"]').value = "0";
    newRow.querySelector('input[name="quantity[]"]').value = "1";
    newRow.querySelector('.line-total').innerText = "0.00";

    tbody.appendChild(newRow);
}

function removeRow(btn) {
    const tbody = document.querySelector('#itemsTable tbody');
    if (tbody.rows.length > 1) {
        btn.closest('tr').remove();
        calcGrandTotal();
    }
}
calcGrandTotal();
</script>

<?php require_once __DIR__ . '/../../partials/footer.php'; ?>
