<?php
// Temporary migration runner — delete after use
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle($request = Illuminate\Http\Request::capture());

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

try {
    if (!Schema::hasColumn('users', 'jeko_contact_id')) {
        Schema::table('users', function (Blueprint $table) {
            $table->string('jeko_contact_id')->nullable()->after('remember_token');
        });
        echo "OK: jeko_contact_id column added to users table.\n";
    } else {
        echo "SKIP: jeko_contact_id column already exists.\n";
    }
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
