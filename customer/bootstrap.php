<?php
require_once __DIR__ . '/../includes/config.php';

// Bridge: provide getDB() expected by customer pages
if (!function_exists('getDB')) {
    function getDB() {
        return getDBConnection();
    }
}

// Bridge: simple redirect helper
if (!function_exists('redirect')) {
    function redirect($path) {
        if (preg_match('/^https?:/i', $path)) {
            header('Location: ' . $path);
        } elseif (str_starts_with($path, '/')) {
            header('Location: ' . $path);
        } else {
            header('Location: ' . $path);
        }
        exit;
    }
}

// Define APP_NAME from settings or fallback once per request
if (!defined('APP_NAME')) {
    $appName = 'Restaurant';
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key='restaurant_name' LIMIT 1");
        $stmt->execute();
        $row = $stmt->fetch();
        if ($row && !empty($row['setting_value'])) {
            $appName = $row['setting_value'];
        }
    } catch (Exception $e) { /* ignore */ }
    define('APP_NAME', $appName);
}

// Flexible date formatter used by customer pages
if (!function_exists('formatDateFlexible')) {
    function formatDateFlexible($datetime, $format = null) {
        if ($format) {
            return date($format, strtotime($datetime));
        }
        if (function_exists('formatDateTime')) {
            return formatDateTime($datetime);
        }
        return date('M d, Y H:i', strtotime($datetime));
    }
}

// Minimal Auth implementation for customer area
if (!class_exists('Auth')) {
    class Auth {
        public function isSessionValid() {
            return isset($_SESSION['user_id']) && isset($_SESSION['user_type']);
        }

        public function isCustomer() {
            return $this->isSessionValid() && $_SESSION['user_type'] === 'customer';
        }

        public function getCurrentUser() {
            if (!$this->isSessionValid()) return null;
            $db = getDB();
            $stmt = $db->prepare('SELECT * FROM users WHERE id = ? LIMIT 1');
            $stmt->execute([$_SESSION['user_id']]);
            return $stmt->fetch();
        }

        public function login($usernameOrEmail, $password) {
            $db = getDB();
            $stmt = $db->prepare('SELECT * FROM users WHERE (username = ? OR email = ?) AND is_active = 1 LIMIT 1');
            $stmt->execute([$usernameOrEmail, $usernameOrEmail]);
            $user = $stmt->fetch();
            if ($user && password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id'] = (int)$user['id'];
                $_SESSION['user_type'] = $user['user_type'];
                return ['success' => true, 'user_type' => $user['user_type']];
            }
            return ['success' => false, 'message' => 'Invalid credentials'];
        }

        public function register($data) {
            $db = getDB();

            // Basic validations
            if ($data['username'] === '' || $data['email'] === '' || $data['password'] === '' || $data['confirm_password'] === '') {
                return ['success' => false, 'message' => 'Please fill in all required fields.'];
            }
            if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                return ['success' => false, 'message' => 'Invalid email format.'];
            }
            if ($data['password'] !== $data['confirm_password']) {
                return ['success' => false, 'message' => 'Passwords do not match.'];
            }

            // Check duplicates
            $stmt = $db->prepare('SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1');
            $stmt->execute([$data['username'], $data['email']]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Username or email already exists.'];
            }

            try {
                $db->beginTransaction();
                $hash = password_hash($data['password'], PASSWORD_BCRYPT);
                $stmt = $db->prepare('INSERT INTO users (username, email, password_hash, first_name, last_name, phone, user_type, is_active) VALUES (?, ?, ?, ?, ?, ?, "customer", 1)');
                $stmt->execute([
                    $data['username'],
                    $data['email'],
                    $hash,
                    $data['first_name'] ?? '',
                    $data['last_name'] ?? '',
                    $data['phone'] ?? null,
                ]);
                $userId = (int)$db->lastInsertId();

                // Optional customer profile
                $stmt = $db->prepare('INSERT INTO customer_profiles (user_id, address, city, state, zip_code) VALUES (?, ?, ?, ?, ?)');
                $stmt->execute([
                    $userId,
                    $data['address'] ?? '',
                    $data['city'] ?? '',
                    $data['state'] ?? '',
                    $data['zip_code'] ?? '',
                ]);

                $db->commit();
                return ['success' => true];
            } catch (Exception $e) {
                if ($db->inTransaction()) { $db->rollBack(); }
                return ['success' => false, 'message' => 'Registration failed. Please try again.'];
            }
        }
    }
}

?>


