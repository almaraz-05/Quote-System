<?php
session_start();
include("db_connect_quote.php");

error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['quote_id'])) {
    die("Invalid request.");
}

$quote_id = $_POST['quote_id'];

// Fetch quote
$stmt = $pdo->prepare("SELECT * FROM quote WHERE quote_id = ?");
$stmt->execute([$quote_id]);
$quote = $stmt->fetch();

if (!$quote || $quote['status'] !== 'finalized') {
    die("Quote not found or already sanctioned.");
}

// Fetch line items
$stmt = $pdo->prepare("SELECT description, price FROM line_item WHERE quote_id = ?");
$stmt->execute([$quote_id]);
$line_items = $stmt->fetchAll();

// Calculate total
$subtotal = array_sum(array_column($line_items, 'price'));
$is_percent = $quote['is_percent'] ?? 0;
$raw_discount = $quote['discount'] ?? 0;

if ($is_percent) {
    $discount = ($raw_discount / 100) * $subtotal;
} else {
    $discount = $raw_discount;
}

$total = $subtotal - $discount;
if ($total < 0) $total = 0;

// Update quote to sanctioned
$stmt = $pdo->prepare("UPDATE quote SET status = 'sanctioned', quote_price = ? WHERE quote_id = ?");
$stmt->execute([$total, $quote_id]);

// Prepare and "send" email
$to = $quote['customer_email'];
$subject = "Your Quote from Our Company";
$headers = "From: no-reply@ourcompany.com\r\n";
$headers .= "Content-Type: text/plain; charset=UTF-8\r\n";

$message = "Dear Customer,\n\nHere is your quote:\n\n";
foreach ($line_items as $item) {
    $message .= "- " . $item['description'] . ": $" . number_format($item['price'], 2) . "\n";
}
if ($discount > 0) {
    if ($is_percent) {
        $message .= "\nDiscount: -{$raw_discount}% (Saved $" . number_format($discount, 2) . ")";
    } else {
        $message .= "\nDiscount: -$" . number_format($discount, 2);
    }
}
$message .= "\n\nTotal: $" . number_format($total, 2);
$message .= "\n\nThank you,\nOur Company";

// Use to send email from php script
mail($to, $subject, $message, $headers);

?>

<!DOCTYPE html>
<html>
<head>
    <title>Quote Sanctioned</title>
    <style>
        html, body { height: 100%; }
        body {
            font-family: Arial, sans-serif;
            background: #f8f8f8;
            margin: 0;
            padding: 0;
            color: #333;
        }
        .container {
            width: 60%;
            margin: 60px auto;
            padding: 30px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        }
        h2 {
            color: #007bff;
            text-align: center;
        }
        .summary {
            font-size: 1.1em;
            margin-top: 20px;
            line-height: 1.6em;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 30px;
            text-decoration: none;
            color: #007bff;
            font-weight: bold;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>Quote #<?= $quote_id ?> Sanctioned</h2>
    <div class="summary">
        <p><strong>Email sent to:</strong> <?= htmlspecialchars($to) ?></p>
        <p><strong>Subtotal:</strong> $<?= number_format($subtotal, 2) ?></p>
        <p><strong>Discount:</strong>
            <?php if ($is_percent): ?>
                <?= htmlspecialchars($raw_discount) ?>% (Saved $<?= number_format($discount, 2) ?>)
            <?php else: ?>
                $<?= number_format($discount, 2) ?>
            <?php endif; ?>
        </p>
        <p><strong>Total Quoted Price:</strong> $<?= number_format($total, 2) ?></p>
    </div>
    <a class="back-link" href="quotes_hq.php">← Return to Quotes</a>
</div>
</body>
</html>

