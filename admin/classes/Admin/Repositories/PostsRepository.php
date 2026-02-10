<?php
declare(strict_types=1);

namespace Admin\Repositories;

use Admin\Core\Database;
use PDO;

final class PostsRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public static function make(): self
    {
        return new self(Database::getConnection());
    }

    public function getAll(): array
    {
        $sql = "SELECT id, title, slug, status, deleted_at, published_at, locked_by 
            FROM posts 
            ORDER BY id DESC";

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function find(int $id): ?array
    {
        $sql = "SELECT * FROM posts WHERE id = :id AND deleted_at IS NULL LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }

    public function findBySlug(string $slug): ?array
    {
        $sql = "SELECT * FROM posts WHERE slug = :slug AND deleted_at IS NULL LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['slug' => $slug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }

    public function getPublishedLatest(int $limit = 6): array
    {
        $limit = max(1, min(50, $limit));

        $sql = "SELECT id, title, slug, content, status, featured_media_id, created_at, 
                       published_at, meta_title, meta_description
                FROM posts
                WHERE status = 'published' 
                AND deleted_at IS NULL 
                AND (published_at IS NULL OR published_at <= NOW())
                ORDER BY published_at DESC
                LIMIT " . (int)$limit;

        $stmt = $this->pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function findPublishedBySlug(string $slug): ?array
    {
        $sql = "SELECT * FROM posts 
                WHERE slug = :slug 
                AND status = 'published' 
                AND deleted_at IS NULL 
                AND (published_at IS NULL OR published_at <= NOW())
                LIMIT 1";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['slug' => $slug]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row !== false ? $row : null;
    }

    public function create(
        string $title,
        string $slug,
        string $content,
        string $status,
        ?int $featuredMediaId = null,
        ?string $publishedAt = null,
        ?string $metaTitle = null,
        ?string $metaDescription = null
    ): int {
        if ($status === 'published' && empty($publishedAt)) {
            $publishedAt = date('Y-m-d H:i:s');
        }

        $sql = "INSERT INTO posts (title, slug, content, status, featured_media_id, published_at, meta_title, meta_description, created_at)
                VALUES (:title, :slug, :content, :status, :featured_media_id, :published_at, :meta_title, :meta_description, NOW())";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'title' => $title,
            'slug' => $slug,
            'content' => $content,
            'status' => $status,
            'featured_media_id' => $featuredMediaId,
            'published_at' => $publishedAt,
            'meta_title' => $metaTitle,
            'meta_description' => $metaDescription
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function update(
        int $id,
        string $slug,
        string $title,
        string $content,
        string $status,
        ?int $featuredMediaId = null,
        ?string $publishedAt = null,
        ?string $metaTitle = null,
        ?string $metaDescription = null
    ): void {
        // Oefening 4: Automatisch NOW() als status naar 'published' gaat zonder datum.
        if ($status === 'published' && empty($publishedAt)) {
            $publishedAt = date('Y-m-d H:i:s');
        }

        $sql = "UPDATE posts
                SET title = :title,
                    slug = :slug,
                    content = :content,
                    status = :status,
                    featured_media_id = :featured_media_id,
                    published_at = :published_at,
                    meta_title = :meta_title,
                    meta_description = :meta_description
                WHERE id = :id AND deleted_at IS NULL";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id' => $id,
            'title' => $title,
            'slug' => $slug,
            'content' => $content,
            'status' => $status,
            'featured_media_id' => $featuredMediaId,
            'published_at' => $publishedAt,
            'meta_title' => $metaTitle,
            'meta_description' => $metaDescription
        ]);
    }

    public function delete(int $id): void
    {
        $sql = "UPDATE posts SET deleted_at = NOW() WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $id]);
    }

    public function restore(int $id): void
    {
        $sql = "UPDATE posts SET deleted_at = NULL WHERE id = :id";
        $this->pdo->prepare($sql)->execute(['id' => $id]);
    }

    public function exists(string $slug): bool
    {
        $sql = "SELECT COUNT(*) FROM posts WHERE slug = :slug AND deleted_at IS NULL";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['slug' => $slug]);

        return (int)$stmt->fetchColumn() > 0;
    }

    public function lock(int $postId, int $userId): void
    {
        $sql = "UPDATE posts SET locked_by = :user_id, locked_at = NOW() WHERE id = :post_id";
        $this->pdo->prepare($sql)->execute(['user_id' => $userId, 'post_id' => $postId]);
    }

    public function unlock(int $postId): void
    {
        $sql = "UPDATE posts SET locked_by = NULL, locked_at = NULL WHERE id = :post_id";
        $this->pdo->prepare($sql)->execute(['post_id' => $postId]);
    }

    public function getLock(int $postId): ?array
    {
        $sql = "SELECT p.locked_at, p.locked_by, u.name as admin_name 
                FROM posts p
                LEFT JOIN users u ON u.id = p.locked_by
                WHERE p.id = :id 
                AND p.locked_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $postId]);
        return $stmt->fetch() ?: null;
    }

    public function getRevisions(int $postId): array
    {
        $sql = "SELECT * FROM revisions WHERE post_id = :post_id ORDER BY created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['post_id' => $postId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function saveRevision(int $postId, array $data): void
    {
        $sql = "INSERT INTO revisions (post_id, title, content, meta_title, meta_description, created_at)
            VALUES (:post_id, :title, :content, :meta_title, :meta_description, NOW())";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'post_id'          => $postId,
            'title'            => $data['title'],
            'content'          => $data['content'],
            'meta_title'       => $data['meta_title'] ?? null,
            'meta_description' => $data['meta_description'] ?? null
        ]);

        $this->pdo->prepare("
        DELETE FROM revisions 
        WHERE post_id = :post_id 
        AND id NOT IN (
            SELECT id FROM (
                SELECT id FROM revisions 
                WHERE post_id = :post_id 
                ORDER BY created_at DESC 
                LIMIT 3
            ) AS tmp
        )
    ")->execute(['post_id' => $postId]);
    }

    public function findRevision(int $revisionId): ?array
    {
        $sql = "SELECT * FROM revisions WHERE id = :id LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $revisionId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}