<?php

namespace App\Http\Controllers;

use App\Models\Group;
use App\Models\Module;
use App\Models\Provider;

class AdvertisingController extends Controller
{
    public function index()
    {
        // Solo los grupos activos se ofrecen en el sitio publico (filtros,
        // "Estructura de Servicios" y etiquetas de los comercios).
        $allGroups = Group::active()->orderBy('id')->get();

        $activeGroupIds = $allGroups->pluck('id');

        // Modulos con sus grupos activos y subgrupos (servicios), para la
        // seccion "Estructura de Servicios" y el footer. Se excluyen los
        // modulos sin grupos activos (por ejemplo, "Administrar").
        $modules = Module::with(['groups' => function ($query) {
            $query->active()->orderBy('id')->with('subgroups');
        }])
            ->get()
            ->filter(fn ($module) => $module->groups->isNotEmpty())
            ->values();

        // Grupos activos sin modulo asignado.
        $orphanGroups = $allGroups->filter(fn ($grp) => ! $grp->module_id)->values();
        $allGroups->load('subgroups');

        $providers = Provider::with([
            'category',
            'services',
            'subgroups.group',
            'user.status',
        ])
            ->where('is_active', true)
            ->where(function ($query) {
                // Politica de visibilidad: umbral minimo de reputacion >= 4.0 o sin calificaciones aun (rating null o 0)
                $query->whereNull('rating')
                    ->orWhere('rating', '>=', 4.0);
            })
            ->whereHas('user', function ($query) {
                $query->advertisable();
            })
            ->get()
            ->filter(function ($provider) use ($activeGroupIds) {
                // Los comercios que solo pertenecen a grupos desactivados no se
                // muestran; sin grupos asignados siguen visibles ("General").
                $linked = $provider->subgroups->pluck('group_id')->filter()
                    ->push($provider->category_id)
                    ->filter();

                if ($linked->isEmpty()) {
                    return true;
                }

                return $linked->intersect($activeGroupIds)->isNotEmpty();
            })
            ->sortByDesc(function ($provider) {
                return [
                    $provider->rating ?? 0,
                    $provider->rating_count ?? 0,
                ];
            })
            ->values()
            ->map(function ($provider) use ($activeGroupIds) {
                $bannerUrl = $this->findProviderBanner($provider->id);

                if ($bannerUrl) {
                    $this->ensureResized(public_path('images/publicidad/'.$bannerUrl));
                }

                $servicesList = $provider->services->pluck('name')->toArray();
                $hoursText = $this->formatHours($provider->hours);

                // La etiqueta del comercio solo puede nombrar grupos activos.
                $groupNames = $provider->subgroups
                    ->filter(function ($subgroup) use ($activeGroupIds) {
                        return $subgroup->group_id && $activeGroupIds->contains($subgroup->group_id);
                    })
                    ->pluck('group.description')->filter()->unique()->values()->toArray();

                if (empty($groupNames)) {
                    $category = $provider->category;
                    $groupNames = ($category && $activeGroupIds->contains($category->id))
                        ? [$category->description]
                        : ['General'];
                }

                $primaryGroup = $groupNames[0];

                return [
                    'id' => $provider->id,
                    'name' => $provider->business_name,
                    'description' => $provider->description,
                    'category' => $primaryGroup,
                    'groups' => $groupNames,
                    'rating' => $provider->rating,
                    'rating_count' => $provider->rating_count ?? 0,
                    'promo' => $provider->promo,
                    'status' => $provider->user->status->status ?? null,
                    'phone' => $provider->phone,
                    'whatsapp' => $provider->whatsapp,
                    'zone' => $provider->zone,
                    'hours' => $hoursText,
                    'banner_url' => $bannerUrl,
                    'services' => $servicesList,
                ];
            });

        return response()->view('welcome', [
            'providers' => $providers,
            'allGroups' => $allGroups,
            'modules' => $modules,
            'orphanGroups' => $orphanGroups,
            'footerModules' => $modules,
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->header('Pragma', 'no-cache');
    }

    private function findProviderBanner($providerId)
    {
        $dir = public_path('images/publicidad');
        $extensions = ['jpg', 'jpeg', 'png', 'webp'];

        foreach ($extensions as $ext) {
            $file = 'provider_'.$providerId.'.'.$ext;
            if (file_exists($dir.'/'.$file)) {
                return $file;
            }
        }

        return null;
    }

    private function formatHours($hours)
    {
        if (! is_array($hours)) {
            return 'Consultar horarios';
        }

        $dayNames = [
            'mon' => 'Lun', 'tue' => 'Mar', 'wed' => 'Mie', 'thu' => 'Jue',
            'fri' => 'Vie', 'sat' => 'Sab', 'dom' => 'Dom',
        ];

        $parts = [];
        foreach ($hours as $day => $data) {
            if (! isset($data['open']) || ! $data['open']) {
                continue;
            }
            $shifts = $data['shifts'] ?? [];
            if (empty($shifts)) {
                continue;
            }

            $timeRanges = array_map(function ($shift) {
                return ($shift['from'] ?? '').'-'.($shift['to'] ?? '');
            }, $shifts);

            $parts[] = ($dayNames[$day] ?? $day).' '.implode('·', $timeRanges);
        }

        return empty($parts) ? 'Consultar horarios' : implode(' · ', $parts);
    }

    private function ensureResized(string $path, int $maxWidth = 200)
    {
        $info = @getimagesize($path);
        if (! $info) {
            return;
        }
        if ($info[0] <= $maxWidth) {
            return;
        }

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $origW = $info[0];
        $origH = $info[1];
        $newW = $maxWidth;
        $newH = (int) round($origH * ($maxWidth / $origW));

        $src = match ($ext) {
            'jpg', 'jpeg' => @imagecreatefromjpeg($path),
            'png' => @imagecreatefrompng($path),
            default => null,
        };
        if (! $src) {
            return;
        }

        $dst = imagecreatetruecolor($newW, $newH);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $newW, $newH, $origW, $origH);

        match ($ext) {
            'jpg', 'jpeg' => imagejpeg($dst, $path, 85),
            'png' => imagepng($dst, $path, 6),
        };

        imagedestroy($src);
        imagedestroy($dst);
    }
}
