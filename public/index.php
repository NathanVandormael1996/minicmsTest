<?php
declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');

/*
|--------------------------------------------------------------------------
| Public Front Controller
|--------------------------------------------------------------------------
*/

require_once __DIR__ . '/../admin/autoload.php';

use Admin\Core\Database;
use Admin\Repositories\PostsRepository;

// 1) Alleen het pad uit de URL halen
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';

// 2) Trailing slash verwijderen zodat /posts/hoi/ ook werkt
$uri = rtrim($uri, '/') ?: '/';

// 3) Database connectie en Repository
$pdo = Database::getConnection();
$postsRepository = new PostsRepository($pdo);

// 4) Routing
switch ($uri) {

    case '/':
        // Haalt de 5 nieuwste gepubliceerde posts op
        $posts = $postsRepository->getPublishedLatest(5);
        require __DIR__ . '/views/posts/home.php';
        break;

    case '/posts':
        // Fix: Gebruik getPublishedLatest() omdat getPublishedAll() niet bestaat
        $posts = $postsRepository->getPublishedLatest(20);
        require __DIR__ . '/views/posts/index.php';
        break;

    default:
        /**
         * DE FIX VOOR DE 404:
         * We veranderen (\d+) naar ([^/]+) om tekst-slugs zoals 'hoi' te vangen.
         * De i-vlag maakt de match ongevoelig voor hoofdletters.
         */
        if (preg_match('#^/posts/([^/]+)$#i', $uri, $matches)) {
            $postSlug = $matches[1];

            // Zoek de post op basis van de slug-kolom in de database
            $post = $postsRepository->findPublishedBySlug($postSlug);

            if (!$post) {
                http_response_code(404);
                echo "404 - Post met slug '{$postSlug}' niet gevonden.";
                exit;
            }

            require __DIR__ . '/views/posts/show.php';
            break;
        }

        // Als geen enkele route matcht
        http_response_code(404);
        echo "404 - Pagina niet gevonden voor: " . htmlspecialchars($uri);
        break;
}