<?php

namespace App\Http\Controllers;

use App\Services\DashboardDataService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request, DashboardDataService $dashboard): View
    {
        return view('dashboard', $dashboard->forUser($request->user()));
    }
}
