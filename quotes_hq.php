<?php

session_start();
include("db_connect_quote.php");

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Fetch finalized quotes
$stmt = $pdo->prepare("
    SELECT q.quote_id, q.customer_email, q.status, q.date_created, q.quote_price, s.name AS associate_name, q.customer_id, q.associate_id
    FROM quote q
    JOIN sales_associate s ON q.associate_id = s.associate_id
    WHERE q.status = 'finalized'
    ORDER BY q.date_created DESC
");
$stmt->execute();
$finalized_quotes = $stmt->fetchAll();

// Fetch sanctioned quotes
$stmt = $pdo->prepare("
    SELECT q.quote_id, q.customer_email, q.status, q.date_created, q.quote_price, s.name AS associate_name
    FROM quote q
    JOIN sales_associate s ON q.associate_id = s.associate_id
    WHERE q.status = 'sanctioned'
    ORDER BY q.date_created DESC
");
$stmt->execute();
$sanctioned_quotes = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Headquarters Interface</title>
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
            width: 80%;
            margin: 40px auto;
            padding: 20px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        }
        table {
            width: 100%;
            max-width: 100%;
            border-collapse: collapse;
            overflow: hidden;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        th, td {
            padding: 15px;
            background-color: rgba(255,255,255,0.2);
            color: #000;
        }
        th { text-align: left; }
        tbody tr:hover {
            background-color: rgba(119, 119, 119, 0.2);
            cursor: pointer;
        }
        button {
            width: 100px;
            padding: 10px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }
        .back-link {
            display: block;
            text-align: center;
            margin-top: 30px;
            text-decoration: none;
            color: #007bff;
            font-weight: bold;
        }
        h1 {
            text-align: center;
            margin-bottom: 24px;
            color: #333;
        }
        h2 {
            margin-top: 40px;
            color: #007bff;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Headquarters Interface</h1>

    <!-- FINALIZED QUOTES -->
    <h2>Finalized Quotes</h2>
    <?php if (count($finalized_quotes) > 0): ?>
    <table>
        <thead>
            <tr>
                <th>Quote ID</th>
                <th>Customer Email</th>
                <th>Sales Associate</th>
                <th>Date Created</th>
                <th>Total Price</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($finalized_quotes as $quote): ?>
            <tr>
                <td><?= $quote['quote_id'] ?></td>
                <td><?= htmlspecialchars($quote['customer_email']) ?></td>
                <td><?= htmlspecialchars($quote['associate_name']) ?></td>
                <td><?= $quote['date_created'] ?></td>
                <td>$<?= number_format($quote['quote_price'], 2) ?></td>
                <td>
                    <form action="edit_quote.php" method="POST" style="display:inline;">
                        <input type="hidden" name="quote_id" value="<?= htmlspecialchars($quote['quote_id']) ?>">
                        <input type="hidden" name="customer_id" value="<?= htmlspecialchars($quote['customer_id']) ?>">
                        <input type="hidden" name="associate_id" value="<?= htmlspecialchars($quote['associate_id']) ?>">
                        <button type="submit" style="background-color: #28a745;">Edit</button>
                    </form>
                    <form action="sanction_quote.php" method="post" style="display:inline;" onsubmit="return confirm('Sanction this quote and notify customer?');">
                        <input type="hidden" name="quote_id" value="<?= $quote['quote_id'] ?>">
                        <button type="submit">Sanction</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
        <p>No finalized quotes available.</p>
    <?php endif; ?>

    <!-- SANCTIONED QUOTES -->
    <h2>Sanctioned Quotes</h2>
    <?php if (count($sanctioned_quotes) > 0): ?>
    <table>
        <thead>
            <tr>
                <th>Quote ID</th>
                <th>Customer Email</th>
                <th>Sales Associate</th>
                <th>Date Sanctioned</th>
                <th>Total Price</th>
                <th>Purchase Order</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($sanctioned_quotes as $quote): ?>
            <tr>
                <td><?= $quote['quote_id'] ?></td>
                <td><?= htmlspecialchars($quote['customer_email']) ?></td>
                <td><?= htmlspecialchars($quote['associate_name']) ?></td>
                <td><?= $quote['date_created'] ?></td>
                <td>$<?= number_format($quote['quote_price'], 2) ?></td>
                <td>
                    <form action="start_purchase_order.php" method="post">
                        <input type="hidden" name="quote_id" value="<?= $quote['quote_id'] ?>">
                        <button type="submit">Start</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
        <p>No sanctioned quotes available.</p>
    <?php endif; ?>

    <a class="back-link" href="loginp.php">← Return to Login</a>
</div>
    
</body>
</html>

