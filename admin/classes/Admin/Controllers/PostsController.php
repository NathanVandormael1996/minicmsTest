<?php
declare(strict_types=1);

namespace Admin\Controllers;

use Admin\Core\Flash;
use Admin\Core\View;
use Admin\Repositories\MediaRepository;
use Admin\Repositories\PostsRepository;
use Admin\Services\SlugService;

final class PostsController
{
    private PostsRepository $posts;
    private SlugService $slugService;

    public function __construct(PostsRepository $posts, SlugService $slugService)
    {
        $this->posts = $posts;
        $this->slugService = $slugService;
    }

    public function index(): void
    {
        View::render('posts.php', [
            'title' => 'Posts',
            'posts' => $this->posts->getAll(),
        ]);
    }

    public function create(): void
    {
        $old = Flash::get('old') ?: ['title' => '', 'content' => '', 'status' => 'draft', 'featured_media_id' => ''];
        View::render('post-create.php', [
            'title' => 'Nieuwe post',
            'old' => $old,
            'media' => MediaRepository::make()->getAllImages(),
        ]);
    }

    public function store(): void
    {
        $title = trim((string)($_POST['title'] ?? ''));
        $slug = $this->slugService->generateSlug($title);
        $content = trim((string)($_POST['content'] ?? ''));
        $status = (string)($_POST['status'] ?? 'draft');
        $featuredId = $this->normalizeFeaturedId((string)($_POST['featured_media_id'] ?? ''));

        $errors = $this->validateCreate($title, $slug, $content, $status, $featuredId);

        if (!empty($errors)) {
            Flash::set('warning', $errors);
            Flash::set('old', compact('title', 'content', 'status') + ['featured_media_id' => $_POST['featured_media_id']]);
            header('Location: ' . ADMIN_BASE_PATH . '/posts/create');
            exit;
        }

        $this->posts->create($title, $slug, $content, $status, $featuredId);
        Flash::set('success', 'Post succesvol aangemaakt.');
        header('Location: ' . ADMIN_BASE_PATH . '/posts');
        exit;
    }

    public function edit(int $id): void
    {
        $currentUserId = (int)$_SESSION['user_id'];
        $lock = $this->posts->getLock($id);

        if ($lock && (int)$lock['locked_by'] !== $currentUserId) {
            Flash::set('warning', "Post is gelockt door {$lock['admin_name']}.");
            header('Location: ' . ADMIN_BASE_PATH . '/posts');
            exit;
        }

        if ($lock && (int)$lock['locked_by'] === $currentUserId) {
            $this->posts->unlock($id);
            Flash::set('info', "Je lock is vrijgegeven door de pagina te herladen.");
            header('Location: ' . ADMIN_BASE_PATH . '/posts');
            exit;
        }

        $this->posts->lock($id, $currentUserId);

        $post = $this->posts->find($id);
        if (!$post) {
            Flash::set('error', 'Post niet gevonden.');
            header('Location: ' . ADMIN_BASE_PATH . '/posts');
            exit;
        }

        $revisions = $this->posts->getRevisions($id);

        $old = Flash::get('old') ?: [
            'title' => (string)$post['title'],
            'content' => (string)$post['content'],
            'status' => (string)$post['status'],
            'featured_media_id' => (string)($post['featured_media_id'] ?? ''),
            'meta_title' => (string)($post['meta_title'] ?? ''),
            'meta_description' => (string)($post['meta_description'] ?? ''),
            'published_at' => (string)($post['published_at'] ?? ''),
        ];

        View::render('post-edit.php', [
            'title' => 'Post bewerken',
            'postId' => $id,
            'post' => $post,
            'old' => $old,
            'revisions' => $revisions,
            'media' => MediaRepository::make()->getAllImages(),
        ]);
    }

    public function cancelEdit(int $id): void
    {
        $currentUserId = (int)$_SESSION['user_id'];
        $lock = $this->posts->getLock($id);

        if ($lock && (int)$lock['locked_by'] === $currentUserId) {
            $this->posts->unlock($id);
        }

        header('Location: ' . ADMIN_BASE_PATH . '/posts');
        exit;
    }

    public function update(int $id): void
    {
        $currentUserId = (int)$_SESSION['user_id'];
        $lock = $this->posts->getLock($id);

        // 1. Lock check
        if ($lock && (int)$lock['locked_by'] !== $currentUserId) {
            Flash::set('error', 'Opslaan mislukt: je lock is verlopen of de post is overgenomen.');
            header('Location: ' . ADMIN_BASE_PATH . '/posts');
            exit;
        }

        $oldPost = $this->posts->find($id);
        if (!$oldPost) {
            Flash::set('error', 'Post niet gevonden.');
            header('Location: ' . ADMIN_BASE_PATH . '/posts');
            exit;
        }

        $title = trim((string)($_POST['title'] ?? ''));
        $content = trim((string)($_POST['content'] ?? ''));
        $status = (string)($_POST['status'] ?? 'draft');
        $featuredId = $this->normalizeFeaturedId((string)($_POST['featured_media_id'] ?? ''));

        $publishedAt = !empty($_POST['published_at']) ? $_POST['published_at'] : null;
        $metaTitle = trim((string)($_POST['meta_title'] ?? ''));
        $metaDescription = trim((string)($_POST['meta_description'] ?? ''));

        $errors = $this->validateBase($title, $content, $status, $featuredId);
        if (!empty($errors)) {
            Flash::set('warning', $errors);
            header('Location: ' . ADMIN_BASE_PATH . '/posts/' . $id . '/edit');
            exit;
        }

        $this->posts->saveRevision($id, $oldPost);

        $this->posts->update(
            $id,
            (string)$oldPost['slug'],
            $title,
            $content,
            $status,
            $featuredId,
            $publishedAt,
            $metaTitle,
            $metaDescription
        );
        $this->posts->unlock($id);

        Flash::set('success', 'Post succesvol aangepast, revisie opgeslagen en lock vrijgegeven.');
        header('Location: ' . ADMIN_BASE_PATH . '/posts');
        exit;
    }

    public function deleteConfirm(int $id): void
    {
        $post = $this->posts->find($id);
        if (!$post) {
            Flash::set('error', 'Post niet gevonden.');
            header('Location: ' . ADMIN_BASE_PATH . '/posts');
            exit;
        }

        View::render('post-delete.php', ['title' => 'Post verwijderen', 'post' => $post]);
    }

    public function destroy(int $id): void
    {
        $this->posts->delete($id);
        Flash::set('success', 'Post verplaatst naar prullenbak.');
        header('Location: ' . ADMIN_BASE_PATH . '/posts');
        exit;
    }

    public function restore(int $id): void
    {
        $this->posts->restore($id);
        Flash::set('success', 'Post is succesvol hersteld.');
        header('Location: ' . ADMIN_BASE_PATH . '/posts');
        exit;
    }

    public function show(string $slug): void
    {
        $post = $this->posts->findBySlug($slug);
        if (!$post) {
            Flash::set('error', 'Post niet gevonden.');
            header('Location: ' . ADMIN_BASE_PATH . '/posts');
            exit;
        }
        View::render('post-show.php', ['title' => 'Post bekijken', 'post' => $post]);
    }

    private function normalizeFeaturedId(string $raw): ?int
    {
        return (ctype_digit($raw) && (int)$raw > 0) ? (int)$raw : null;
    }

    private function validateBase(string $title, string $content, string $status, ?int $featuredId): array
    {
        $errors = [];
        if (mb_strlen($title) < 3) $errors[] = 'Titel te kort.';
        if (mb_strlen($content) < 10) $errors[] = 'Inhoud te kort.';
        if (!in_array($status, ['draft', 'published'])) $errors[] = 'Ongeldige status.';
        return $errors;
    }

    private function validateCreate(string $title, string $slug, string $content, string $status, ?int $featuredId): array
    {
        $errors = $this->validateBase($title, $content, $status, $featuredId);
        if ($this->posts->exists($slug)) $errors[] = 'Slug bestaat al.';
        return $errors;
    }

    public function viewRevision(int $id, int $revisionId): void
    {
        $revision = $this->posts->findRevision($revisionId);

        if (!$revision) {
            Flash::set('error', 'Revisie niet gevonden.');
            header('Location: ' . ADMIN_BASE_PATH . '/posts/' . $id . '/edit');
            exit;
        }

        View::render('post-revision-view.php', [
            'title' => 'Revisie bekijken: ' . $revision['title'],
            'revision' => $revision,
            'postId' => $id
        ]);
    }

    public function restoreRevision(int $id, int $revId): void
    {
        $revision = $this->posts->findRevision($revId);

        if (!$revision) {
            Flash::set('error', 'Revisie niet gevonden.');
            header('Location: ' . ADMIN_BASE_PATH . '/posts/' . $id . '/edit');
            exit;
        }

        $currentPost = $this->posts->find($id);

        $this->posts->update(
            $id,
            (string)$currentPost['slug'],
            (string)$revision['title'],
            (string)$revision['content'],
            'draft',
            $currentPost['featured_media_id'],
            $currentPost['published_at'],
            $revision['meta_title'],
            $revision['meta_description']
        );

        Flash::set('success', 'Post succesvol hersteld naar de versie van ' . $revision['created_at']);
        header('Location: ' . ADMIN_BASE_PATH . '/posts/' . $id . '/edit');
        exit;
    }
}