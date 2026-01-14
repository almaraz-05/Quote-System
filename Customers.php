<?php 
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$dsn = "mysql:host=blitz.cs.niu.edu;port=3306;dbname=csci467";
$dbusername = "student";
$dbpassword = "student";

try {
    $pdo = new PDO($dsn, $dbusername, $dbpassword);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Pagination setup
$limit = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Get total count for pagination
$countStmt = $pdo->query("SELECT COUNT(*) FROM customers");
$totalCustomers = $countStmt->fetchColumn();
$totalPages = ceil($totalCustomers / $limit);

// Fetch customers for current page
$stmt = $pdo->prepare("SELECT name, city, street, contact, id FROM customers LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$customers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Customer List</title>
    <style>
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

        h2 {
            text-align: center;
            color: #007bff;
        }
        .Logoff {
            display: inline-block;
            text-align: center;
            margin-top: 20px;
            text-decoration: none;
            color: #007bff;
            font-weight: bold;
        }

        .custinfo {
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 10px;
            background-color: #f9f9f9;
        }

        .custinfo p {
            margin: 5px 0;
        }

        .qoute {
            display: block;
            margin-top: 10px;
            text-align: right;
            color: #28a745;
            text-decoration: none;
        }

        .qoute:hover {
            text-decoration: underline;
        }

        .pagination {
            text-align: center;
            margin-top: 20px;
        }

        .pagination a {
            margin: 0 5px;
            padding: 8px 12px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
        }

        .pagination a.disabled {
            background: #ccc;
            pointer-events: none;
        }

        .search-box {
            text-align: center;
            margin-bottom: 20px;
        }

        #searchInput {
            padding: 10px;
            width: 50%;
            font-size: 16px;
            border-radius: 6px;
            border: 1px solid #ccc;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>Customer Directory</h2>
    <a class="Logoff" href="loginp.php"> LOGOFF</a>
    <a href="Quotes.php" class="qoute" style="display: block; text-align: center; margin-top: 30px;">Go to Quotes</a>

    <div class="search-box">
        <input type="text" id="searchInput" onkeyup="filterCustomers()" placeholder="Search customers...">
    </div>

    <?php foreach ($customers as $customer): ?>
        <div class="custinfo">
            <p><strong>Customer ID:</strong> <?= htmlspecialchars($customer['id']) ?></p>
            <p><strong>Name:</strong> <?= htmlspecialchars($customer['name']) ?></p>
            <p><strong>City:</strong> <?= htmlspecialchars($customer['city']) ?></p>
            <p><strong>Street:</strong> <?= htmlspecialchars($customer['street']) ?></p>
            <p><strong>Contact:</strong> <?= htmlspecialchars($customer['contact']) ?></p>
            <form action="create_quote.php" method="POST" style="display: flex; justify-content: flex-end; margin-top: 10px;">
                <input type="hidden" name="customer_id" value="<?= htmlspecialchars($customer['id']) ?>">
                <input type="hidden" name="associate_id" value="<?= htmlspecialchars($_SESSION['associate_id']) ?>">
                <button type="submit" class="qoute" style="border: none; background: none; color: #28a745; cursor: pointer;">Create Quote</button>
            </form>
        </div>
    <?php endforeach; ?>

    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>">← Previous</a>
        <?php else: ?>
            <a class="disabled">← Previous</a>
        <?php endif; ?>

        <?php if ($page < $totalPages): ?>
            <a href="?page=<?= $page + 1 ?>">Next →</a>
        <?php else: ?>
            <a class="disabled">Next →</a>
        <?php endif; ?>
    </div>

</div>

<script>
function filterCustomers() {
    const input = document.getElementById("searchInput").value.toLowerCase();
    const cards = document.querySelectorAll(".custinfo");

    cards.forEach(card => {
        const text = card.textContent.toLowerCase();
        card.style.display = text.includes(input) ? "block" : "none";
    });
}
</script>
</body>
</html>
