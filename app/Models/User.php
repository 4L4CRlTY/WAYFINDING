<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;

#[Fillable([
    'username',
    'email',
    'password',
    'photo',
    'role',
    'position',
    'authorized_permissions',
    'status',
])]
#[Hidden([
    'password',
    'remember_token',
])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'authorized_permissions' => 'array',
        ];
    }

    public function canAccessFeature(string $feature): bool
    {
        if ($this->role === 'admin') {
            return true;
        }

        if ($this->role !== 'authorized_user' || ! array_key_exists($feature, config('authorized_features', []))) {
            return false;
        }

        return in_array($feature, $this->authorized_permissions ?? [], true);
    }

    /**
     * @return array<string, array<string, string>>
     */
    public function accessibleAuthorizedFeatures(): array
    {
        return array_filter(
            config('authorized_features', []),
            fn (array $definition, string $feature): bool => $this->canAccessFeature($feature),
            ARRAY_FILTER_USE_BOTH,
        );
    }

    public function displayPosition(): string
    {
        $position = trim((string) $this->position);

        if ($position === '') {
            return 'Position Not Assigned';
        }

        return collect(preg_split('/\s+/', $position) ?: [])
            ->map(function (string $word): string {
                if (preg_match('/^[A-Z0-9]{2,5}$/', $word) === 1) {
                    return $word;
                }

                return Str::title(Str::lower($word));
            })
            ->implode(' ');
    }
}
