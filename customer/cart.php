<?php
require_once 'bootstrap.php';

$auth = new Auth();
if (!$auth->isSessionValid() || !$auth->isCustomer()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

function cart_get_tax_rate() {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key='tax_rate' LIMIT 1");
        $stmt->execute();
        $row = $stmt->fetch();
        return $row ? (float)$row['setting_value'] : 0.0;
    } catch (Exception $e) { return 0.0; }
}

$action = $_GET['action'] ?? ($_POST['action'] ?? 'get');

switch ($action) {
    case 'add':
        $id = (int)($_POST['id'] ?? 0);
        $name = htmlspecialchars(trim($_POST['name'] ?? ''));
        $price = (float)($_POST['price'] ?? 0);
        $qty = max(1, (int)($_POST['qty'] ?? 1));
        if ($id > 0 && $price >= 0 && $name !== '') {
            if (!isset($_SESSION['cart'][$id])) {
                $_SESSION['cart'][$id] = ['id' => $id, 'name' => $name, 'price' => $price, 'qty' => 0];
            }
            $_SESSION['cart'][$id]['qty'] += $qty;
        }
        echo json_encode(['ok' => true]);
        break;

    case 'remove':
        $id = (int)($_POST['id'] ?? 0);
        unset($_SESSION['cart'][$id]);
        echo json_encode(['ok' => true]);
        break;

    case 'clear':
        $_SESSION['cart'] = [];
        echo json_encode(['ok' => true]);
        break;

    case 'get':
    default:
        $items = [];
        foreach ($_SESSION['cart'] as $row) {
            $items[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'qty' => (int)$row['qty'],
                'price' => (float)$row['price'],
                'total' => (float)$row['price'] * (int)$row['qty'],
            ];
        }
        echo json_encode(['items' => $items, 'taxRate' => cart_get_tax_rate()]);
        break;
}
?>

