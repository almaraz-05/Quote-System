<?php
   session_start();
   include("secrets.php");

   $customer_id = $_POST['customer_id'] ?? null;
   $associate_id = $_POST['associate_id'] ?? null;
   $quote_id = $_POST['quote_id'] ?? null;

   if (!$customer_id || !$associate_id || !$quote_id) {
      die("Missing customer, associate, or quote ID");
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

     if (isset($_POST['status_btn'])) {
         $email = $_POST['customer_email'];
         $date = date('Y-m-d');
         $status = 'finalized';

         $discount = isset($_POST['discount']) ? floatval($_POST['discount']) : 0.00;
         $total = 0.00;

         if (isset($_POST['prices']) && is_array($_POST['prices'])) {
             foreach ($_POST['prices'] as $price) {
                 $price = floatval($price);
                 $total += $price;
             }
         }

         // Apply discount
         if ($_POST['choice'] === 'percent') {
             $quote_amount = $total - ($total * ($discount / 100));
         } else {
             $quote_amount = $total - $discount;
         }
         $quote_amount = max($quote_amount, 0);

         $is_percent = ($_POST['choice'] === 'percent') ? 1 : 0;

         $stmt2 = $pdo->prepare("UPDATE quote SET customer_email = ?, status = ?, quote_price = ?, discount = ?, is_percent = ? WHERE quote_id = ?");
         $stmt2->execute([$email, $status, $quote_amount, $discount, $is_percent, $quote_id]);

         $pdo->prepare("DELETE FROM line_item WHERE quote_id = ?")->execute([$quote_id]);
            if (!empty($_POST['descriptions']) && !empty($_POST['prices'])) {
                foreach ($_POST['descriptions'] as $i => $desc) {
                    $description = htmlspecialchars($desc);
                    $price = floatval($_POST['prices'][$i]);
                    $stmt3 = $pdo->prepare("INSERT INTO line_item (quote_id, description, price) VALUES (?,?,?)");
                    $stmt3->execute([$quote_id, $description, $price]);
                }
            }

            $pdo->prepare("DELETE FROM secret_note WHERE quote_id = ?")->execute([$quote_id]);
            if (!empty($_POST['notes'])) {
                foreach ($_POST['notes'] as $i => $note) {
                    $description = htmlspecialchars($note);
                    $stmt4 = $pdo->prepare("INSERT INTO secret_note (quote_id, description) VALUES (?,?)");
                    $stmt4->execute([$quote_id, $description]);
                }
            }

         header("Location: quotes_hq.php");
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

      <form action="edit_quote.php" method="POST">
         <input type="hidden" name="customer_id" value="<?= htmlspecialchars($customer_id) ?>">
         <input type="hidden" name="associate_id" value="<?= htmlspecialchars($_SESSION['associate_id']) ?>">
         <input type="hidden" name="quote_id" value="<?= htmlspecialchars($quote_id) ?>">

         <label for="email"><strong>Email: </strong></label>
         <input type="text" id="customerEmail" name="customer_email" value="<?= htmlspecialchars($email) ?>" readonly><br><br>

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

         <label for="discount"><strong>Discount: </strong></label>
	 <input type="number" id="discountAmount" name="discount" value="<?= htmlspecialchars($discount ?? '0.00') ?>"><br><br>
	 <button type="button" id="discount_btn" style="padding: 5px">Apply</button>
         <label><input type="radio" name="choice" id="percent" style="accent-color: #007bff" value="percent" <?= $isPercent ? 'checked' : '' ?>>Percent</label>
         <label><input type="radio" name="choice" id="fixed" style="accent-color: #007bff" value="fixed" <?= !$isPercent ? 'checked' : '' ?>>Amount</label>

	 <p><strong>Discount Amount: $<span id="discountDisplay">0.00</span></strong></p>
     <p><strong>Total After Discount: $<span id="totalAmount">0.00</span></strong></p>

	 <button type="submit" name="status_btn" style="float:right; background-color: #28a745; margin-left: 10px;" value="open"\>Save</button>
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
    const existingLineItems = <?= json_encode($line_items) ?>;
    const existingNotes = <?= json_encode($secret_notes) ?>;

    const lineTable = document.querySelector('#line_items tbody');
    existingLineItems.forEach(item => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><input type="text" name="descriptions[]" class="description" value="${item.description}"></td>
            <td><input type="number" step="0.01" name="prices[]" class="num" value="${item.price}"></td>
            <td><button type="button" class="addBtn" style="width: 60px; padding: 5px">Add</button></td>
            <td><button type="button" class="deleteBtn" style="width: 60px; padding: 5px">Delete</button></td>
        `;
        lineTable.appendChild(tr);

        tr.querySelector('.addBtn').addEventListener('click', () => calculateTotal());
        tr.querySelector('.deleteBtn').addEventListener('click', () => {
            tr.remove();
            calculateTotal();
        });
        tr.querySelector('.num').addEventListener('input', calculateTotal);
    });

    const notesTable = document.querySelector('#secret_notes tbody');
    existingNotes.forEach(note => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><input type="text" name="notes[]" class="note" value="${note.description}"></td>
            <td><button type="button" class="deleteRowBtn" style="width: 60px; padding: 5px">Delete</button></td>
        `;
        notesTable.appendChild(tr);
        tr.querySelector('.deleteRowBtn').addEventListener('click', () => tr.remove());
    });

    document.getElementById('add_line_btn').addEventListener('click', () => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><input type="text" name="descriptions[]" class="description" value=""></td>
            <td><input type="number" step="0.01" name="prices[]" class="num" value="0.00"></td>
            <td><button type="button" class="addBtn" style="width: 60px; padding: 5px">Add</button></td>
            <td><button type="button" class="deleteBtn" style="width: 60px; padding: 5px">Delete</button></td>
        `;
        lineTable.appendChild(tr);

        tr.querySelector('.addBtn').addEventListener('click', () => calculateTotal());
        tr.querySelector('.deleteBtn').addEventListener('click', () => {
            tr.remove();
            calculateTotal();
        });
        tr.querySelector('.num').addEventListener('input', calculateTotal);

        calculateTotal();
    });

    document.getElementById('add_secret_btn').addEventListener('click', () => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><input type="text" name="notes[]" class="note" value=""></td>
            <td><button type="button" class="deleteRowBtn" style="width: 60px; padding: 5px">Delete</button></td>
        `;
        document.querySelector('#secret_notes tbody').appendChild(tr);
        tr.querySelector('.deleteRowBtn').addEventListener('click', () => tr.remove());
    });

    document.getElementById('discount_btn').addEventListener('click', () => {
        calculateTotal();
    });

    // Also recalc total if discount input changes or radio choice changes
    document.getElementById('discountAmount').addEventListener('input', calculateTotal);
    document.querySelectorAll('input[name="choice"]').forEach(radio => {
        radio.addEventListener('change', calculateTotal);
    });

    // Initial calculation on page load
    calculateTotal();
});

function calculateTotal() {
    let total = 0;

    document.querySelectorAll('.num').forEach(input => {
        const price = parseFloat(input.value);
        if (!isNaN(price)) total += price;
    });

    const discountInput = document.getElementById('discountAmount');
    let discount = parseFloat(discountInput.value);
    if (isNaN(discount)) discount = 0;

    let discountAmount = 0;
    let totalAmount = total;

    if (discount !== 0) {
        if (document.getElementById('percent').checked) {
            discountAmount = total * (discount / 100);
        } else {
            discountAmount = discount;
        }

        if (discountAmount > total) discountAmount = total;
        totalAmount = total - discountAmount;
    }

    if (totalAmount < 0) totalAmount = 0;

    document.getElementById('totalAmount').textContent = totalAmount.toFixed(2);
    document.getElementById('discountDisplay').textContent = discountAmount.toFixed(2);
}



  window.onload = calculateTotal;
</script>
