<?php
require_once 'bootstrap.php';

$auth = new Auth();
if (!$auth->isSessionValid() || !$auth->isCustomer()) {
    redirect('../index.php');
}

$db = getDB();
$user = $auth->getCurrentUser();
$error = '';
$success = '';

// Check if cart is empty
if (empty($_SESSION['cart'])) {
    redirect('home.php');
}

// Get cart items with menu item details
$cartItems = [];
$total = 0;
foreach ($_SESSION['cart'] as $item) {
    $stmt = $db->prepare("SELECT name, price FROM menu_items WHERE id = ? AND is_available = 1");
    $stmt->execute([$item['id']]);
    $menuItem = $stmt->fetch();
    
    if ($menuItem) {
        $itemTotal = $item['price'] * $item['qty'];
        $cartItems[] = [
            'id' => $item['id'],
            'name' => $menuItem['name'],
            'price' => $item['price'],
            'qty' => $item['qty'],
            'total' => $itemTotal
        ];
        $total += $itemTotal;
    }
}

if (empty($cartItems)) {
    redirect('home.php');
}

// Get tax rate
$taxRate = 0.0;
try {
    $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key='tax_rate' LIMIT 1");
    $stmt->execute();
    $row = $stmt->fetch();
    if ($row) { $taxRate = (float)$row['setting_value']; }
} catch (Exception $e) { /* ignore */ }

$tax = round(($taxRate / 100.0) * $total, 2);
$finalTotal = $total + $tax;

// Handle checkout form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'checkout') {
    $token = $_POST['csrf_token'] ?? '';
    if (!verifyCSRFToken($token)) {
        $error = 'Invalid request token.';
    } else {
        $orderType = $_POST['order_type'] ?? 'takeout';
        $tableId = isset($_POST['table_id']) && $_POST['table_id'] !== '' ? (int)$_POST['table_id'] : null;
        $paymentMethod = $_POST['payment_method'] ?? 'cash';
        $specialInstructions = sanitizeInput($_POST['special_instructions'] ?? '');
        
        if (!in_array($orderType, ['dine_in', 'takeout', 'delivery'], true)) {
            $error = 'Invalid order type.';
        } elseif ($orderType === 'dine_in' && !$tableId) {
            $error = 'Please select a table for dine-in orders.';
        } else {
            try {
                $db->beginTransaction();
                
                // Create order
                $stmt = $db->prepare("
                    INSERT INTO orders (customer_id, table_id, order_type, status, total_amount, tax_amount, discount_amount, final_amount, payment_status, payment_method, special_instructions, created_at) 
                    VALUES (?, ?, ?, 'pending', ?, ?, 0.00, ?, 'pending', ?, ?, NOW())
                ");
                $stmt->execute([$user['id'], $tableId, $orderType, $total, $tax, $finalTotal, $paymentMethod, $specialInstructions]);
                $orderId = (int)$db->lastInsertId();
                
                // Add order items
                $stmt = $db->prepare("
                    INSERT INTO order_items (order_id, menu_item_id, quantity, unit_price, total_price, created_at) 
                    VALUES (?, ?, ?, ?, ?, NOW())
                ");
                foreach ($cartItems as $item) {
                    $stmt->execute([$orderId, $item['id'], $item['qty'], $item['price'], $item['total']]);
                }
                
                // Clear cart
                $_SESSION['cart'] = [];
                
                // Award loyalty points (1 point per dollar spent)
                $points = (int)$finalTotal;
                $stmt = $db->prepare("UPDATE customer_profiles SET loyalty_points = loyalty_points + ? WHERE user_id = ?");
                $stmt->execute([$points, $user['id']]);
                
                $db->commit();
                
                // Redirect to confirmation page
                redirect("order-confirmation.php?order_id=$orderId");
                
            } catch (Exception $e) {
                if ($db->inTransaction()) { $db->rollBack(); }
                $error = 'Failed to place order. Please try again.';
            }
        }
    }
}

// Get available tables for dine-in
$tables = $db->query("SELECT id, table_number, capacity FROM tables WHERE is_available=1 ORDER BY table_number")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Checkout</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="styles/checkout-style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom">
        <div class="container">
            <a class="navbar-brand" href="home.php"><i class="fas fa-utensils me-2"></i>Delicious</a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="home.php"><i class="fas fa-home me-1"></i>Home</a>
                <a class="nav-link" href="orders.php"><i class="fas fa-receipt me-1"></i>Orders</a>
                <a class="nav-link" href="profile.php"><i class="fas fa-user me-1"></i>Profile</a>
                <a class="nav-link" href="../admin/logout.php"><i class="fas fa-sign-out-alt me-1"></i>Logout</a>
            </div>
        </div>
    </nav>

    <div class="container my-4">
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="fas fa-credit-card me-2"></i>Checkout</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>

                        <form method="POST" action="" id="checkoutForm">
                            <input type="hidden" name="action" value="checkout">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(generateCSRFToken()); ?>">
                            
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Order Type</label>
                                    <select name="order_type" id="orderType" class="form-select" required>
                                        <option value="takeout">Takeout</option>
                                        <option value="dine_in">Dine In</option>
                                        <option value="delivery">Delivery</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-6" id="tableSelectWrapper" style="display: none;">
                                    <label class="form-label">Table (for Dine In)</label>
                                    <select name="table_id" class="form-select">
                                        <option value="">Select table</option>
                                        <?php foreach ($tables as $table): ?>
                                            <option value="<?php echo (int)$table['id']; ?>">
                                                Table <?php echo htmlspecialchars($table['table_number']); ?> (<?php echo (int)$table['capacity']; ?> seats)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="col-md-6">
                                    <label class="form-label">Payment Method</label>
                                    <select name="payment_method" class="form-select" required>
                                        <option value="cash">Cash</option>
                                        <option value="credit_card">Credit Card</option>
                                        <option value="debit_card">Debit Card</option>
                                        <option value="digital_wallet">Digital Wallet</option>
                                    </select>
                                </div>
                                
                                <div class="col-12">
                                    <label class="form-label">Special Instructions (Optional)</label>
                                    <textarea name="special_instructions" class="form-control" rows="3" placeholder="Any special requests or notes..."></textarea>
                                </div>
                            </div>
                            
                            <div class="text-end mt-4">
                                <a href="home.php" class="btn btn-outline-secondary me-2">
                                    <i class="fas fa-arrow-left me-2"></i>Back to Menu
                                </a>
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-check me-2"></i>Place Order
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header bg-white">
                        <h5 class="mb-0">Order Summary</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <?php foreach ($cartItems as $item): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <div class="fw-bold"><?php echo htmlspecialchars($item['name']); ?></div>
                                    <small class="text-muted">Qty: <?php echo $item['qty']; ?> × $<?php echo number_format($item['price'], 2); ?></small>
                                </div>
                                <span class="fw-bold">$<?php echo number_format($item['total'], 2); ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <div class="card-footer bg-white">
                        <div class="d-flex justify-content-between">
                            <span>Subtotal:</span>
                            <span>$<?php echo number_format($total, 2); ?></span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Tax (<?php echo $taxRate; ?>%):</span>
                            <span>$<?php echo number_format($tax, 2); ?></span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between fw-bold fs-5">
                            <span>Total:</span>
                            <span class="text-success">$<?php echo number_format($finalTotal, 2); ?></span>
                        </div>
                        <div class="text-center mt-2">
                            <small class="text-muted">
                                <i class="fas fa-gift me-1"></i>
                                You'll earn <?php echo (int)$finalTotal; ?> loyalty points!
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="scripts/checkout-script.js"></script>
</body>
</html>
