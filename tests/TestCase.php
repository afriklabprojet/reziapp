<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Cache;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Désactiver Vite pendant les tests (manifest non disponible en CI)
        $this->withoutVite();

        // Flush le cache array entre chaque test pour éviter
        // la contamination (ex: available_locations, filter_communes_*)
        Cache::flush();
    }
}
