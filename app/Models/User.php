<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\Request;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Sefirosweb\LaravelAccessList\Http\Models\User as SefiroswebUser;
use Sefirosweb\LaravelAccessList\Http\Traits\SelfModelValidator;

class User extends SefiroswebUser
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes, SelfModelValidator;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    public function getValidationRules(Request $request = null)
    {
        return [
            'name' => [
                'required',
                'min:3',
                'max:255',
                'unique:App\Models\User,name,' . $request?->id
            ],
            'email' => [
                'required',
                'min:3',
                'max:255',
                'email',
                'unique:App\Models\User,email,' . $request?->id
            ],
            'password' => [
                'required',
                'min:6',
                'max:16',
            ],
        ];
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
}
