<?php

namespace App\Http\Controllers;

use App\Models\Provider;
use Illuminate\Http\Request;

class AdvertisingController extends Controller
{
    public function index()
    {
        $providers = Provider::with([
            'category',
            'services',
            'primaryBannerImage',
            'bannerImages',
            'subgroups',
        ])
        ->where('is_active', true)
        ->get()
        ->map(function ($provider) {
            $bannerUrl = null;
            if ($provider->primaryBannerImage) {
                $bannerUrl = $provider->primaryBannerImage->image_path;
            } elseif ($provider->bannerImages->first()) {
                $bannerUrl = $provider->bannerImages->first()->image_path;
            }

            $servicesList = $provider->services->pluck('name')->toArray();
            $hoursText = $this->formatHours($provider->hours);

            return [
                'id' => $provider->id,
                'name' => $provider->business_name,
                'description' => $provider->description,
                'category' => $provider->category->description ?? 'General',
                'rating' => $provider->rating,
                'promo' => $provider->promo,
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
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
          ->header('Pragma', 'no-cache');
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
}
