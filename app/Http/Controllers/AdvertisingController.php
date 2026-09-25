<?php

namespace App\Http\Controllers;

use App\Models\Provider;
use App\Models\Group;
use Illuminate\Http\Request;

class AdvertisingController extends Controller
{
    public function index()
    {
        $allGroups = Group::orderBy('id')->get();

        $providers = Provider::with([
            'category',
            'services',
            'subgroups.group',
            'user.status',
        ])
        ->where('is_active', true)
        ->whereHas('user', function ($query) {
            $query->advertisable();
        })
        ->get()
        ->map(function ($provider) {
            $bannerUrl = $this->findProviderBanner($provider->id);

            if ($bannerUrl) {
                $this->ensureResized(public_path('images/publicidad/' . $bannerUrl));
            }

            $servicesList = $provider->services->pluck('name')->toArray();
            $hoursText = $this->formatHours($provider->hours);

            $groupNames = $provider->subgroups->pluck('group.description')->filter()->unique()->values()->toArray();
            if (empty($groupNames)) {
                $groupNames = [$provider->category->description ?? 'General'];
            }

            $primaryGroup = $groupNames[0];

            return [
                'id' => $provider->id,
                'name' => $provider->business_name,
                'description' => $provider->description,
                'category' => $primaryGroup,
                'groups' => $groupNames,
                'rating' => $provider->rating,
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
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
          ->header('Pragma', 'no-cache');
    }

    private function findProviderBanner($providerId)
    {
        $dir = public_path('images/publicidad');
        $extensions = ['jpg', 'jpeg', 'png', 'webp'];

        foreach ($extensions as $ext) {
            $file = 'provider_' . $providerId . '.' . $ext;
            if (file_exists($dir . '/' . $file)) {
                return $file;
            }
        }

        return null;
    }

    private function formatHours($hours)
    {
        if (!is_array($hours)) return 'Consultar horarios';

        $dayNames = [
            'mon' => 'Lun', 'tue' => 'Mar', 'wed' => 'Mie', 'thu' => 'Jue',
            'fri' => 'Vie', 'sat' => 'Sab', 'dom' => 'Dom',
        ];

        $parts = [];
        foreach ($hours as $day => $data) {
            if (!isset($data['open']) || !$data['open']) continue;
            $shifts = $data['shifts'] ?? [];
            if (empty($shifts)) continue;

            $timeRanges = array_map(function ($shift) {
                return ($shift['from'] ?? '') . '-' . ($shift['to'] ?? '');
            }, $shifts);

            $parts[] = ($dayNames[$day] ?? $day) . ' ' . implode('·', $timeRanges);
        }

        return empty($parts) ? 'Consultar horarios' : implode(' · ', $parts);
    }

    private function ensureResized(string $path, int $maxWidth = 200)
    {
        $info = @getimagesize($path);
        if (!$info) return;
        if ($info[0] <= $maxWidth) return;

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
        if (!$src) return;

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
