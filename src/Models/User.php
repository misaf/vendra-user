<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasName;
use Filament\Models\Contracts\HasTenants;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
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
use Misaf\VendraMultimedia\Concerns\HasDefaultMediaConversions;
use Misaf\VendraPermission\Enums\RoleEnum;
use Misaf\VendraSupport\Capabilities\HasOptionalTags;
use Misaf\VendraSupport\Contracts\ShouldLogActivity;
use Misaf\VendraSupport\Tenancy\BelongsToTenant;
use Misaf\VendraUser\Database\Factories\UserFactory;
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
#[Fillable(['tenant_id', 'username', 'email', 'email_verified_at', 'password', 'password_fingerprint'])]
#[Hidden(['tenant_id', 'password', 'password_fingerprint', 'remember_token', 'active_email_guard'])]
#[UseFactory(UserFactory::class)]
final class User extends Authenticatable implements
    FilamentUser,
    HasLocalePreference,
    HasName,
    MustVerifyEmail,
    HasTenants,
    HasMedia,
    ShouldLogActivity
{
    use BelongsToTenant;

    use HasDefaultMediaConversions, InteractsWithMedia {
        HasDefaultMediaConversions::registerMediaConversions insteadof InteractsWithMedia;
    }

    /** @use HasFactory<UserFactory> */
    use HasFactory;
    use HasFeatures;
    use HasOptionalTags;
    use HasRoles;
    use Notifiable;
    use SoftDeletes;
    public const string TAG_TYPE = 'user';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'id'                   => 'integer',
            'tenant_id'            => 'integer',
            'username'             => 'string',
            'email'                => 'string',
            'email_verified_at'    => 'datetime',
            'password'             => 'string',
            'password_fingerprint' => 'string',
            'remember_token'       => 'string',
        ];
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return match ($panel->getId()) {
            'admin' => $this->hasRole(RoleEnum::Admin),
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

    /**
     * @return BelongsToMany<Model, $this>
     */
    public function teams(): BelongsToMany
    {
        return $this->tenants();
    }

    /**
     * @return Collection<int, Model>
     */
    public function getTenants(Panel $panel): Collection
    {
        return $this->teams;
    }

    public function canAccessTenant(Model $tenant): bool
    {
        return $this->teams()->whereKey($tenant)->exists();
    }

    /**
     * The tenants this user may sign into.
     *
     * The pivot is inferred from the configured tenant model rather than named,
     * so a Store tenant joins through `store_user` and a Company tenant through
     * `company_user` without this package knowing either.
     *
     * @return BelongsToMany<Model, $this>
     */
    public function tenants(): BelongsToMany
    {
        return $this
            ->belongsToMany($this->tenantModelClass())
            ->withTimestamps();
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

    protected function tagType(): string
    {
        return self::TAG_TYPE;
    }

}
