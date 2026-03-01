<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Laravel\Pennant\Concerns\HasFeatures;
use Misaf\LaravelAuthifyLog\Contracts\HasUsername;
use Misaf\VendraTenant\Models\Tenant;
use Misaf\VendraTenant\Traits\BelongsToTenant;
use Misaf\VendraUser\Database\Factories\UserFactory;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property int $tenant_id
 * @property string $username
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string|null $password_fingerprint
 * @property string|null $remember_token
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property Carbon|null $deleted_at
 */
final class User extends Authenticatable implements
    FilamentUser,
    HasLocalePreference,
    HasName,
    MustVerifyEmail,
    HasTenants,
    HasMedia
    // HasUsername
{
    use BelongsToTenant;

    /** @use HasFactory<UserFactory> */
    use HasFactory;

    use HasFeatures;
    use HasRoles;
    use InteractsWithMedia;
    use LogsActivity;
    use Notifiable;
    use SoftDeletes;

    protected $casts = [
        'id'                   => 'integer',
        'tenant_id'            => 'integer',
        'username'             => 'string',
        'email'                => 'string',
        'email_verified_at'    => 'datetime',
        'password'             => 'string',
        'password_fingerprint' => 'string',
        'remember_token'       => 'string',
    ];

    protected $fillable = [
        'tenant_id',
        'username',
        'email',
        'email_verified_at',
        'password',
        'password_fingerprint',
    ];

    protected $hidden = [
        'tenant_id',
        'password',
        'password_fingerprint',
        'remember_token',
    ];

    public function canAccessPanel(Panel $panel): bool
    {
        return match ($panel->getId()) {
            'admin' => $this->hasRole('super-admin') || $this->hasRole('admin'),
            'user'  => $this->hasAnyRole(['super-admin', 'admin', 'reseller']),
            default => false,
        };
    }

    public function getFilamentName(): string
    {
        return $this->username ?? $this->email;
    }

    public function getAuthifyLogUsername(): string
    {
        return $this->username;
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class);
    }

    public function getTenants(Panel $panel): Collection
    {
        return $this->teams;
    }

    public function canAccessTenant(Model $tenant): bool
    {
        return $this->teams()->whereKey($tenant)->exists();
    }

    /**
     * @return BelongsToMany<Tenant, $this>
     */
    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class);
    }

    /**
     * @return Attribute<string, string>
     */
    protected function email(): Attribute
    {
        return Attribute::make(
            set: fn(string $value) => Str::lower(mb_trim($value)),
        );
    }

    public function preferredLocale(): string
    {
        return 'fa';
    }

    /**
     * @return MorphMany<Media, $this>
     */
    public function multimedia(): MorphMany
    {
        return $this->media();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaConversion('thumb-table')
            ->width(48)
            ->format('webp');

        $this->addMediaConversion('small')
            ->width(300)
            ->format('webp');

        $this->addMediaConversion('medium')
            ->width(500)
            ->format('webp');

        $this->addMediaConversion('large')
            ->width(800)
            ->format('webp');

        $this->addMediaConversion('extra-large')
            ->width(1200)
            ->format('webp');
    }

    public function registerMediaConversions(?Media $media = null): void {}

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logFillable()->logExcept(['id']);
    }
}
