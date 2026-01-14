<?php
   session_start();
   include("secrets.php");

   $customer_id = $_POST['customer_id'] ?? null;
   $associate_id = $_POST['associate_id'] ?? null;

   if (!$customer_id || !$associate_id) {
      die("Missing customer or associate ID");
   }


   $legacy_dsn = 'mysql:host=blitz.cs.niu.edu;dbname=csci467';
   $legacy_user = 'student';

   try {
     $dsn = "mysql:host=courses;dbname=$username";
     $pdo = new PDO($dsn, $username, $password);
     $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

     $legacy_pdo = new PDO($legacy_dsn, $legacy_user, $legacy_user);
     $legacy_pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

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

     if (isset($_POST['status_btn'])) {
         $email = $_POST['customer_email'];
         $date = date('Y-m-d');
         $status = $_POST['status_btn'];

         $total = 0.00;

         if (isset($_POST['prices']) && is_array($_POST['prices'])) {
             foreach ($_POST['prices'] as $price) {
                 $price = floatval($price);
                 $total += $price;
             }
         }

         $discount = 0;
         $quote_amount = max($total, 0);

         $stmt2 = $pdo->prepare("INSERT INTO quote (associate_id, date_created, customer_id, customer_email, status, discount, quote_price) VALUES (?,?,?,?,?,?,?)");
         $stmt2->execute([$associate_id, $date, $customer_id, $email, $status, $discount, $quote_amount]);

         $quote_id = $pdo->lastInsertId();

         if (!empty($_POST['descriptions']) && !empty($_POST['prices'])) {
             foreach ($_POST['descriptions'] as $i => $desc) {
                 $description = htmlspecialchars($desc);
                 $price = floatval($_POST['prices'][$i]);

                 $stmt3 = $pdo->prepare("INSERT INTO line_item (quote_id, description, price) VALUES (?,?,?)");
                 $stmt3->execute([$quote_id, $description, $price]);
             }
         }

         if (!empty($_POST['notes'])) {
             foreach ($_POST['notes'] as $i => $note) {
                 $description = htmlspecialchars($note);

                 $stmt4 = $pdo->prepare("INSERT INTO secret_note (quote_id, description) VALUES (?,?)");
                 $stmt4->execute([$quote_id, $description]);
             }
         }

         header("Location: Quotes.php");
         exit;
     }

   } catch (PDOException $e) {
       echo "Connection failed: " . $e->getMessage();
       exit;
   }
?>

<html>
   <head>
      <title> Quotes System </title>
   </head>

   <body>
      <div class="container">
      <h2>Quote for: <?php echo $customer_name; ?> </h2>   
      <p><?php echo $customer_street; ?> <br>
          <?php echo $customer_city; ?> <br>
          <?php echo $customer_contact; ?>
</p>

      <form action="create_quote.php" method="POST">
         <input type="hidden" name="customer_id" value="<?= htmlspecialchars($customer_id) ?>">
         <input type="hidden" name="associate_id" value="<?= htmlspecialchars($_SESSION['associate_id']) ?>">

         <label for="email"><strong>Email: </strong></label>
         <input type="text" id="customerEmail" name="customer_email" required><br><br>

	 <h3>Line Items: <button type="button" id="add_line_btn" style="padding: 6px">New Item</button></h3>
         
	 <table id="line_items">
	    <tbody>

            </tbody>
         </table>

         <h3>Secret Notes: <button type="button" id="add_secret_btn" style="padding: 6px">New Note</button></h3>

         <table id="secret_notes">
            <tbody>
            
            </tbody>
	 </table>

         <br>
         <hr>

	 <h3>Amount: <span id="totalAmount"></span></h3>

	 <button type="submit" name="status_btn" style="float:right; background-color: #28a745; margin-left: 10px;" value="open">Create</button>
    <button type="button" id="cancel_btn" style="float:right;" onclick="window.history.back();">Cancel</button>
         <br>
	 <hr>
      </form>
   </div>
   </body>
</html>

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
      width: 60%;
   }

   button {
      width: 20%;
      padding: 12px;
      background: #007bff;
      color: white;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      white-space: nowrap;
   }

</style>

<script>
   document.addEventListener('DOMContentLoaded', function() {
      document.getElementById('add_line_btn').addEventListener('click', function() {

         const tableBody = document.querySelector('#line_items tbody');
         const newLine = document.createElement('tr');

         newLine.innerHTML = `
            <td><input type="text" name="descriptions[]" class="description" ></td>
            <td><input type="number" step="0.01" name="prices[]" class="num" placeholder="0"></td>
            <td><button type="button" class="addBtn" style="width: 60px; padding: 5px">Add</button></td>
            <td><button type="button" class="deleteBtn" style="width: 60px; padding: 5px">Delete</button></td>
         `;

	 tableBody.appendChild(newLine);

      newLine.querySelector('.addBtn').addEventListener('click', function(){
	 calculateTotal();
      });

      newLine.querySelector('.deleteBtn').addEventListener('click', function(){
         this.closest('tr').remove();
         calculateTotal();
      });
      });
   });


 document.addEventListener('DOMContentLoaded', function() {
      document.getElementById('add_secret_btn').addEventListener('click', function() {
         const body = document.querySelector('#secret_notes tbody');
         const newSecret = document.createElement('tr');

	 newSecret.innerHTML = `
            <td><input type="text" name="notes[]" class="note" value=""></td>
            <td><button type="button" class="deleteRowBtn" style="width: 60px; padding: 5px">Delete</button></td>
         `;

         body.appendChild(newSecret);

      newSecret.querySelector('.deleteRowBtn').addEventListener('click', function(){
         this.closest('tr').remove();
      });
      });
 });

   function calculateTotal(discount=0){
      let total = 0;
      const item = document.querySelectorAll('.num');

      item.forEach(input => {
         const price = parseFloat(input.value);
         if (!isNaN(price)) {
               total += price;
         }
      });

      document.getElementById('totalAmount').textContent = total.toFixed(2);
   }

  window.onload = calculateTotal;
</script>
