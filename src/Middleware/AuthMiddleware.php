<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\Database;
use App\Core\Request;
use App\Core\Response;

readonly class AuthMiddleware
{
    public static function requireAuth(Request $request, Database $db): int
    {
        if (!$request->token) {
            Response::unauthorized();
        }

        $stmt = $db->query('SELECT id, email, first_name, last_name FROM users WHERE token = ?', [$request->token]);
        $user = $stmt->fetch();

        if (!$user) {
            Response::unauthorized();
        }

        return (int)$user['id'];
    }

    public static function requireOwner(Request $request, Database $db, string $fileId, int $userId): array
    {
        $stmt = $db->query('SELECT * FROM files WHERE file_id = ? AND user_id = ?', [$fileId, $userId]);
        $file = $stmt->fetch();

        if (!$file) {
            $stmtCheck = $db->query('SELECT id FROM files WHERE file_id = ?', [$fileId]);
            if (!$stmtCheck->fetch()) {
                Response::notFound();
            }
            Response::forbidden();
        }

        return $file;
    }
}