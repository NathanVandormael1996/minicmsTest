<?php
declare(strict_types=1);
?>

<section class="p-6">
    <div class="bg-white p-6 rounded shadow">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-xl font-bold">Rollen overzicht</h2>

            <a class="underline" href="/admin/roles/create">
                + Nieuwe rol
            </a>
        </div>

        <?php require __DIR__ . '/partials/flash.php'; ?>

        <table class="w-full text-sm">
            <thead>
            <tr class="text-left border-b">
                <th class="py-2">ID</th>
                <th>Naam</th>
                <th class="text-right">Acties</th>
            </tr>
            </thead>

            <tbody>
            <?php foreach (($roles ?? []) as $role): ?>
                <tr class="border-b">
                    <td class="py-2"><?php echo (int)$role['id']; ?></td>
                    <td><?php echo htmlspecialchars((string)$role['name'], ENT_QUOTES); ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>