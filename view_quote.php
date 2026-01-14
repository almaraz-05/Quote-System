<?php
session_start();
include("secrets.php");

$quote_id = $_GET['id'] ?? null;

if (!$quote_id) {
    die("Missing quote ID");
}

$legacy_dsn = 'mysql:host=blitz.cs.niu.edu;dbname=csci467';
$legacy_user = 'student';

try {
    $dsn = "mysql:host=courses;dbname=$username";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $legacy_pdo = new PDO($legacy_dsn, $legacy_user, $legacy_user);
    $legacy_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Get customer_id and associate_id from quote
    $stmt = $pdo->prepare("SELECT customer_id, associate_id FROM quote WHERE quote_id = ?");
    $stmt->execute([$quote_id]);
    $ids = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$ids) {
        die("Quote not found.");
    }

    $customer_id = $ids['customer_id'];
    $associate_id = $ids['associate_id'];

    // Get customer details
    $stmt = $legacy_pdo->prepare("SELECT * FROM customers WHERE id = ?");
    $stmt->execute([$customer_id]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($customer) {
        $customer_name = $customer['name'];
        $customer_street = $customer['street'];
        $customer_city = $customer['city'];
        $customer_contact = $customer['contact'];
    } else {
        $customer_name = $customer_street = $customer_city = $customer_contact = 'Unknown';
    }

    // Get quote details
    $stmt_quote = $pdo->prepare("SELECT * FROM quote WHERE quote_id = ?");
    $stmt_quote->execute([$quote_id]);
    $quote = $stmt_quote->fetch(PDO::FETCH_ASSOC);

    if (!$quote) {
        die("Quote not found.");
    }

    $email = $quote['customer_email'] ?? '';

    // Get line items
    $stmt_items = $pdo->prepare("SELECT * FROM line_item WHERE quote_id = ?");
    $stmt_items->execute([$quote_id]);
    $line_items = $stmt_items->fetchAll(PDO::FETCH_ASSOC);

    // Get secret notes
    $stmt_notes = $pdo->prepare("SELECT * FROM secret_note WHERE quote_id = ?");
    $stmt_notes->execute([$quote_id]);
    $secret_notes = $stmt_notes->fetchAll(PDO::FETCH_ASSOC);

    $discount = $quote['discount'] ?? 0.00;
    $isPercent = $quote['is_percent'] ?? true;

    // Calculate total and total after discount
    $total = 0;
    foreach ($line_items as $item) {
        $total += floatval($item['price']);
    }

    if ($isPercent) {
        $discountAmount = $total * ($discount / 100);
    } else {
        $discountAmount = $discount;
    }
    if ($discountAmount > $total) $discountAmount = $total;

    $totalAfterDiscount = $total - $discountAmount;

} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>View Quote (Read-Only)</title>
    <style>
        html,
        body {
            height: 100%;
        }

        body {
            font-family: Arial, sans-serif;
            background: #f8f8f8;
            margin: 0;
            padding: 0;
            color: #333;
        }

        .container {
            width: 80%;
            margin: 40px auto;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        }  
        table {
            border-collapse: collapse;
            width: 70%;
            margin-bottom: 30px;
        }
        table, th, td {
            border: 1px solid #999;
        }
        th, td {
            padding: 8px 12px;
            text-align: left;
        }
        h2, h3 {
            margin-top: 0;
        }
        label {
            font-weight: bold;
        }
        .info-block {
            margin-bottom: 20px;
        }
        a.button-link {
            display: inline-block;
            padding: 8px 16px;
            background-color: #28a745;
            color: white;
            text-decoration: none;
            border-radius: 6px;
        }
        a.button-link:hover {
            background-color: #218838;
        }
    </style>
</head>
<body>

<div class="container">
<h2>Quote for: <?= htmlspecialchars($customer_name) ?></h2>
<div class="info-block">
    <div><?= htmlspecialchars($customer_street) ?></div>
    <div><?= htmlspecialchars($customer_city) ?></div>
    <div><?= htmlspecialchars($customer_contact) ?></div>
</div>

<div class="info-block">
    <label>Email:</label>
    <div><?= htmlspecialchars($email) ?></div>
</div>

<h3>Line Items:</h3>
<table>
    <thead>
        <tr>
            <th>Description</th>
            <th>Price ($)</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($line_items as $item): ?>
            <tr>
                <td><?= htmlspecialchars($item['description']) ?></td>
                <td><?= number_format($item['price'], 2) ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<h3>Secret Notes:</h3>
<table>
    <tbody>
        <?php foreach ($secret_notes as $note): ?>
            <tr>
                <td><?= htmlspecialchars($note['description']) ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<div class="info-block">
    <label>Discount:</label>
    <div><?= number_format($discount, 2) ?></div><br>

    <label>Discount Type:</label>
    <div><?= $isPercent ? 'Percent' : 'Fixed Amount' ?></div><br>

    <label>Total Before Discount:</label>
    <div>$<?= number_format($total, 2) ?></div><br>

    <label>Discount Amount:</label>
    <div>$<?= number_format($discountAmount, 2) ?></div><br>

    <label>Total After Discount:</label>
    <div><strong>$<?= number_format($totalAfterDiscount, 2) ?></strong></div>
</div>

<a href="admin_interface.php" class="button-link">← Return to Admin Interface</a>
</div>
</body>
</html>
