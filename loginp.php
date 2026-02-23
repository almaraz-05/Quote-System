<?php
// start a seccion so we can store login info across pages
session_start();
// turn on error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include("secrets.php");

// only run this login code if the form was submitted using POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // dsn tells PHP what database to connect to
        $dsn = "mysql:host=courses;dbname=$username";
        // create a new PDO connection using DSN + credentials
        $pdo = new PDO($dsn, $username, $password);
    } catch (PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }

    // get the userid and password the user typed in the form
    $UserID = $_POST['userid'];
    $Password = $_POST['password'];

    // prepare SQL query to find user with matching userid
    //using prepared statements prevents SQL injection
    $stmt = $pdo->prepare("SELECT * FROM sales_associate WHERE userid = :userid");

    // run the query and replace :userid with actual value
    $stmt->execute([':userid' => $UserID]);

    // fetch the row from the database as an associative array
    $sales_associate = $stmt->fetch(PDO::FETCH_ASSOC);

    // did we find a user?
    // does entered password match stored password?
    if ($sales_associate && $Password == $sales_associate['password']) {
        $_SESSION['userid'] = $sales_associate['userid'];
        $_SESSION['associate_id'] = $sales_associate['associate_id'];

        // redirect to another page if succesful
        header("Location: Quotes.php");
        exit;
    } else {
        // if login fails.
        echo "Invalid username or password.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Login</title>
  <style>
    body {
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      background: #f5f7fa;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      margin: 0;
    }

    .login-container {
      background: white;
      padding: 40px;
      border-radius: 8px;
      box-shadow: 0px 4px 15px rgba(0, 0, 0, 0.1);
      width: 350px;
      text-align: center;
    }

    h1 {
      margin-bottom: 24px;
      color: #333;
    }

    input[type="text"],
    input[type="password"] {
      width: 100%;
      padding: 12px;
      margin: 10px 0 20px;
      border: 1px solid #ccc;
      border-radius: 6px;
      font-size: 16px;
    }

    button {
      width: 100%;
      padding: 12px;
      background: #007bff;
      color: white;
      border: none;
      border-radius: 6px;
      font-size: 16px;
      cursor: pointer;
    }

    button:hover {
      background: #0056b3;
    }

    .error-message {
      color: red;
      margin-bottom: 16px;
    }

    .register-link {
      margin-top: 16px;
      font-size: 14px;
    }

    .register-link a {
      color: #007bff;
      text-decoration: none;
    }

    .register-link a:hover {
      text-decoration: underline;
    }
  </style>
</head>
<body>
  <div class="login-container">
    <h1>Sign In</h1>

    <?php if (!empty($error)): ?>
      <div class="error-message"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="post">
      <input type="text" name="userid" placeholder="Username" required>
      <input type="password" name="password" placeholder="Password" required>
      <button type="submit">Login</button>
    </form>
    <div class="admin-login">
      <form action="admin_interface.php" method="get">
        <button type="submit" style="margin-top: 10px; background-color: #28a745;">Admin Login</button>
      </form>
    </div>
    <div class="hq-login">
      <form action="quotes_hq.php" method="get">
        <button type="submit" style="margin-top: 10px; background-color: #6F7378">HQ Login</button>
      </form>
    </div>

    </div>
  </div>
</body>
</html>
