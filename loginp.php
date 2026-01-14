<?php
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include("secrets.php");

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $dsn = "mysql:host=courses;dbname=$username";
        $pdo = new PDO($dsn, $username, $password);
    } catch (PDOException $e) {
        die("Connection failed: " . $e->getMessage());
    }

    $UserID = $_POST['userid'];
    $Password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM sales_associate WHERE userid = :userid");
    $stmt->execute([':userid' => $UserID]);
    $sales_associate = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($sales_associate && $Password == $sales_associate['password']) {
        $_SESSION['userid'] = $sales_associate['userid'];
        $_SESSION['associate_id'] = $sales_associate['associate_id'];
        header("Location: Quotes.php");
        exit;
    } else {
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
