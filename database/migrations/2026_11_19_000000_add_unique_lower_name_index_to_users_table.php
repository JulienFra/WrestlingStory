<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * The pseudo (users.name) is unique whatever its case: "Julien" and "julien" cannot both exist.
     * The validation checks it first; this index blocks two sign-ups sent at the same time.
     */
    public function up(): void
    {
        DB::statement('CREATE UNIQUE INDEX users_name_lower_unique ON users (lower(name))');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS users_name_lower_unique');
    }
};
