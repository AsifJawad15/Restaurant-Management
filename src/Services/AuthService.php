<?php

namespace RestaurantMS\Services;

use RestaurantMS\Models\User;
use RestaurantMS\Models\Customer;
use RestaurantMS\Exceptions\AuthException;
use RestaurantMS\Exceptions\ValidationException;

/**
 * Authentication Service - Simple auth management
 * 
 * Handles login, logout, registration, and session management
 * Replaces the existing procedural auth code
 */
class AuthService
{
    private static ?AuthService $instance = null;
    
    /**
     * Get singleton instance
     */
    public static function getInstance(): AuthService
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Start session if not already started
     */
    private function ensureSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    /**
     * Login admin user
     */
    public function loginAdmin(string $email, string $password): bool
    {
        $this->ensureSession();
        
        // Find admin user
        $user = User::findByEmail($email);
        if (!$user || $user->user_type !== User::TYPE_ADMIN) {
            return false;
        }
        
        // Verify password
        if (!$user->verifyPassword($password)) {
            return false;
        }
        
        // Check if user is active
        if (!$user->isActive()) {
            return false;
        }
        
        // Set admin session data
        $_SESSION['admin_id'] = $user->id;
        $_SESSION['admin_email'] = $user->email;
        $_SESSION['admin_name'] = $user->first_name . ' ' . $user->last_name;
        $_SESSION['admin_username'] = $user->username;
        
        // Update last login
        $user->updateLastLogin();
        
        return true;
    }
    
    /**
     * Check if admin is logged in
     */
    public function isAdminLoggedIn(): bool
    {
        $this->ensureSession();
        return isset($_SESSION['admin_id']) && isset($_SESSION['admin_email']);
    }
    
    /**
     * Login customer user
     */
    public function loginCustomer(string $email, string $password): bool
    {
        $this->ensureSession();
        
        // Find customer user
        $user = User::findByEmail($email);
        if (!$user || $user->user_type !== User::TYPE_CUSTOMER) {
            return false;
        }
        
        // Verify password
        if (!$user->verifyPassword($password)) {
            return false;
        }
        
        // Check if user is active
        if (!$user->isActive()) {
            return false;
        }
        
        // Set customer session data
        $_SESSION['customer_id'] = $user->id;
        $_SESSION['customer_email'] = $user->email;
        $_SESSION['customer_name'] = $user->first_name . ' ' . $user->last_name;
        $_SESSION['customer_username'] = $user->username;
        
        // Update last login
        $user->updateLastLogin();
        
        return true;
    }
    
    /**
     * Check if customer is logged in
     */
    public function isCustomerLoggedIn(): bool
    {
        $this->ensureSession();
        return isset($_SESSION['customer_id']) && isset($_SESSION['customer_email']);
    }

    /**
     * Login user with email/username and password
     */
    public function login(string $emailOrUsername, string $password): User
    {
        $this->ensureSession();
        
        // Find user by email or username
        $user = User::findByEmail($emailOrUsername);
        if (!$user) {
            $user = User::findByUsername($emailOrUsername);
        }
        
        if (!$user) {
            throw new AuthException('Invalid login credentials');
        }
        
        // Verify password
        if (!$user->verifyPassword($password)) {
            throw new AuthException('Invalid login credentials');
        }
        
        // Check if user is active
        if (!$user->isActive()) {
            throw new AuthException('Account is inactive');
        }
        
        // Set session data
        $_SESSION['user_id'] = $user->id;
        $_SESSION['user_type'] = $user->user_type;
        $_SESSION['username'] = $user->username;
        $_SESSION['logged_in'] = true;
        
        // Update last login
        $user->updateLastLogin();
        
        return $user;
    }
    
    /**
     * Register new customer
     */
    public function registerCustomer(array $userData): Customer
    {
        $this->ensureSession();
        
        try {
            // Create user account
            $userData['user_type'] = User::TYPE_CUSTOMER;
            $userData['is_active'] = true;
            $userData['email_verified'] = false;
            
            $user = User::create($userData);
            
            // Create customer profile
            $customer = Customer::create([
                'user_id' => $user->id,
                'loyalty_points' => 0,
                'total_spent' => 0.00,
                'visit_count' => 0,
                'tier_level' => Customer::TIER_BRONZE
            ]);
            
            return $customer;
            
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            throw new AuthException('Registration failed: ' . $e->getMessage());
        }
    }
    
    /**
     * Logout current user
     */
    public function logout(): void
    {
        $this->ensureSession();
        
        // Clear all session data
        $_SESSION = [];
        
        // Destroy session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        // Destroy session
        session_destroy();
    }
    
    /**
     * Check if user is logged in
     */
    public function isLoggedIn(): bool
    {
        $this->ensureSession();
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
    
    /**
     * Get current logged-in user
     */
    public function getCurrentUser(): ?User
    {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        $userId = $_SESSION['user_id'] ?? null;
        if (!$userId) {
            return null;
        }
        
        return User::find($userId);
    }
    
    /**
     * Get current user ID
     */
    public function getCurrentUserId(): ?int
    {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        return $_SESSION['user_id'] ?? null;
    }
    
    /**
     * Get current user type
     */
    public function getCurrentUserType(): ?string
    {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        return $_SESSION['user_type'] ?? null;
    }
    
    /**
     * Check if current user is admin
     */
    public function isAdmin(): bool
    {
        return $this->getCurrentUserType() === User::TYPE_ADMIN;
    }
    
    /**
     * Check if current user is staff
     */
    public function isStaff(): bool
    {
        $userType = $this->getCurrentUserType();
        return in_array($userType, [User::TYPE_STAFF, User::TYPE_MANAGER, User::TYPE_ADMIN]);
    }
    
    /**
     * Check if current user is customer
     */
    public function isCustomer(): bool
    {
        return $this->getCurrentUserType() === User::TYPE_CUSTOMER;
    }
    
    /**
     * Get current customer profile
     */
    public function getCurrentCustomer(): ?Customer
    {
        if (!$this->isCustomer()) {
            return null;
        }
        
        $userId = $this->getCurrentUserId();
        if (!$userId) {
            return null;
        }
        
        return Customer::findByUserId($userId);
    }
    
    /**
     * Require authentication - redirect if not logged in
     */
    public function requireAuth(string $redirectUrl = '/admin/login.php'): void
    {
        if (!$this->isLoggedIn()) {
            header("Location: $redirectUrl");
            exit;
        }
    }
    
    /**
     * Require admin access - redirect if not admin
     */
    public function requireAdmin(string $redirectUrl = '/admin/login.php'): void
    {
        $this->requireAuth($redirectUrl);
        
        if (!$this->isAdmin()) {
            header("Location: $redirectUrl");
            exit;
        }
    }
    
    /**
     * Require staff access - redirect if not staff
     */
    public function requireStaff(string $redirectUrl = '/admin/login.php'): void
    {
        $this->requireAuth($redirectUrl);
        
        if (!$this->isStaff()) {
            header("Location: $redirectUrl");
            exit;
        }
    }
    
    /**
     * Change password for current user
     */
    public function changePassword(string $currentPassword, string $newPassword): void
    {
        $user = $this->getCurrentUser();
        if (!$user) {
            throw new AuthException('User not logged in');
        }
        
        // Verify current password
        if (!$user->verifyPassword($currentPassword)) {
            throw new AuthException('Current password is incorrect');
        }
        
        // Validate new password
        if (strlen($newPassword) < 8) {
            throw new ValidationException('New password must be at least 8 characters');
        }
        
        // Update password
        $user->setPassword($newPassword);
        $user->save();
    }
    
    /**
     * Update user profile
     */
    public function updateProfile(array $data): User
    {
        $user = $this->getCurrentUser();
        if (!$user) {
            throw new AuthException('User not logged in');
        }
        
        // Remove sensitive fields that shouldn't be updated this way
        unset($data['password'], $data['id'], $data['user_type']);
        
        $user->fill($data);
        $user->save();
        
        return $user;
    }
    
    /**
     * Generate password reset token (simple implementation)
     */
    public function generatePasswordResetToken(string $email): string
    {
        $user = User::findByEmail($email);
        if (!$user) {
            throw new AuthException('Email not found');
        }
        
        // Generate simple token (in production, use more secure method)
        $token = bin2hex(random_bytes(32));
        
        // Store token in session for simplicity (in production, use database)
        $this->ensureSession();
        $_SESSION['password_reset_token'] = $token;
        $_SESSION['password_reset_email'] = $email;
        $_SESSION['password_reset_expires'] = time() + 3600; // 1 hour
        
        return $token;
    }
    
    /**
     * Reset password with token
     */
    public function resetPassword(string $token, string $newPassword): void
    {
        $this->ensureSession();
        
        // Verify token
        if (!isset($_SESSION['password_reset_token']) || 
            $_SESSION['password_reset_token'] !== $token) {
            throw new AuthException('Invalid reset token');
        }
        
        // Check expiration
        if (time() > $_SESSION['password_reset_expires']) {
            throw new AuthException('Reset token has expired');
        }
        
        // Find user
        $email = $_SESSION['password_reset_email'];
        $user = User::findByEmail($email);
        if (!$user) {
            throw new AuthException('User not found');
        }
        
        // Update password
        $user->setPassword($newPassword);
        $user->save();
        
        // Clear reset token
        unset($_SESSION['password_reset_token']);
        unset($_SESSION['password_reset_email']);
        unset($_SESSION['password_reset_expires']);
    }
    
    /**
     * Get user session data
     */
    public function getSessionData(): array
    {
        $this->ensureSession();
        
        if (!$this->isLoggedIn()) {
            return [];
        }
        
        return [
            'user_id' => $_SESSION['user_id'] ?? null,
            'username' => $_SESSION['username'] ?? null,
            'user_type' => $_SESSION['user_type'] ?? null,
            'logged_in' => $_SESSION['logged_in'] ?? false
        ];
    }
}