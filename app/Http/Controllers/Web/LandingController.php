<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Support\Facades\Auth;

class LandingController extends Controller
{
    public function index()
    {
        // Si ya inició sesión, lo llevamos a su panel.
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }

        $plans = Plan::where('active', true)->orderBy('monthly_price')->get();

        return view('landing', compact('plans'));
    }
}
