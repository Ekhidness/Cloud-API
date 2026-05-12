<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use App\Services\AuthService;

readonly class AuthController
{
    public function __construct(private Database $db) {}

    public function register(Request $request): void
    {
        $service = new AuthService($this->db);
        $token = $service->register($request->body);

        Response::json([
            'success' => true,
            'code' => 201,
            'message' => 'Success',
            'token' => $token
        ], 201);
    }

    public function login(Request $request): void
    {
        $service = new AuthService($this->db);
        $token = $service->login($request->body);

        Response::json([
            'success' => true,
            'code' => 200,
            'message' => 'Success',
            'token' => $token
        ], 200);
    }

    public function logout(Request $request, int $userId): void
    {
        $service = new AuthService($this->db);
        $service->logout($userId);
        Response::json([], 204);
    }
}