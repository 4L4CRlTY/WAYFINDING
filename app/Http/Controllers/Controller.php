<?php

namespace App\Http\Controllers;

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
}
