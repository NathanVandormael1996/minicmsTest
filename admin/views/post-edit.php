<?php
declare(strict_types=1);
?>

<section class="p-6">
    <div class="bg-white p-6 rounded shadow max-w-2xl">
        <h1 class="text-2xl font-bold mb-4"><?= htmlspecialchars((string)($title ?? 'Post bewerken'), ENT_QUOTES) ?></h1>

        <?php require __DIR__ . '/partials/flash.php'; ?>

        <form method="post" action="<?= ADMIN_BASE_PATH ?>/posts/<?= (int)($postId ?? 0) ?>/update" class="space-y-4">
            <div>
                <label class="block text-sm font-semibold mb-1">Titel</label>
                <input class="w-full border rounded px-3 py-2"
                       type="text"
                       name="title"
                       value="<?= htmlspecialchars((string)($old['title'] ?? ''), ENT_QUOTES) ?>"
                       required>
            </div>

            <div>
                <label class="block text-sm font-semibold mb-1">Inhoud</label>
                <textarea class="w-full border rounded px-3 py-2"
                          name="content"
                          rows="10"
                          required><?= htmlspecialchars((string)($old['content'] ?? ''), ENT_QUOTES) ?></textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold mb-1">Status</label>
                    <?php $status = (string)($old['status'] ?? 'draft'); ?>
                    <select class="w-full border rounded px-3 py-2" name="status">
                        <option value="draft" <?= $status === 'draft' ? 'selected' : '' ?>>draft</option>
                        <option value="published" <?= $status === 'published' ? 'selected' : '' ?>>published</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-semibold mb-1">Publicatiedatum (Planning)</label>
                    <input class="w-full border rounded px-3 py-2"
                           type="datetime-local"
                           name="published_at"
                           value="<?= !empty($old['published_at']) ? date('Y-m-d\TH:i', strtotime($old['published_at'])) : '' ?>">
                    <p class="text-xs text-gray-500 mt-1">Leeg laten voor onmiddellijke publicatie.</p>
                </div>
            </div>

            <div>
                <label class="block text-sm font-semibold mb-1">Featured image</label>
                <?php $featured = (string)($old['featured_media_id'] ?? ''); ?>
                <select class="w-full border rounded px-3 py-2" name="featured_media_id">
                    <option value="">Geen</option>
                    <?php foreach (($media ?? []) as $item): ?>
                        <option value="<?= (int)$item['id'] ?>" <?= ((string)$item['id'] === $featured) ? 'selected' : '' ?>>
                            <?= htmlspecialchars((string)$item['original_name'], ENT_QUOTES) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="bg-gray-50 p-4 rounded border border-gray-200">
                <h3 class="font-bold text-sm mb-3">SEO Instellingen</h3>
                <div class="space-y-3">
                    <div>
                        <label class="block text-xs font-semibold mb-1">Meta Titel</label>
                        <input class="w-full border rounded px-3 py-2 text-sm"
                               type="text"
                               name="meta_title"
                               value="<?= htmlspecialchars((string)($old['meta_title'] ?? ''), ENT_QUOTES) ?>">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold mb-1">Meta Beschrijving</label>
                        <textarea class="w-full border rounded px-3 py-2 text-sm"
                                  name="meta_description"
                                  rows="3"><?= htmlspecialchars((string)($old['meta_description'] ?? ''), ENT_QUOTES) ?></textarea>
                    </div>
                </div>
            </div>

            <div class="flex gap-3 pt-4">
                <button class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700" type="submit">
                    Opslaan
                </button>
                <a href="<?= ADMIN_BASE_PATH ?>/posts/<?= $postId ?>/cancel" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                    Annuleren
                </a>
            </div>
        </form>

        <div class="mt-12 pt-6 border-t border-gray-200">
            <h2 class="text-lg font-bold mb-4">Revisiehistorie (Max 3)</h2>
            <?php if (empty($revisions)): ?>
                <p class="text-sm text-gray-500 italic">Er zijn nog geen eerdere versies van deze post.</p>
            <?php else: ?>
                <div class="overflow-hidden border rounded">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="px-4 py-2 text-left">Datum</th>
                            <th class="px-4 py-2 text-right">Acties</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($revisions as $rev): ?>
                            <tr class="border-b last:border-0">
                                <td class="px-4 py-2"><?= date('d-m-Y H:i', strtotime($rev['created_at'])) ?></td>
                                <td class="px-4 py-2 text-right space-x-2">
                                    <a href="<?= ADMIN_BASE_PATH ?>/posts/<?= $postId ?>/revisions/<?= $rev['id'] ?>"
                                       class="text-blue-600 hover:underline">Bekijken</a>

                                    <form action="<?= ADMIN_BASE_PATH ?>/posts/<?= $postId ?>/revisions/<?= $rev['id'] ?>/restore"
                                          method="post"
                                          class="inline"
                                          onsubmit="return confirm('Weet je zeker dat je deze versie wilt herstellen? De huidige tekst wordt overschreven.');">
                                        <button type="submit" class="text-green-600 hover:underline">Herstellen</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>