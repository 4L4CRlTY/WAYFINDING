<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

abstract class Controller
{
    protected function tableSearch(Request $request): string
    {
        $search = $request->query('search', '');

        if (! is_string($search) && ! is_numeric($search)) {
            return '';
        }

        return trim((string) $search);
    }

    protected function tableSearchPattern(string $search): string
    {
        return "%{$search}%";
    }

    protected function dashboardRouteName(?User $user): string
    {
        return match ($user?->role) {
            'admin' => 'admin.dashboard',
            'authorized_user' => 'authorized.dashboard',
            default => 'user.dashboard',
        };
    }
}
