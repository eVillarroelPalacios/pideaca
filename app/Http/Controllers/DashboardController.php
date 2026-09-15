<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Module;

class DashboardController extends Controller
{
    public function index()
    {
        if (!Auth::check()) {
            return redirect('/');
        }

        $user = Auth::user();
        $modules = Module::with(['pages' => function ($q) use ($user) {
            $q->whereHas('users', function ($q2) use ($user) {
                $q2->where('users.id', $user->id);
            })->orderBy('description');
        }])->orderBy('description')->get()->filter(function ($module) {
            return $module->pages->isNotEmpty();
        });

        return view('dashboard', [
            'user' => $user,
            'modules' => $modules,
        ]);
    }
}