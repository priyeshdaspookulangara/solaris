<?php
// includes/Auth.php
require_once __DIR__ . '/../config/database.php';

class Auth {
    public static function startSession(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function user(): ?array {
        self::startSession();
        if (!isset($_SESSION['user_id'])) {
            return null;
        }
        $pdo = Database::getConnection();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        if (!$user) {
            self::logout();
            return null;
        }
        return $user;
    }

    public static function check(): bool {
        return self::user() !== null;
    }

    public static function isAdmin(): bool {
        $user = self::user();
        return $user && ($user['role'] === 'admin');
    }

    public static function requireLogin(): void {
        if (!self::check()) {
            header('Location: /login.php');
            exit;
        }
    }

    public static function requireAdmin(): void {
        self::requireLogin();
        if (!self::isAdmin()) {
            header('Location: /dashboard.php');
            exit;
        }
    }

    public static function register(string $username, string $email, string $password, ?string $referralCode = null): array {
        $pdo = Database::getConnection();

        $username = trim($username);
        $email = trim(strtolower($email));

        if (empty($username) || empty($email) || empty($password)) {
            return ['success' => false, 'error' => 'All fields are required.'];
        }

        if (strlen($password) < 6) {
            return ['success' => false, 'error' => 'Password must be at least 6 characters.'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'error' => 'Invalid email address format.'];
        }

        // Check if user or email exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            return ['success' => false, 'error' => 'Username or email already exists.'];
        }

        // Handle referral
        $referredById = null;
        if (!empty($referralCode)) {
            $stmtRef = $pdo->prepare("SELECT id FROM users WHERE referral_code = ?");
            $stmtRef->execute([trim($referralCode)]);
            $referrer = $stmtRef->fetch();
            if ($referrer) {
                $referredById = $referrer['id'];
            }
        }

        $myReferralCode = strtoupper(substr(preg_replace('/[^a-zA-Z0-9]/', '', $username), 0, 4) . rand(1000, 9999));
        $hash = password_hash($password, PASSWORD_BCRYPT);

        $stmtInsert = $pdo->prepare("INSERT INTO users (username, email, password_hash, referral_code, referred_by_id, wallet_balance) VALUES (?, ?, ?, ?, ?, 20.00)");
        // Give new users $20.00 initial free deposit/bonus so they can immediately test investing 2x Solaria 40 units!
        $stmtInsert->execute([$username, $email, $hash, $myReferralCode, $referredById]);
        $newId = $pdo->lastInsertId();

        // Log registration bonus transaction
        $stmtTx = $pdo->prepare("INSERT INTO transactions (user_id, type, amount, balance_after, description) VALUES (?, 'deposit', 20.00, 20.00, 'Welcome testing balance credited')");
        $stmtTx->execute([$newId]);

        return ['success' => true, 'user_id' => $newId];
    }

    public static function login(string $usernameOrEmail, string $password): array {
        $pdo = Database::getConnection();
        $input = trim($usernameOrEmail);

        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$input, $input]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return ['success' => false, 'error' => 'Invalid username/email or password.'];
        }

        self::startSession();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        return ['success' => true, 'user' => $user];
    }

    public static function logout(): void {
        self::startSession();
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
    }
}
