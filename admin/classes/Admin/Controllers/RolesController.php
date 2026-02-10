<?php
declare(strict_types=1);

namespace Admin\Controllers;

use Admin\Repositories\RolesRepository;
use Admin\Core\View;
use Admin\Core\Flash;

class RolesController
{
    private RolesRepository $roles;

    public function __construct(RolesRepository $roles)
    {
        $this->roles = $roles;
    }

    /**
     * Toont het overzicht van alle rollen.
     */
    public function index(): void
    {
        View::render('roles.php', [
            'title' => 'Rollenbeheer',
            'roles' => $this->roles->getAll(),
        ]);
    }

    /**
     * Toont het formulier om een nieuwe rol aan te maken.
     */
    public function create(): void
    {
        View::render('role-create.php', [
            'title' => 'Nieuwe rol aanmaken',
        ]);
    }

    /**
     * Verwerkt het opslaan van een nieuwe rol.
     */
    public function store(): void
    {
        $name = trim((string)($_POST['name'] ?? ''));

        if (empty($name)) {
            Flash::set('warning', 'Naam van de rol mag niet leeg zijn.');
            header('Location: ' . ADMIN_BASE_PATH . '/roles/create');
            exit;
        }

        $success = $this->roles->store($name);

        if ($success) {
            Flash::set('success', "Rol '{$name}' succesvol aangemaakt.");
            header('Location: ' . ADMIN_BASE_PATH . '/roles');
        } else {
            Flash::set('error', 'Er is iets misgegaan bij het opslaan.');
            header('Location: ' . ADMIN_BASE_PATH . '/roles/create');
        }
        exit;
    }
}