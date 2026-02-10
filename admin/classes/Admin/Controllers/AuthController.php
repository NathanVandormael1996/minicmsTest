<?php
declare(strict_types=1);

namespace Admin\Controllers;

use Admin\Core\View;
use Admin\Core\Auth;
use Admin\Repositories\UsersRepository;

class AuthController
{
    private UsersRepository $usersRepository;

    /**
     * __construct()
     *
     * Doel:
     * Bewaart UsersRepository zodat we users kunnen opzoeken bij login.
     */
    public function __construct(UsersRepository $usersRepository)
    {
        $this->usersRepository = $usersRepository;
    }

    /**
     * showLogin()
     *
     * Doel:
     * Toont de loginpagina met lege errors en old input.
     */
    public function showLogin(): void
    {
        View::render('login.php', [
            'title' => 'Login',
            'errors' => [],
            'old' => [
                'email' => '',
            ],
        ]);
    }

    /**
     * login()
     *
     * Doel:
     * Verwerkt het loginformulier.
     *
     * Werking:
     * 1) Lees email en password uit $_POST.
     * 2) Basis validatie: email/password verplicht.
     * 3) Zoek user op via findByEmail().
     * 4) Als user niet bestaat of password niet klopt -> error.
     * 5) Als login ok -> redirect naar dashboard.
     *
     * Belangrijk:
     * In LES 7.1 maken we nog geen session-login.
     */
    public function login(): void
    {
        $email = trim((string)($_POST['email'] ?? ''));
        $password = (string)($_POST['password'] ?? '');

        $errors = [];

        if ($email === '') {
            $errors[] = 'Email is verplicht.';
        }

        if ($password === '') {
            $errors[] = 'Wachtwoord is verplicht.';
        }

        if (!empty($errors)) {
            View::render('login.php', [
                'title' => 'Login',
                'errors' => $errors,
                'old' => ['email' => $email],
            ]);
            return;
        }

        $user = $this->usersRepository->findByEmail($email);

        if ($user === null) {
            View::render('login.php', [
                'title' => 'Login',
                'errors' => ['Deze login is niet correct.'],
                'old' => ['email' => $email],
            ]);
            return;
        }

        $hash = (string)$user['password_hash'];

        if (!password_verify($password, $hash)) {
            View::render('login.php', [
                'title' => 'Login',
                'errors' => ['Deze login is niet correct.'],
                'old' => ['email' => $email],
            ]);
            return;
        }

        /**
         * Bewaar user_id en role_name zodat we autorisatiechecks kunnen doen.
         */
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['user_role'] = (string)$user['role_name'];

        header('Location: /admin');
        exit;
    }
    /**
     * logout()
     *
     * Doel:
     * Logt de gebruiker uit en stuurt door naar login.
     */
    public function logout(): void
    {
        Auth::logout();

        header('Location: /admin/login');
        exit;
    }

    // Inside AuthController.php

    public function redirectToProvider(string $provider): void
    {
        if ($provider === 'github') {
            $clientId = 'Ov23liFnvyC7Mheaxi8s';
            $redirectUri = urlencode('http://minicms.test/admin/login/callback/github');

            // This sends the user to the REAL GitHub login page [cite: 105]
            $url = "https://github.com/login/oauth/authorize?client_id={$clientId}&redirect_uri={$redirectUri}&scope=user:email";

            header("Location: " . $url);
            exit;
        }
    }

    public function handleProviderCallback(string $provider): void
    {
        // 1. GitHub sends a temporary ?code=... in the URL
        $code = $_GET['code'] ?? null;
        if (!$code) {
            header('Location: /admin/login');
            exit;
        }

        // 2. Technical Exchange: Trade 'code' for an 'access_token'
        $clientId = 'Ov23liFnvyC7Mheaxi8s';
        $clientSecret = 'ebce81271209e927410f910d6c34ea1b759ee368';

        $tokenParams = [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'code' => $code
        ];

        $opts = [
            'http' => [
                'method' => 'POST',
                'header' => "Accept: application/json\r\nContent-type: application/x-www-form-urlencoded\r\n",
                'content' => http_build_query($tokenParams)
            ]
        ];

        $tokenResponse = json_decode(file_get_contents("https://github.com/login/oauth/access_token", false, stream_context_create($opts)), true);
        $accessToken = $tokenResponse['access_token'] ?? null;

        if (!$accessToken) {
            exit('Token exchange failed. Check your Secret/Client ID.');
        }

        // 3. Get REAL user info from GitHub API
        $userOpts = [
            'http' => [
                'method' => 'GET',
                'header' => "Authorization: token $accessToken\r\nUser-Agent: MiniCMS\r\n"
            ]
        ];

        $userData = json_decode(file_get_contents("https://api.github.com/user", false, stream_context_create($userOpts)), true);

        // Now these values are REAL, not "imagined" [cite: 93, 107]
        $extId = (string)$userData['id'];
        $email = $userData['email'] ?? ($userData['login'] . '@github.com');
        $name  = $userData['name'] ?? $userData['login'];

        // 4. Decision: Check if user exists [cite: 112]
        $user = $this->usersRepository->findByExternalId($provider, $extId);

        if ($user === null) {
            // 5. Create user automatically if new [cite: 112, 117]
            $userId = $this->usersRepository->createExternal($email, $name, $provider, $extId, 2);
            $user = $this->usersRepository->findById($userId);
        }

        // 6. Set session and login [cite: 100, 116]
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['user_role'] = (string)$user['role_name'];

        header('Location: /admin');
        exit;
    }


}
