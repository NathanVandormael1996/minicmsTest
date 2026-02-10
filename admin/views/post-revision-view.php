<?php
declare(strict_types=1);
?>

<section class="p-6">
    <div class="max-w-4xl mx-auto">
        <div class="flex items-center justify-between mb-6">
            <a href="<?= ADMIN_BASE_PATH ?>/posts/<?= (int)$postId ?>/edit" class="text-blue-600 hover:underline flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Terug naar het overzicht
            </a>

            <div class="flex items-center gap-4">
                <span class="text-sm text-gray-500 italic">
                    Revisie van: <?= date('d-m-Y H:i', strtotime($revision['created_at'])) ?>
                </span>

                <form action="<?= ADMIN_BASE_PATH ?>/posts/<?= (int)$postId ?>/revisions/<?= (int)$revision['id'] ?>/restore"
                      method="post"
                      onsubmit="return confirm('Weet je zeker dat je de huidige post wilt overschrijven met deze versie?');">
                    <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 text-sm font-bold">
                        Deze versie herstellen
                    </button>
                </form>
            </div>
        </div>

        <div class="bg-white rounded shadow overflow-hidden border border-gray-200">
            <div class="p-6 border-b border-gray-100 bg-gray-50">
                <h1 class="text-2xl font-bold text-gray-800">
                    <?= htmlspecialchars((string)$revision['title'], ENT_QUOTES) ?>
                </h1>
            </div>

            <div class="p-6 prose max-w-none">
                <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-2">Inhoud</h3>
                <div class="text-gray-700 whitespace-pre-wrap border-l-4 border-blue-200 pl-4">
                    <?= htmlspecialchars((string)$revision['content'], ENT_QUOTES) ?>
                </div>
            </div>

            <?php if (!empty($revision['meta_title']) || !empty($revision['meta_description'])): ?>
                <div class="p-6 bg-gray-50 border-t border-gray-200">
                    <h3 class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-3">SEO Metadata van deze revisie</h3>
                    <div class="grid grid-cols-1 gap-4 text-sm">
                        <div>
                            <span class="font-semibold block">Meta Titel:</span>
                            <span class="text-gray-600"><?= htmlspecialchars((string)($revision['meta_title'] ?? 'Niet ingesteld'), ENT_QUOTES) ?></span>
                        </div>
                        <div>
                            <span class="font-semibold block">Meta Beschrijving:</span>
                            <span class="text-gray-600"><?= htmlspecialchars((string)($revision['meta_description'] ?? 'Niet ingesteld'), ENT_QUOTES) ?></span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>