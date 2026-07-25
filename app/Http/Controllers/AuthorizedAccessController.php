<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class AuthorizedAccessController extends Controller
{
    public function index(): View
    {
        return view('admin.authorized_access.index', [
            'authorizedUsers' => User::query()
                ->where('role', 'authorized_user')
                ->latest()
                ->paginate(10),
            'features' => config('authorized_features', []),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules(), [], $this->validationAttributes());

        User::create([
            'username' => $validated['username'],
            'email' => $validated['email'],
            'email_verified_at' => now(),
            'password' => Hash::make($validated['password']),
            'role' => 'authorized_user',
            'position' => $validated['position'],
            'authorized_permissions' => array_values(array_unique($validated['authorized_permissions'])),
            'status' => $validated['status'],
        ]);

        return back()->with('success', 'Authorized account created and feature access assigned.');
    }

    public function update(Request $request, User $authorizedUser): RedirectResponse
    {
        abort_unless($authorizedUser->role === 'authorized_user', 404);

        $validated = $request->validate($this->rules($authorizedUser), [], $this->validationAttributes());

        $attributes = [
            'username' => $validated['username'],
            'email' => $validated['email'],
            'position' => $validated['position'],
            'authorized_permissions' => array_values(array_unique($validated['authorized_permissions'])),
            'status' => $validated['status'],
        ];

        if (! empty($validated['password'])) {
            $attributes['password'] = Hash::make($validated['password']);
        }

        $authorizedUser->update($attributes);

        return back()->with('success', "Access settings for {$authorizedUser->username} were updated.");
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(?User $authorizedUser = null): array
    {
        $emailRule = Rule::unique('users', 'email');

        if ($authorizedUser) {
            $emailRule->ignore($authorizedUser->id);
        }

        return [
            'username' => ['required', 'string', 'max:100'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', $emailRule],
            'position' => ['required', 'string', 'max:120'],
            'password' => [
                $authorizedUser ? 'nullable' : 'required',
                'confirmed',
                Rules\Password::defaults(),
            ],
            'status' => ['required', Rule::in(['0', '1'])],
            'authorized_permissions' => ['required', 'array', 'min:1'],
            'authorized_permissions.*' => [
                'string',
                Rule::in(array_keys(config('authorized_features', []))),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function validationAttributes(): array
    {
        return [
            'username' => 'account holder name',
            'authorized_permissions' => 'feature access',
            'authorized_permissions.*' => 'selected feature',
        ];
    }
}
