<?php

namespace Admin\Services;

class SlugService
{
    public function generateSlug(string $title) : string
    {
        $slug = strtolower($title);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        return trim($slug, '-');    }
}