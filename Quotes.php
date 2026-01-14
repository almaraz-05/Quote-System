<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include("secrets.php");

if (!isset($_SESSION['associate_id'])) {
    die("You must be logged in.");
}

$associate_id = $_SESSION['associate_id'];

// Zoe-New legacy stuff
$legacy_dsn = "mysql:host=blitz.cs.niu.edu;port=3306;dbname=csci467";
$legacy_user = "student";
$legacy_pass = "student";

try {
    $dsn = "mysql:host=courses;dbname=$username";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Zoe-new legacy stuff
    $legacy_pdo = new PDO($legacy_dsn, $legacy_user, $legacy_pass);
    $legacy_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['quote_id'], $_POST['new_status'])) {
        $quote_id = $_POST['quote_id'];
        $new_status = $_POST['new_status'];

        $allowed_statuses = ['open', 'finalized', 'sanctioned', 'ordered'];
        if (in_array($new_status, $allowed_statuses)) {
            $update = $pdo->prepare("UPDATE quote SET Status = ? WHERE quote_id = ? AND associate_id = ?");
            $update->execute([$new_status, $quote_id, $associate_id]);
        }
    }

    // Zoe-Changed this to include customer_id
    $stmt = $pdo->prepare("SELECT quote_id, Status, quote_price, customer_id FROM quote WHERE associate_id = ?AND Status = 'open'");
    $stmt->execute([$associate_id]);
    $quotes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
     // Look up customer names from the legacy DB
    foreach ($quotes as &$quote) {
        $custStmt = $legacy_pdo->prepare("SELECT name FROM customers WHERE id = ?");
        $custStmt->execute([$quote['customer_id']]);
        $quote['customer_name'] = $custStmt->fetchColumn() ?? 'Unknown';
    }

    // Fetch associate name
    $assocQuery = $pdo->prepare("SELECT name FROM sales_associate WHERE associate_id = ?");
    $assocQuery->execute([$associate_id]);
    $associate_name = $assocQuery->fetchColumn() ?? 'Unknown';

   
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Your Quotes</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f6f9;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 85%;
            margin: 40px auto;
            background-color: #fff;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        h2 {
            text-align: center;
            color: #333;
        }
        .Logoff {
            display: block;
            text-align: right;
            margin-top: 30px;
            text-decoration: none;
            color: #007bff;
            font-weight: bold;
        }

        .qoute {
            display: block;
            margin-top: 10px;
            text-align: right;
            color: #28a745;
            text-decoration: none;
        }
        
        .quote-item {
            border-bottom: 1px solid #ddd;
            padding: 20px 0;
        }

        .quote-item:last-child {
            border-bottom: none;
        }

        .quote-item p {
            margin: 5px 0;
        }

        .status-form {
            margin-top: 10px;
        }

        select {
            padding: 5px;
            font-size: 14px;
        }

        button {
            padding: 5px 10px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            margin-left: 10px;
        }

        .total {
            color: #28a745;
            font-weight: bold;
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
    <h2>Your Quotes</h2>
    <p style="text-align:center; font-size: 18px;"><strong>Logged in as:</strong> <?= htmlspecialchars($associate_name) ?></p>
    <a class="Logoff" href="loginp.php"> LOGOFF</a>
    <?php
    $hasVisible = false;
    foreach ($quotes as &$quote):
        if ($quote['Status'] === 'sanctioned') continue;
        $hasVisible = true;
    ?>
      <div class="quote-item">
    <p><strong>Quote ID:</strong> <?= htmlspecialchars($quote['quote_id']) ?></p>
    <p><strong>Customer:</strong> <?= htmlspecialchars($quote['customer_name']) ?> (ID: <?= htmlspecialchars($quote['customer_id']) ?>)</p>
    <p><strong>Total:</strong> <span class="total">$<?= number_format($quote['quote_price'], 2) ?></span></p>

    <form class="status-form" method="post">
        <label for="status-<?= $quote['quote_id'] ?>"><strong>Status:</strong></label>
        <select name="new_status" id="status-<?= $quote['quote_id'] ?>">
            <?php
            $enum_values = ['open', 'finalized'];
            foreach ($enum_values as $status) {
                $selected = ($quote['Status'] === $status) ? 'selected' : '';
                echo "<option value=\"$status\" $selected>$status</option>";
            }
            ?>
        </select>
        <input type="hidden" name="quote_id" value="<?= $quote['quote_id'] ?>">
        <button type="submit">Update</button>
    </form>

    <form action="edit_quote_sa.php" method="POST" style="display: flex; justify-content: flex-end; margin-top: 10px;">
       <input type="hidden" name="customer_id" value="<?= htmlspecialchars($quote['customer_id']) ?>">
        <input type="hidden" name="associate_id" value="<?= htmlspecialchars($_SESSION['associate_id']) ?>">
        <input type="hidden" name="quote_id" value="<?= htmlspecialchars($quote['quote_id']) ?>">
        <button type="submit" class="qoute" style="border: none; background: none; color: #28a745; cursor: pointer;">Edit Quote</button>
    </form>
       </div>
    <?php endforeach; ?>

    <?php if (!$hasVisible): ?>
        <p style="text-align:center; color:gray;">No editable quotes found.</p>
    <?php endif; ?>

    <a class="back-link" href="Customers.php">← Back to Customers</a>
</div>
</body>
</html>