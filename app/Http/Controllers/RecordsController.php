<?php

namespace App\Http\Controllers;

use Illuminate\View\View;
use Illuminate\Http\Request;

class RecordsController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $expenses = $user->expenses()
            ->latest('date')
            ->get();

        $incomes = $user->incomes()
            ->latest('date')
            ->get();

        return view('records', [
            'expenses' => $expenses,
            'incomes' => $incomes,
        ]);
    }
}
