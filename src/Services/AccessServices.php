<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Response;

readonly class AccessService
{
    public function __construct(private Database $db) {}

    public function add(string $fileId, int $ownerId, string $email): array
    {
        $user = $this->db->query('SELECT id, first_name, last_name, email FROM users WHERE email = ?', [$email])->fetch();
        if (!$user) {
            Response::notFound();
        }

        $exists = $this->db->query('SELECT id FROM file_access WHERE file_id = ? AND user_id = ?', [$fileId, $user['id']])->fetch();
        if (!$exists) {
            $this->db->query('INSERT INTO file_access (file_id, user_id, type) VALUES (?, ?, ?)', [$fileId, $user['id'], 'co-author']);
        }

        return $this->getAccessList($fileId);
    }

    public function remove(string $fileId, int $ownerId, string $email): array
    {
        $user = $this->db->query('SELECT id FROM users WHERE email = ?', [$email])->fetch();
        if (!$user) {
            Response::notFound();
        }

        if ($user['id'] === $ownerId) {
            Response::forbidden();
        }

        $access = $this->db->query('SELECT id FROM file_access WHERE file_id = ? AND user_id = ? AND type = ?', [$fileId, $user['id'], 'co-author'])->fetch();
        if (!$access) {
            Response::notFound();
        }

        $this->db->query('DELETE FROM file_access WHERE file_id = ? AND user_id = ?', [$fileId, $user['id']]);

        return $this->getAccessList($fileId);
    }

    private function getAccessList(string $fileId): array
    {
        $stmt = $this->db->query(
            'SELECT u.first_name, u.last_name, u.email, fa.type
             FROM file_access fa
             JOIN users u ON fa.user_id = u.id
             WHERE fa.file_id = ?',
            [$fileId]
        );

        $list = [];
        while ($row = $stmt->fetch()) {
            $list[] = [
                'fullname' => $row['first_name'] . ' ' . $row['last_name'],
                'email' => $row['email'],
                'type' => $row['type'],
                'code' => 200
            ];
        }

        return $list;
    }
}