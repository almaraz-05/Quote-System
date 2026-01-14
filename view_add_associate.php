<html>
    <head>
        <title>Associate Information</title>
        <style>
            .container {
                width: 80%;
                margin: 40px auto;
                padding: 20px;
                background: #fff;
                border-radius: 8px;
                box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            }

            body {
                font-family: Arial, sans-serif;
                background: #f8f8f8;
                margin: 0;
                padding: 0;
                color: #333;
            }

            .form-row {
                display: grid;
                grid-template-columns: 300px 1fr;
                align-items: center;
                margin-bottom: 15px;
            }
            input[type="text"] {
                width: 100%;
                box-sizing: border-box;
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
        </style>
    </head>

    <body>
        <div class="container">
        <script>
            function cancelEdit() {
                window.location.href = "admin_interface.php";
            }
            function confirmDelete() {
                return confirm("Are you sure you want to delete this associate?");
            }
        </script>

    <?php
    include("secrets.php");

    if (isset($_GET['id'])) {
        $associate_id = $_GET['id'];
        $is_new = ($associate_id === "new");
        $delete_error = "";

        try {
            $dsn = "mysql:host=courses;dbname=$username";
            $pdo = new PDO($dsn, $username, $password);

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $params = [
                    'name' => $_POST['name'],
                    'address' => $_POST['address'],
                    'userid' => $_POST['userid'],
                    'password' => $_POST['password'],
                    'commission' => $_POST['commission'],
                ];

                // Save updated associate info
                if (isset($_POST['save'])) {
                    if ($is_new) {
                        // Insert new associate
                        $stmt = $pdo->prepare("INSERT INTO sales_associate (name, address, userid, password, accumulated_commission) 
                                VALUES (:name, :address, :userid, :password, :commission)");
                    } else {
                        // Update existing associate
                        $stmt = $pdo->prepare("UPDATE sales_associate 
                                            SET name = :name, address = :address, userid = :userid, password = :password, accumulated_commission = :commission 
                                            WHERE associate_id = :id");
                        $params['id'] = $associate_id;
                    }

                    if ($is_new) {
                        $stmt->execute($params);
                    } else {
                        $params['id'] = $associate_id;
                        $stmt->execute($params);
                    }

                    // Redirect back to admin interface after save
                    header("Location: admin_interface.php");
                    exit;
                }

                if (isset($_POST['delete'])) {
                    // Check if associate has quotes
                    $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM quote WHERE associate_id = :id");
                    $checkStmt->execute(['id' => $associate_id]);
                    $quoteCount = $checkStmt->fetchColumn();
        
                    if ($quoteCount > 0) {
                        $delete_error = "Cannot delete associate. There are $quoteCount quote(s) linked to this associate.";
                    } else {
                        $delStmt = $pdo->prepare("DELETE FROM sales_associate WHERE associate_id = :id");
                        $delStmt->execute(['id' => $associate_id]);
                        header("Location: admin_interface.php");
                        exit;
                    }
                }
            }

            if ($is_new) {
                // Initialize blank values for new associate
                $row = [
                    'name' => '',
                    'address' => '',
                    'userid' => '',
                    'password' => '',
                    'accumulated_commission' => '0.00'
                ];
            } else {
                $stmt = $pdo->prepare("SELECT * FROM sales_associate WHERE associate_id = :id");
                $stmt->execute(['id' => $associate_id]);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$row) {
                    echo "Associate not found.";
                    exit;
                }
            } 

        } catch (PDOException $e) {
            echo "Error: " . $e->getMessage();
        }
    }
    ?>
        <fieldset>
            <legend style="font-weight: bold; font-size: 1.5em; color: #007bff;">Associate Details</legend>
            <br>
            <form method="post">
                <div class="form-row">
                <label><strong>Name:</strong>
                    <input type="text" id="name" name="name" value="<?= htmlspecialchars($row['name']) ?>">
                </label></div>

                <div class="form-row">
                <label><strong>Address:</strong>
                    <input type="text" id="address" name="address" value="<?= htmlspecialchars($row['address']) ?>">
                </label></div>

                <div class="form-row">
                <label><strong>User ID:</strong>
                    <input type="text" id="userid" name="userid" value="<?= htmlspecialchars($row['userid']) ?>">
                </label></div>

                <div class="form-row">
                <label><strong>Password:</strong>
                    <input type="text" id="password" name="password" value="<?= htmlspecialchars($row['password']) ?>">
                </label></div>

                <div class="form-row">
                <label><strong>Commission:</strong>
                    <input type="text" id="commission" name="commission" value="<?= htmlspecialchars($row['accumulated_commission']) ?>">
                </label></div>

                <button type="submit" name="save" style="background-color: #28a745;">Save</button>
                <button type="button" onclick="cancelEdit()">Cancel</button>
                <?php if (!$is_new): ?>
                    <button type="submit" name="delete" onclick="return confirmDelete()" style="background-color: red;">
                        Delete Associate
                    </button>
                <?php endif; ?>
            </form>
        </fieldset>
    </div>
    </body>
</html>