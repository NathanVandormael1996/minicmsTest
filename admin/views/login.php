<?php
declare(strict_types=1);
?>

<section class="p-6">
    <div class="bg-white p-6 rounded shadow max-w-md mx-auto">
        <h2 class="text-xl font-bold mb-4">Login</h2>

        <?php if (!empty($errors)): ?>
            <div class="mb-4 p-4 border border-red-200 bg-red-50 rounded text-red-700">
                <p class="font-bold mb-2">Controleer je invoer:</p>
                <ul class="list-disc pl-6">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars((string)$error, ENT_QUOTES); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="post" action="/admin/login" class="space-y-4 border-b pb-6 mb-6">
            <div>
                <label class="block text-sm font-bold mb-1" for="email">Email</label>
                <input
                        class="w-full border rounded p-2"
                        type="email"
                        id="email"
                        name="email"
                        value="<?php echo htmlspecialchars((string)($old['email'] ?? ''), ENT_QUOTES); ?>"
                >
            </div>

            <div>
                <label class="block text-sm font-bold mb-1" for="password">Wachtwoord</label>
                <input
                        class="w-full border rounded p-2"
                        type="password"
                        id="password"
                        name="password"
                >
            </div>

            <div>
                <button class="w-full bg-blue-600 text-white font-bold rounded px-4 py-2 hover:bg-blue-700" type="submit">
                    Login
                </button>
            </div>
        </form>

        <div class="space-y-3">
            <p class="text-center text-sm text-gray-500 mb-4">Of log in via</p>

            <a href="/admin/login/github"
               class="flex items-center justify-center gap-2 w-full border border-gray-300 rounded px-4 py-2 hover:bg-gray-50 transition-colors">
                <img src="https://github.githubassets.com/images/modules/logos_page/GitHub-Mark.png" alt="" class="w-5 h-5">
                <span>Login met GitHub</span> </a>
        </div>
    </div>
</section>