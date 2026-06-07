<?php

/*
 * Custom test bootstrap for git worktrees.
 *
 * The worktree shares the main project's vendor directory (symlinked), but
 * its App\ classes live under the worktree's own app/ directory — not the
 * main project's. We prepend the worktree paths so Composer's autoloader
 * resolves App\ and Tests\ from the correct location.
 */

$worktreeRoot = dirname(__DIR__);
$mainVendor = realpath($worktreeRoot.'/vendor');

require $mainVendor.'/autoload.php';

// Prepend the worktree's app/ so it shadows the main project's app/
spl_autoload_register(function (string $class) use ($worktreeRoot): void {
    if (str_starts_with($class, 'App\\')) {
        $relative = str_replace('\\', '/', substr($class, 4));
        $file = $worktreeRoot.'/app/'.$relative.'.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }

    if (str_starts_with($class, 'Tests\\')) {
        $relative = str_replace('\\', '/', substr($class, 6));
        $file = $worktreeRoot.'/tests/'.$relative.'.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
}, prepend: true);
