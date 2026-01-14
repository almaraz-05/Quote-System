<?php
session_start();
include("db_connect_quote.php");

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_POST['quote_id'])) {
    die("Missing quote ID.");
}

$quote_id = $_POST['quote_id'];

// Fetch quote
$stmt = $pdo->prepare("SELECT * FROM quote WHERE quote_id = ?");
$stmt->execute([$quote_id]);
$quote = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$quote || $quote['status'] !== 'sanctioned') {
    die("Invalid or already processed quote.");
}

// Fetch subtotal
$stmt = $pdo->prepare("SELECT SUM(price) FROM line_item WHERE quote_id = ?");
$stmt->execute([$quote_id]);
$subtotal = $stmt->fetchColumn() ?: 0;

$is_percent = $quote['is_percent'] ?? 0;
$raw_discount = $quote['discount'] ?? 0;

if ($is_percent) {
    $initial_discount = ($raw_discount / 100) * $subtotal;
} else {
    $initial_discount = $raw_discount;
}

$base_total = max(0, $subtotal - $initial_discount);

// Show Final Discount Form
if (!isset($_POST['final_discount'])) {
?>
<!DOCTYPE html>
<html>
<head>
    <title>Start Purchase Order</title>
    <style>
        body { font-family: Arial; background: #f8f8f8; }
        .container {
            width: 60%; margin: 60px auto; padding: 30px;
            background: #fff; border-radius: 8px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        input, button {
            padding: 10px; margin: 10px 0;
            border-radius: 6px;
        }
        button {
            background: #007bff; color: white;
            border: none; cursor: pointer;
        }
    </style>
</head>
<body>
<script>
function cancelEdit() {
    window.location.href = "quotes_hq.php";
}

document.getElementById('finalTotalDisplay').textContent =
    parseFloat(document.getElementById('baseTotal').value).toFixed(2);

function applyDiscount() {
    const baseTotal = parseFloat(document.getElementById('baseTotal').value);
    const discountVal = parseFloat(document.getElementById('final_discount').value);
    const discountType = document.querySelector('input[name="discount_type"]:checked').value;

    let finalDiscount = 0;
    if (discountType === 'percent') {
        finalDiscount = (discountVal / 100) * baseTotal;
    } else {
        finalDiscount = discountVal;
    }

    const finalTotal = Math.max(0, baseTotal - finalDiscount);
    document.getElementById('finalTotalDisplay').textContent = finalTotal.toFixed(2);
}
</script>

<div class="container">
    <h2>Start Purchase Order for Quote #<?= $quote_id ?></h2>
    <p><strong>Total Before Discount:</strong> $<?= number_format($subtotal, 2) ?></p>
    <p><strong>Initial Discount:</strong>
        <?php if ($is_percent): ?>
            <?= htmlspecialchars($raw_discount) ?>% (Saved $<?= number_format($initial_discount, 2) ?>)
        <?php else: ?>
            $<?= number_format($initial_discount, 2) ?>
        <?php endif; ?>
    </p>
    <p><strong>Base Total:</strong> $<?= number_format($base_total, 2) ?></p>

    <form method="post">
        <input type="hidden" id="baseTotal" value="<?= $base_total ?>">
        <input type="hidden" name="quote_id" value="<?= $quote_id ?>">
        <label><strong>Final Discount:</strong></label><br>
        <input type="number" id="final_discount" name="final_discount" step="0.01" value="0.00" required><br>

        <input type="radio" name="discount_type" value="flat" style="accent-color: #007bff" checked>Amount 
        <input type="radio" name="discount_type" value="percent" style="accent-color: #007bff"> Percent 
        
        <button type="button" onclick="applyDiscount()">Apply Discount</button><br><br>

        <strong>Total After Final Discount: $<span id="finalTotalDisplay">--</span></strong><br><br>

        <br>
        <button type="submit" style="background-color: #28a745;">Submit Purchase Order</button>
        <button type="button" onclick="cancelEdit()" style="background-color: red;">Cancel</button>
    </form>
</div>
</body>
</html>
<?php
    exit;
}

// Final Discount + POST to API
$raw_discount = floatval($_POST['final_discount']);
$discount_type = $_POST['discount_type'] ?? 'flat';

if ($discount_type === 'percent') {
    $final_discount = ($raw_discount / 100.0) * $base_total;
} else {
    $final_discount = $raw_discount;
}

$final_total = max(0, $base_total - $final_discount);


// Prepare payload
$order_num = uniqid("PO_");
$payload = array(
    'order' => $order_num,
    'associate' => $quote['associate_id'],
    'custid' => $quote['customer_id'],
    'amount' => $final_total
);

$options = array(
    'http' => array(
        'header'  => array('Content-type: application/json', 'Accept: application/json'),
        'method'  => 'POST',
        'content' => json_encode($payload)
    )
);

$context = stream_context_create($options);
$response = file_get_contents("http://blitz.cs.niu.edu/PurchaseOrder/", false, $context);
$result = json_decode($response, true);

// Error Handling
if (isset($result['errors'])) {
    echo "<h2>API Error:</h2><ul>";
    foreach ($result['errors'] as $error) {
        echo "<li>" . htmlspecialchars($error) . "</li>";
    }
    echo "</ul><a href='quotes_hq.php'>Back to Quotes</a>";
    exit;
}

// Extract API response data
$processing_date = $result['processDay'] ?? null;
$commission_percent = floatval($result['commission'] ?? 0);
$commission_amount = ($commission_percent / 100.0) * $final_total;

// Save updated quote
$stmt = $pdo->prepare("UPDATE quote SET 
    status = 'ordered',
    quote_price = ?, 
    final_discount = ?, 
    processing_date = ? 
    WHERE quote_id = ?");
$stmt->execute([$final_total, $final_discount, $processing_date, $quote_id]);

// Update associate's commission
$stmt = $pdo->prepare("UPDATE sales_associate 
    SET accumulated_commission = accumulated_commission + ? 
    WHERE associate_id = ?");
$stmt->execute([$commission_amount, $quote['associate_id']]);
?>


<!DOCTYPE html>
<html>
<head>
    <title>Purchase Order Complete</title>
    <style>
        body { font-family: Arial; background: #f8f8f8; }
        .container {
            width: 60%; margin: 60px auto; padding: 30px;
            background: #fff; border-radius: 8px;
            box-shadow: 0 0 15px rgba(0,0,0,0.1);
        }
        .summary { font-size: 1.1em; }
        a {
            display: block; text-align: center;
            margin-top: 30px; text-decoration: none;
            color: #007bff; font-weight: bold;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>Purchase Order Complete</h2>
    <div class="summary">
        <p><strong>Quote ID:</strong> <?= $quote_id ?></p>
        <p><strong>Final Total:</strong> $<?= number_format($final_total, 2) ?></p>
        <p><strong>Processing Date:</strong> <?= $processing_date ? htmlspecialchars(date("m/d/Y", strtotime($processing_date))) : "Unavailable" ?></p>
        <p><strong>Commission Earned:</strong> $<?= number_format($commission_amount, 2) ?> (<?= $commission_percent ?>%)</p>
    </div>
    <a href="quotes_hq.php">← Back to Quotes</a>
</div>
</body>
</html>

