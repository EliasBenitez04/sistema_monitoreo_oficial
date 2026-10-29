<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * El rol solo permite consultar Accesorios. El acceso se controla en rutas
     * y middleware, sin conceder permisos de escritura a otros módulos.
     */
    public function up(): void
    {
        Role::firstOrCreate([
            'name' => 'visualizador_accesorios',
            'guard_name' => 'web',
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        // No eliminar el rol porque podría estar asignado a usuarios existentes.
        // Si se desea retirarlo, primero deben reasignarse esos usuarios.
    }
};
