<?php

namespace Database\Seeders;

use App\Models\Module;
use App\Models\Page;
use App\Models\TypeUser;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class InventoryPagesSeeder extends Seeder
{
    /**
     * Páginas del proceso de inventario por tipo de usuario.
     *
     * @var array<string, array<int, array{description: string, url: string}>>
     */
    private const PAGES = [
        'Prestador' => [
            ['description' => 'Inventario', 'url' => 'inventario'],
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
