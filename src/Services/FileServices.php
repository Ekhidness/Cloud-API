<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Database;
use App\Core\Response;

readonly class FileService
{
    public function __construct(private Database $db) {}

    public function upload(array $files, int $userId): array
    {
        $results = [];
        $normalized = $this->normalizeFiles($files);
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $baseUrl = $protocol . '://' . $_SERVER['HTTP_HOST'] . '/api-file/files/';

        foreach ($normalized as $file) {
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $results[] = ['success' => false, 'message' => ['Upload error'], 'name' => $file['name']];
                continue;
            }

            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ALLOWED_EXTENSIONS, true)) {
                $results[] = ['success' => false, 'message' => ['Invalid file type'], 'name' => $file['name']];
                continue;
            }

            if ($file['size'] > MAX_FILE_SIZE) {
                $results[] = ['success' => false, 'message' => ['File too large'], 'name' => $file['name']];
                continue;
            }

            $baseName = pathinfo($file['name'], PATHINFO_FILENAME);
            $finalName = $this->getUniqueName($baseName, $ext, $userId);
            $storedName = bin2hex(random_bytes(16)) . '.' . $ext;
            $fileId = substr(bin2hex(random_bytes(5)), 0, 10);
            $destination = UPLOAD_DIR . $storedName;

            if (!move_uploaded_file($file['tmp_name'], $destination)) {
                $results[] = ['success' => false, 'message' => ['Failed to save file'], 'name' => $file['name']];
                continue;
            }

            $this->db->query(
                'INSERT INTO files (file_id, user_id, original_name, stored_name, mime_type, size) VALUES (?, ?, ?, ?, ?, ?)',
                [$fileId, $userId, $finalName, $storedName, $file['type'], $file['size']]
            );
            $this->db->query(
                'INSERT INTO file_access (file_id, user_id, type) VALUES (?, ?, ?)',
                [$fileId, $userId, 'author']
            );

            $results[] = [
                'success' => true,
                'code' => 200,
                'message' => 'Success',
                'name' => $finalName,
                'url' => $baseUrl . $fileId,
                'file_id' => $fileId
            ];
        }

        return $results;
    }

    public function update(string $fileId, int $userId, string $newName): void
    {
        if (empty(trim($newName))) {
            Response::validationError(['name' => ['Name cannot be empty']]);
        }

        $ext = pathinfo($newName, PATHINFO_EXTENSION);
        $base = $ext ? pathinfo($newName, PATHINFO_FILENAME) : $newName;
        $fullNewName = $ext ? $base . '.' . $ext : $base;

        $exists = $this->db->query(
            'SELECT id FROM files WHERE user_id = ? AND original_name = ? AND file_id != ?',
            [$userId, $fullNewName, $fileId]
        )->fetch();

        if ($exists) {
            Response::validationError(['name' => ['Name already exists']]);
        }

        $this->db->query('UPDATE files SET original_name = ? WHERE file_id = ? AND user_id = ?', [$fullNewName, $fileId, $userId]);
    }

    public function delete(string $fileId, int $userId): void
    {
        $file = $this->db->query('SELECT stored_name FROM files WHERE file_id = ? AND user_id = ?', [$fileId, $userId])->fetch();
        if (!$file) {
            $check = $this->db->query('SELECT id FROM files WHERE file_id = ?', [$fileId])->fetch();
            $check ? Response::forbidden() : Response::notFound();
        }

        $path = UPLOAD_DIR . $file['stored_name'];
        if (file_exists($path)) unlink($path);

        $this->db->query('DELETE FROM file_access WHERE file_id = ?', [$fileId]);
        $this->db->query('DELETE FROM files WHERE file_id = ?', [$fileId]);
    }

    public function download(string $fileId, int $userId): array
    {
        $access = $this->db->query(
            'SELECT f.stored_name, f.original_name FROM files f
             JOIN file_access fa ON f.file_id = fa.file_id
             WHERE f.file_id = ? AND fa.user_id = ?',
            [$fileId, $userId]
        )->fetch();

        if (!$access) {
            $check = $this->db->query('SELECT id FROM files WHERE file_id = ?', [$fileId])->fetch();
            $check ? Response::forbidden() : Response::notFound();
        }

        return ['path' => UPLOAD_DIR . $access['stored_name'], 'name' => $access['original_name']];
    }

    public function disk(int $userId): array
    {
        $stmt = $this->db->query(
            'SELECT f.file_id, f.original_name, fa.user_id, u.first_name, u.last_name, u.email, fa.type
             FROM files f
             JOIN file_access fa ON f.file_id = fa.file_id
             JOIN users u ON fa.user_id = u.id
             WHERE f.user_id = ?
             ORDER BY f.id DESC',
            [$userId]
        );

        $files = [];
        while ($row = $stmt->fetch()) {
            $fid = $row['file_id'];
            if (!isset($files[$fid])) {
                $files[$fid] = [
                    'file_id' => $fid,
                    'name' => $row['original_name'],
                    'code' => 200,
                    'url' => '/api-file/files/' . $fid,
                    'accesses' => []
                ];
            }
            $files[$fid]['accesses'][] = [
                'fullname' => $row['first_name'] . ' ' . $row['last_name'],
                'email' => $row['email'],
                'type' => $row['type'],
                'code' => 200
            ];
        }

        return array_values($files);
    }

    public function shared(int $userId): array
    {
        $stmt = $this->db->query(
            'SELECT f.file_id, f.original_name
             FROM files f
             JOIN file_access fa ON f.file_id = fa.file_id
             WHERE fa.user_id = ? AND f.user_id != ?
             ORDER BY f.id DESC',
            [$userId, $userId]
        );

        $result = [];
        while ($row = $stmt->fetch()) {
            $result[] = [
                'file_id' => $row['file_id'],
                'code' => 200,
                'name' => $row['original_name'],
                'url' => '/api-file/files/' . $row['file_id']
            ];
        }

        return $result;
    }

    private function normalizeFiles(array $files): array
    {
        $normalized = [];
        if (!isset($files['files'])) return [];

        $fileArr = $files['files'];
        if (is_array($fileArr['name'])) {
            $count = count($fileArr['name']);
            for ($i = 0; $i < $count; $i++) {
                $normalized[] = [
                    'name' => $fileArr['name'][$i],
                    'type' => $fileArr['type'][$i],
                    'tmp_name' => $fileArr['tmp_name'][$i],
                    'error' => $fileArr['error'][$i],
                    'size' => $fileArr['size'][$i],
                ];
            }
        } else {
            $normalized[] = $fileArr;
        }

        return $normalized;
    }

    private function getUniqueName(string $base, string $ext, int $userId): string
    {
        $name = $base . '.' . $ext;
        $i = 1;
        while ($this->db->query('SELECT id FROM files WHERE user_id = ? AND original_name = ?', [$userId, $name])->fetch()) {
            $name = $base . ' (' . $i . ').' . $ext;
            $i++;
        }
        return $name;
    }
}