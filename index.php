<?php
declare(strict_types=1);

require_once __DIR__ . DIRECTORY_SEPARATOR . 'config.php';

spl_autoload_register(function (string $class) {
    $prefix = 'App\\';
    $baseDir = __DIR__ . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR;

    if (!str_starts_with($class, $prefix)) return;

    $relativeClass = substr($class, strlen($prefix));
    $file = $baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';

    if (file_exists($file)) require $file;
});

use App\Core\Request;
use App\Core\Database;
use App\Core\Router;
use App\Controllers\AuthController;
use App\Controllers\FileController;
use App\Middleware\AuthMiddleware;

$request = new Request();
$db = new Database();
$router = new Router($request, $db);

$authController = new AuthController($db);
$fileController = new FileController($db);

$router->add('POST', '/api-file/registration', fn($req) => $authController->register($req));
$router->add('POST', '/api-file/authorization', fn($req) => $authController->login($req));

$protected = function(callable $handler) use ($request, $db) {
    return function($req, $_db, $params) use ($handler, $request, $db) {
        $userId = AuthMiddleware::requireAuth($request, $db);
        $handler($req, $userId, $params);
    };
};

$router->add('GET', '/api-file/logout', $protected(fn($req, $userId) => $authController->logout($req, $userId)));
$router->add('POST', '/api-file/files', $protected(fn($req, $userId) => $fileController->upload($req, $userId)));
$router->add('GET', '/api-file/files/disk', $protected(fn($req, $userId) => $fileController->disk($req, $userId)));
$router->add('GET', '/api-file/files/shared', $protected(fn($req, $userId) => $fileController->shared($req, $userId)));
$router->add('PATCH', '/api-file/files/{file_id}', $protected(fn($req, $userId, $params) => $fileController->update($req, $userId, $params)));
$router->add('DELETE', '/api-file/files/{file_id}', $protected(fn($req, $userId, $params) => $fileController->delete($req, $userId, $params)));
$router->add('GET', '/api-file/files/{file_id}', $protected(fn($req, $userId, $params) => $fileController->download($req, $userId, $params)));
$router->add('POST', '/api-file/files/{file_id}/accesses', $protected(fn($req, $userId, $params) => $fileController->addAccess($req, $userId, $params)));
$router->add('DELETE', '/api-file/files/{file_id}/accesses', $protected(fn($req, $userId, $params) => $fileController->removeAccess($req, $userId, $params)));

$router->dispatch();