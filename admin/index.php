<?php
declare(strict_types=1);

session_start();

require __DIR__ . '/config/app.php';
require __DIR__ . '/autoload.php';

use Admin\Controllers\AuthController;
use Admin\Controllers\DashboardController;
use Admin\Controllers\ErrorController;
use Admin\Controllers\MediaController;
use Admin\Controllers\PostsController;
use Admin\Controllers\RolesController;
use Admin\Controllers\UsersController;
use Admin\Core\Auth;
use Admin\Core\Router;
use Admin\Models\StatsModel;
use Admin\Repositories\MediaRepository;
use Admin\Repositories\PostsRepository;
use Admin\Repositories\RolesRepository;
use Admin\Repositories\UsersRepository;
use Admin\Services\SlugService;

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';

if (str_starts_with($uri, ADMIN_BASE_PATH)) {
    $uri = substr($uri, strlen(ADMIN_BASE_PATH));
}

$uri = rtrim($uri, '/') ?: '/';

/**
 * Exercise 2: OAuth Public Routes
 * Added the specific callback routes to allow the external provider to return data.
 */
$publicRoutes = [
    '/login',
    '/login/github',
    '/login/google',
    '/login/callback/github',
    '/login/callback/google'
];

if (!Auth::check() && !in_array($uri, $publicRoutes, true)) {
    header('Location: ' . ADMIN_BASE_PATH . '/login');
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$router = new Router();
$errorController = new ErrorController();

$router->setNotFoundHandler(function (string $requestedUri) use ($errorController): void {
    $errorController->notFound($requestedUri);
});

$requireAdmin = function () use ($errorController): void {
    if (!Auth::isAdmin()) {
        $errorController->forbidden('Admin rechten vereist.');
        exit;
    }
};

/**
 * Dashboard
 */
$router->get('/', function (): void {
    (new DashboardController(new StatsModel()))->index();
});

/**
 Auth
 **/
$router->get('/login', function (): void {
    (new AuthController(UsersRepository::make()))->showLogin();
});

$router->get('/login/{provider}', function (string $provider): void {
    (new AuthController(UsersRepository::make()))->redirectToProvider($provider);
});

$router->get('/login/callback/{provider}', function (string $provider): void {
    (new AuthController(UsersRepository::make()))->handleProviderCallback($provider);
});

$router->post('/login', function (): void {
    (new AuthController(UsersRepository::make()))->login();
});

$router->post('/logout', function (): void {
    (new AuthController(UsersRepository::make()))->logout();
});

/**
 * Users (admin-only)
 */
$router->get('/users', function () use ($requireAdmin): void {
    $requireAdmin();
    (new UsersController(UsersRepository::make(), RolesRepository::make()))->index();
});

$router->get('/users/create', function () use ($requireAdmin): void {
    $requireAdmin();
    (new UsersController(UsersRepository::make(), RolesRepository::make()))->create();
});

$router->post('/users/store', function () use ($requireAdmin): void {
    $requireAdmin();
    (new UsersController(UsersRepository::make(), RolesRepository::make()))->store();
});

$router->get('/users/{id}/edit', function (int $id) use ($requireAdmin): void {
    $requireAdmin();
    (new UsersController(UsersRepository::make(), RolesRepository::make()))->edit($id);
});

$router->post('/users/{id}/update', function (int $id) use ($requireAdmin): void {
    $requireAdmin();
    (new UsersController(UsersRepository::make(), RolesRepository::make()))->update($id);
});

/**
 Roles
 **/
$router->get('/roles', function (): void {
    (new RolesController(RolesRepository::make()))->index();
});
$router->get('/roles/create', function (): void {
    (new RolesController(RolesRepository::make()))->create();
});
$router->post('/roles/store', function (): void {
    (new RolesController(RolesRepository::make()))->store();
});

/**
Posts
 **/

$router->get('/posts', function (): void {
    (new PostsController(PostsRepository::make(), new SlugService()))->index();
});

$router->get('/posts/create', function (): void {
    (new PostsController(PostsRepository::make(), new SlugService()))->create();
});

$router->post('/posts/store', function (): void {
    (new PostsController(PostsRepository::make(), new SlugService()))->store();
});

$router->get('/posts/view/{slug}', function (string $slug): void {
    (new PostsController(PostsRepository::make(), new SlugService()))->show($slug);
});

$router->get('/posts/{id}/edit', function (int $id): void {
    (new PostsController(PostsRepository::make(), new SlugService()))->edit($id);
});

$router->get('/posts/{id}/cancel', function (int $id): void {
    (new PostsController(PostsRepository::make(), new SlugService()))->cancelEdit($id);
});

$router->post('/posts/{id}/update', function (int $id): void {
    (new PostsController(PostsRepository::make(), new SlugService()))->update($id);
});

$router->get('/posts/{id}/delete', function (int $id) use ($requireAdmin): void {
    $requireAdmin();
    (new PostsController(PostsRepository::make(), new SlugService()))->deleteConfirm($id);
});

$router->post('/posts/{id}/delete', function (int $id) use ($requireAdmin): void {
    $requireAdmin();
    (new PostsController(PostsRepository::make(), new SlugService()))->destroy($id);
});

$router->post('/posts/{id}/restore', function (int $id) use ($requireAdmin): void {
    $requireAdmin();
    (new PostsController(PostsRepository::make(), new SlugService()))->restore($id);
});

$router->post('/posts/{id}/revisions/{revId}/restore', function (int $id, int $revId): void {
    (new PostsController(PostsRepository::make(), new SlugService()))->restoreRevision($id, $revId);
});

$router->get('/posts/{id}/revisions/{revId}', function (int $id, int $revId): void {
    (new PostsController(PostsRepository::make(), new SlugService()))->viewRevision($id, $revId);
});

$router->get('/posts/{slug}', function (string $slug): void {
    (new PostsController(PostsRepository::make(), new SlugService()))->show($slug);
});

/**
 Media
 **/
$router->get('/media', function () use ($requireAdmin): void {
    $requireAdmin();
    (new MediaController(MediaRepository::make()))->index();
});

$router->get('/media/upload', function () use ($requireAdmin): void {
    $requireAdmin();
    (new MediaController(MediaRepository::make()))->uploadForm();
});

$router->post('/media/store', function () use ($requireAdmin): void {
    $requireAdmin();
    (new MediaController(MediaRepository::make()))->store();
});

$router->post('/media/{id}/delete', function (int $id) use ($requireAdmin): void {
    $requireAdmin();
    (new MediaController(MediaRepository::make()))->delete($id);
});

$router->dispatch($uri, $method);