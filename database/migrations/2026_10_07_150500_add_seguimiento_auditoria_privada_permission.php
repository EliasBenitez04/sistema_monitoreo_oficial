<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $tableNames = config('permission.table_names');
        $permissionsTable = $tableNames['permissions'] ?? 'permissions';

        if (!Schema::hasTable($permissionsTable)) {
            return;
        }

        $name = 'seguimiento auditoria privada';
        $guard = 'web';

        $exists = DB::table($permissionsTable)
            ->where('name', $name)
            ->where('guard_name', $guard)
            ->exists();

        if (!$exists) {
            DB::table($permissionsTable)->insert([
                'name' => $name,
                'guard_name' => $guard,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $tableNames = config('permission.table_names');
        $permissionsTable = $tableNames['permissions'] ?? 'permissions';

        if (!Schema::hasTable($permissionsTable)) {
            return;
        }

        DB::table($permissionsTable)
            ->where('name', 'seguimiento auditoria privada')
            ->where('guard_name', 'web')
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
