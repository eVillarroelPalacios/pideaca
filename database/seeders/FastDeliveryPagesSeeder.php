<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Page;
use App\Models\TypeUser;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Deja configurado el circuito de permisos del módulo Pide acá (Fast Delivery).
 *
 * 1. Crea las páginas del módulo según los procesos de Fast Delivery.
 * 2. Las asigna a page_type_user, que es la plantilla que usa
 *    RegisterController al registrarse un Cliente o un Prestador.
 * 3. Las propaga a page_user de los usuarios que ya existen de esos tipos,
 *    igual que hace el registro (sync, no attach).
 *
 * Es idempotente: se puede correr las veces que haga falta.
 */
class FastDeliveryPagesSeeder extends Seeder
{
    /**
     * Páginas por tipo de usuario. El tipo se busca por description en type_users.
     *
     * @var array<string, array<int, array{description: string, url: string}>>
     */
    private const PAGES = [
        'Cliente' => [
            ['description' => 'Comercios', 'url' => 'comercios'],
            ['description' => 'Mis Pedidos', 'url' => 'mis-pedidos'],
        ],
        'Prestador' => [
            ['description' => 'Mi Catálogo', 'url' => 'mi-catalogo'],
            ['description' => 'Pedidos', 'url' => 'pedidos'],
        ],
    ];

    public function run(): void
    {
        $module = Module::firstOrCreate(
            ['description' => 'Pide acá'],
            ['icon' => null]
        );

        DB::transaction(function () use ($module) {
            foreach (self::PAGES as $typeDescription => $pages) {
                $type = TypeUser::where('description', $typeDescription)->first();

                if (! $type) {
                    $this->command?->warn("No se encontró el tipo de usuario «{$typeDescription}»: se omiten sus páginas.");

                    continue;
                }

                foreach ($pages as $page) {
                    // La description es la clave natural: si la página ya existe
                    // (por ejemplo, creada desde el panel) no se pisa su url ni su módulo.
                    $model = Page::firstOrCreate(
                        ['description' => $page['description']],
                        ['url' => $page['url'], 'module_id' => $module->id]
                    );

                    $type->assignedPages()->syncWithoutDetaching([$model->id]);
                }

                $this->syncUsersOfType($type);
            }
        });
    }

    /**
     * Los usuarios existentes del tipo quedan con la misma plantilla que recibe
     * un usuario nuevo al registrarse. Solo agrega: nunca quita páginas que un
     * administrador haya asignado a mano.
     */
    private function syncUsersOfType(TypeUser $type): void
    {
        $templateIds = $type->assignedPages()->pluck('pages.id')->all();

        User::where('type_user_id', $type->id)
            ->chunkById(200, function ($users) use ($templateIds) {
                foreach ($users as $user) {
                    $user->pages()->syncWithoutDetaching($templateIds);
                }
            });
    }
}
