<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Group;
use App\Models\Module;

class DashboardController extends Controller
{
    /**
     * Raiz del proyecto: panel para usuarios autenticados y landing publica
     * para visitantes. La URL del panel deja de tener el segmento /dashboard.
     */
    public function home()
    {
        if (! Auth::check()) {
            return app(AdvertisingController::class)->index();
        }

        return $this->index();
    }

    public function index()
    {
        if (!Auth::check()) {
            return redirect('/');
        }

        $user = Auth::user()->load(['status', 'typeUser']);
        $modules = Module::with(['pages' => function ($q) use ($user) {
            $q->whereHas('users', function ($q2) use ($user) {
                $q2->where('users.id', $user->id);
            })->orderBy('description');
        }])->orderBy('description')->get()->filter(function ($module) {
            return $module->pages->isNotEmpty();
        });

        $footerGroups = Group::active()->orderBy('id')->get();

        return view('dashboard', [
            'user' => $user,
            'modules' => $modules,
            'footerGroups' => $footerGroups,
        ]);
    }
}