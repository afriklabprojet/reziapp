<?php

namespace Tests;

/**
 * Trait pour gérer les tests spécifiques à MySQL
 */
trait RequiresMysql
{
    /**
     * Skip le test si on utilise SQLite (environnement de test par défaut)
     */
    protected function skipIfSqlite(): void
    {
        if (config('database.default') === 'sqlite' ||
            config('database.connections.'.config('database.default').'.driver') === 'sqlite') {
            $this->markTestSkipped('Ce test requiert MySQL et ne peut pas être exécuté avec SQLite.');
        }
    }
}
