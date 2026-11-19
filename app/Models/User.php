<?php

declare(strict_types=1);

namespace App\Models;

use App\Traits\HasSemanticSearch;
use App\Traits\Loggable;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, HasSemanticSearch, Loggable, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'username',
        'password',
        'is_aktif',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'is_aktif' => 'boolean',
        ];
    }

    /**
     * Gudang locations assigned to this user.
     */
    public function gudang(): BelongsToMany
    {
        return $this->belongsToMany(Gudang::class, 'user_gudang');
    }

    public function getSemanticText(): string
    {
        $text = "Pengguna bernama {$this->name}, dengan username {$this->username}.";

        if (! $this->exists) {
            return $text;
        }

        $roles = $this->relationLoaded('roles')
            ? $this->roles->pluck('name')->filter()->values()
            : $this->roles()->pluck('name');

        if ($roles->isNotEmpty()) {
            $text .= ' Role: '.$roles->join(', ').'.';
        }

        $gudangs = $this->relationLoaded('gudang')
            ? $this->gudang->pluck('nama')->filter()->values()
            : $this->gudang()->pluck('nama');

        if ($gudangs->isNotEmpty()) {
            $text .= ' Penugasan gudang: '.$gudangs->join(', ').'.';
        }

        return $text;
    }
}
