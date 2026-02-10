<?php
declare(strict_types=1);

use Admin\Core\Auth;

?>

<section class="p-6">
    <div class="bg-white p-6 rounded shadow">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-bold">Posts overzicht</h2>

            <a class="underline" href="/admin/posts/create">
                + Nieuwe post
            </a>
        </div>

        <table class="w-full text-sm">
            <thead>
            <tr class="text-left border-b">
                <th class="py-2">Titel</th>
                <th>Datum</th>
                <th>Status</th>
                <th class="text-right">Acties</th>
            </tr>
            </thead>

            <tbody>
            <?php foreach ($posts as $post): ?>
                <?php $isDeleted = !empty($post['deleted_at']); ?>
                <tr class="border-b <?php echo $isDeleted ? 'bg-red-50 opacity-75' : ''; ?>">
                    <td class="py-2">
                        <?php if (!$isDeleted): ?>
                            <a class="underline" href="/admin/posts/view/<?php echo $post['slug']; ?>">
                                <?php echo htmlspecialchars((string)$post['title'], ENT_QUOTES); ?>
                            </a>
                        <?php else: ?>
                            <span class="text-gray-500 italic">
                                [Verwijderd] <?php echo htmlspecialchars((string)$post['title'], ENT_QUOTES); ?>
                            </span>
                        <?php endif; ?>
                    </td>
                    <td><?php echo htmlspecialchars((string)$post['created_at'], ENT_QUOTES); ?></td>
                    <td>
                        <?php if ($isDeleted): ?>
                            <span class="bg-red-200 text-red-800 px-2 py-1 rounded text-xs font-bold">VERWIJDERD</span>
                        <?php else: ?>
                            <span class="bg-green-100 text-green-800 px-2 py-1 rounded text-xs font-bold">
                                <?php echo strtoupper(htmlspecialchars((string)$post['status'], ENT_QUOTES)); ?>
                            </span>
                        <?php endif; ?>
                    </td>
                    <td class="text-right space-x-3">
                        <?php if (!$isDeleted): ?>
                            <a class="underline" href="/admin/posts/<?php echo (int)$post['id']; ?>/edit">
                                Bewerken
                            </a>
                            <?php if (Auth::isAdmin()): ?>
                                <a class="underline text-red-600" href="/admin/posts/<?php echo (int)$post['id']; ?>/delete">
                                    Verwijderen
                                </a>
                            <?php endif; ?>
                        <?php else: ?>
                            <?php if (Auth::isAdmin()): ?>
                                <form action="<?= ADMIN_BASE_PATH ?>/posts/<?= $post['id'] ?>/restore" method="POST" style="display:inline;">
                                    <button type="submit" class="underline text-green-600 border-none bg-none p-0 cursor-pointer">
                                        Herstellen
                                    </button>
                                </form>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>