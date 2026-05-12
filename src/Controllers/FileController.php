<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Response;
use App\Core\Database;
use App\Services\FileService;
use App\Services\AccessService;
use App\Middleware\AuthMiddleware;

readonly class FileController
{
    public function __construct(private Database $db) {}

    public function upload(Request $request, int $userId): void
    {
        $service = new FileService($this->db);
        $result = $service->upload($request->files, $userId);
        Response::json($result, 200);
    }

    public function update(Request $request, int $userId, array $params): void
    {
        $fileId = $params['file_id'];
        AuthMiddleware::requireOwner($request, $this->db, $fileId, $userId);
        $service = new FileService($this->db);
        $service->update($fileId, $userId, $request->getParam('name', ''));
        Response::json(['success' => true, 'code' => 200, 'message' => 'Renamed'], 200);
    }

    public function delete(Request $request, int $userId, array $params): void
    {
        $fileId = $params['file_id'];
        AuthMiddleware::requireOwner($request, $this->db, $fileId, $userId);
        $service = new FileService($this->db);
        $service->delete($fileId, $userId);
        Response::json(['success' => true, 'code' => 200, 'message' => 'File deleted'], 200);
    }

    public function download(Request $request, int $userId, array $params): void
    {
        $fileId = $params['file_id'];
        $service = new FileService($this->db);
        $file = $service->download($fileId, $userId);
        Response::file($file['path'], $file['name']);
    }

    public function disk(Request $request, int $userId): void
    {
        $service = new FileService($this->db);
        $files = $service->disk($userId);
        Response::json($files, 200);
    }

    public function shared(Request $request, int $userId): void
    {
        $service = new FileService($this->db);
        $files = $service->shared($userId);
        Response::json($files, 200);
    }

    public function addAccess(Request $request, int $userId, array $params): void
    {
        $fileId = $params['file_id'];
        AuthMiddleware::requireOwner($request, $this->db, $fileId, $userId);
        $service = new AccessService($this->db);
        $list = $service->add($fileId, $userId, $request->getParam('email', ''));
        Response::json($list, 200);
    }

    public function removeAccess(Request $request, int $userId, array $params): void
    {
        $fileId = $params['file_id'];
        AuthMiddleware::requireOwner($request, $this->db, $fileId, $userId);
        $service = new AccessService($this->db);
        $list = $service->remove($fileId, $userId, $request->getParam('email', ''));
        Response::json($list, 200);
    }
}