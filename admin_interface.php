<html>
    <head>
        <title>Admin Interface</title>
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
                width: 100%;
                max-width: 100%;
                border-collapse: collapse;
                overflow: hidden;
                box-shadow: 0 0 20px rgba(0,0,0,0.1);
            }

            th,
            td {
                padding: 15px;
                background-color: rgba(255,255,255,0.2);
                color: black;
            }

            th {
                text-align: left;
            }

            thead {
                tr {
                    &:hover {
                        background-color: inherit !important;
                    }
                }
            }

            tbody {
                tr {
                    &:hover {
                        background-color: rgba(119, 119, 119, 0.2);
                        cursor: pointer;
                    }
                }
                td {
                    position: relative;
                }
            }

            button {
                width: 20%;
                padding: 12px;
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
        </style>
    </head>

    <body>
        <div class="container">
        <h1>Administrator Interface</h1>
        <script>
            // Redirection to view/edit associates
            function selectRow(id) {
			    window.location.href = "view_add_associate.php?id=" + encodeURIComponent(id);
			}

            // redirection to view quotes for onclick on quote table entry
            function selectRowQuote(id) {
			    window.location.href = "view_quote.php?id=" + encodeURIComponent(id);
			}
        </script>

        <fieldset>
            <legend style="font-weight: bold; font-size: 1.5em; color: #007bff;">Sales Associates</legend>
            <p>Select an Associate to View & Edit Information</p>

            <?php
            // Replace with own secrets file
            include("secrets.php");

            ini_set('display_errors', 1);
            ini_set('display_startup_errors', 1);
            error_reporting(E_ALL);

            try {
                $dsn = "mysql:host=courses;dbname=$username";
                $pdo = new PDO($dsn, $username, $password);

                // Legacy customer database connection
                $legacy_dsn = "mysql:host=blitz.cs.niu.edu;port=3306;dbname=csci467";
                $legacy_user = "student";
                $legacy_pass = "student";
                $legacy_pdo = new PDO($legacy_dsn, $legacy_user, $legacy_pass);
                $legacy_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                // Get all sales associates and their information
                $query = "SELECT * FROM sales_associate";
                $stmt = $pdo->prepare($query);
                $stmt->execute();

                // Display all associates and their information
                echo "<table border='1' cellpadding='5'>
                        <thead><tr>
                            <th>Associate ID</th>
                            <th>Name</th>
                            <th>Accumulated Commission</th>
                        </thead></tr><tbody>";
                while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    echo "<tr onclick=\"selectRow('" . htmlspecialchars($row['associate_id']) . "')\">
                        <td>" . htmlspecialchars($row['associate_id']) . "</td>
                        <td>" . htmlspecialchars($row['name']) . "</td>
                        <td>$" . htmlspecialchars($row['accumulated_commission']) . "</td>
                        </tr>";
                }
                echo "</tbody></table>";

                $stmt = $pdo->query("SELECT * FROM sales_associate");
                $associates = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $associate_count = count($associates);

            } catch (PDOException $e) {
                echo "Connection failed: " . $e->getMessage();
            }
            ?>

            <p>
                <strong>Associates Found: <?= $associate_count ?></strong>
            </p>

            <!-- Add Associate Button -->
            <form action="view_add_associate.php" method="POST">
                <input type="hidden" name="id" value="new">
                <button type="submit" style="background-color: #28a745;">Add New Associate</button>
            </form>
        </fieldset>
        <br>

        <fieldset>
            <legend style="font-weight: bold; font-size: 1.5em; color: #007bff;">Quotes</legend>
            <p>Search Quotes by Date, Status, Associate, & Customer</p>

            <form method="POST">
                <fieldset>
                    <legend><strong>Filter Quotes</strong></legend>

                    <!-- Date Range Inputs -->
                    <label for="from_date">From:</label>
                    <input type="date" name="from_date" id="from_date" value="<?= htmlspecialchars($_POST['from_date'] ?? '') ?>">

                    <label for="to_date">To:</label>
                    <input type="date" name="to_date" id="to_date" value="<?= htmlspecialchars($_POST['to_date'] ?? '') ?>">

                    <!-- Status Dropdown -->
                    <label for="status">Status:</label>
                    <select name="status" id="status">
                        <option value="">All</option>
                        <option value="open" <?= ($_POST['status'] ?? '') === 'open' ? 'selected' : '' ?>>Open</option>
                        <option value="finalized" <?= ($_POST['status'] ?? '') === 'finalized' ? 'selected' : '' ?>>Finalized</option>
                        <option value="sanctioned" <?= ($_POST['status'] ?? '') === 'sanctioned' ? 'selected' : '' ?>>Sanctioned</option>
                        <option value="ordered" <?= ($_POST['status'] ?? '') === 'ordered' ? 'selected' : '' ?>>Ordered</option>
                    </select>

                    <!-- Sales Associate Dropdown -->
                    <label for="associate">Sales Associate:</label>
                    <select name="associate" id="associate">
                        <option value="">All</option>
                        <?php foreach ($associates as $assoc): ?>
                            <option value="<?= $assoc['associate_id'] ?>" 
                                <?= ($_POST['associate'] ?? '') == $assoc['associate_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($assoc['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>

                    <!-- Customer Dropdown: Only those in existing quotes -->
                    <label for="customer">Customer:</label>
                    <select name="customer" id="customer">
                        <option value="">All</option>
                        <?php
                        try {
                            $stmt = $pdo->prepare("SELECT DISTINCT customer_id FROM quote");
                            $stmt->execute();
                            $customerIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                            foreach ($customerIds as $id) {
                                $custStmt = $legacy_pdo->prepare("SELECT name FROM customers WHERE id = ?");
                                $custStmt->execute([$id]);
                                $name = $custStmt->fetchColumn();
                
                                if ($name) {
                                    $selected = ($_POST['customer'] ?? '') == $id ? "selected" : "";
                                    echo "<option value='$id' $selected>" . htmlspecialchars($name) . "</option>";
                                }
                            }
                        } catch (PDOException $e) {
                            echo "<option disabled style='color:red;'>Error: " . htmlspecialchars($e->getMessage()) . "</option>";
                        }                                    
                        ?>
                    </select>
                    <br>
                    <button type="submit" style="margin-top: 10px; background-color: #28a745;">Search</button>
                </fieldset>
            </form>

            <?php

            try {
                $where = [];
                $params = [];

                if (!empty($_POST['status'])) {
                    $where[] = "q.status = :status";
                    $params[':status'] = $_POST['status'];
                }

                if (!empty($_POST['from_date'])) {
                    $where[] = "q.date_created >= :from_date";
                    $params[':from_date'] = $_POST['from_date'];
                }

                if (!empty($_POST['to_date'])) {
                    $where[] = "q.date_created <= :to_date";
                    $params[':to_date'] = $_POST['to_date'];
                }

                if (!empty($_POST['associate'])) {
                    $where[] = "q.associate_id = :associate";
                    $params[':associate'] = $_POST['associate'];
                }

                if (!empty($_POST['customer'])) {
                    $where[] = "q.customer_id = :customer";
                    $params[':customer'] = $_POST['customer'];
                }                

                $sql = "SELECT q.*, sa.name AS associate_name
                        FROM quote q
                        JOIN sales_associate sa ON q.associate_id = sa.associate_id";

                if ($where) {
                    $sql .= " WHERE " . implode(" AND ", $where);
                }

                $sql .= " ORDER BY q.date_created ASC";

                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $quotes = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($quotes as &$q) {
                    $stmt = $legacy_pdo->prepare("SELECT name FROM customers WHERE id = ?");
                    $stmt->execute([$q['customer_id']]);
                    $cust = $stmt->fetch(PDO::FETCH_ASSOC);
                    $q['customer_name'] = $cust['name'] ?? 'Unknown';
                }

                unset($q);

                if ($quotes) {
                    echo "<h3>Filtered Quotes</h3>";
                    echo "<table border='1' cellpadding='5'>";
                    echo "<thead><tr>
                            <th>ID</th>
                            <th>Date Created</th>
                            <th>Status</th>
                            <th>Sales Associate</th>
                            <th>Customer</th>
                            <th>Discount</th>
                            <th>Final Discount</th>
                            <th>Price</th>
                        </tr></thead><tbody>";
                    foreach ($quotes as $q) {
                        echo "<tr onclick=\"selectRowQuote('" . htmlspecialchars($q['quote_id']) . "')\">
                                <td>{$q['quote_id']}</td>
                                <td>{$q['date_created']}</td>
                                <td>{$q['status']}</td>
                                <td>" . htmlspecialchars($q['associate_name']) . "</td>
                                <td>" . htmlspecialchars($q['customer_name']) . "</td>
                                <td>" . 
                                    ($q['is_percent'] ? 
                                        htmlspecialchars($q['discount']) . '%' : 
                                        '$' . htmlspecialchars($q['discount'])) . 
                                "</td>
                                <td>\${$q['final_discount']}</td>
                                <td>\${$q['quote_price']}</td>
                            </tr>";
                    }
                    echo "</tbody></table>";
                } else {
                    echo "<p>No quotes found.</p>";
                }       
                
                $quote_count = count($quotes);
            } catch (PDOException $e) {
                echo "<p style='color:red;'>Error fetching quotes: " . htmlspecialchars($e->getMessage()) . "</p>";
            }
            ?> 

            <p>
                <strong>Quotes Found: <?= $quote_count ?></strong>
            </p>
        </fieldset>
        <a class="back-link" href="loginp.php">← Return to Login</a>
    </div>
    </body>
</html>