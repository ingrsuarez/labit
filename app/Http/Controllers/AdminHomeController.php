<?php

namespace App\Http\Controllers;

use App\Support\NavigationCatalog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminHomeController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $shortcuts = NavigationCatalog::shortcutsForUser($user, 12);

        return view('admin.home', compact('shortcuts'));
    }
}
