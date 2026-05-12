<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Response;

readonly class AuthService
{
    public function __construct(private Database $db) {}

    public function register(array $data): string
    {
        $errors = [];
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'][] = 'Invalid or missing email';
        }
        if (empty($data['password']) || !preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{3,}$/', $data['password'])) {
            $errors['password'][] = 'Password must be at least 3 chars, with lowercase, uppercase and digit';
        }
        if (empty($data['first_name']) || strlen($data['first_name']) < 2) {
            $errors['first_name'][] = 'First name must be at least 2 characters';
        }
        if (empty($data['last_name'])) {
            $errors['last_name'][] = 'Last name is required';
        }

        if ($errors) {
            Response::validationError($errors);
        }

        $exists = $this->db->query('SELECT id FROM users WHERE email = ?', [$data['email']])->fetch();
        if ($exists) {
            $errors['email'][] = 'Email already exists';
            Response::validationError($errors);
        }

        $token = bin2hex(random_bytes(32));
        $hash = password_hash($data['password'], PASSWORD_BCRYPT);

        $this->db->query(
            'INSERT INTO users (email, password_hash, first_name, last_name, token) VALUES (?, ?, ?, ?, ?)',
            [$data['email'], $hash, $data['first_name'], $data['last_name'], $token]
        );

        return $token;
    }

    public function login(array $data): string
    {
        $errors = [];
        if (empty($data['email'])) $errors['email'][] = 'Email is required';
        if (empty($data['password'])) $errors['password'][] = 'Password is required';

        if ($errors) Response::validationError($errors);

        $stmt = $this->db->query('SELECT id, password_hash FROM users WHERE email = ?', [$data['email']]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($data['password'], $user['password_hash'])) {
            Response::json(['success' => false, 'code' => 401, 'message' => 'Authorization failed'], 401);
        }

        $token = bin2hex(random_bytes(32));
        $this->db->query('UPDATE users SET token = ? WHERE id = ?', [$token, $user['id']]);

        return $token;
    }

    public function logout(int $userId): void
    {
        $this->db->query('UPDATE users SET token = NULL WHERE id = ?', [$userId]);
    }
}