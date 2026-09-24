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
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Builder;
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
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Laravel\Pennant\Concerns\HasFeatures;
use Misaf\VendraMultimedia\Concerns\HasDefaultMediaConversions;
use Misaf\VendraPermission\Enums\RoleEnum;
use Misaf\VendraSupport\Capabilities\HasOptionalTags;
use Misaf\VendraSupport\Contracts\ShouldLogActivity;
use Misaf\VendraSupport\Tenancy\BelongsToTenant;
use Misaf\VendraSupport\Tenancy\Scopes\TeamScope;
use Misaf\VendraSupport\Tenancy\Scopes\TenantScope;
use Misaf\VendraSupport\Tenancy\TenantSchema;
use Misaf\VendraUser\Database\Factories\UserFactory;
use Misaf\VendraUser\Support\PanelAccessRegistry;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property int $id
 * @property int|null $tenant_id
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
final class User extends Authenticatable implements FilamentUser, HasLocalePreference, HasMedia, HasName, HasTenants, MustVerifyEmail, ShouldLogActivity
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
            'id' => 'integer',
            'tenant_id' => 'integer',
            'username' => 'string',
            'email' => 'string',
            'email_verified_at' => 'datetime',
            'password' => 'string',
            'password_fingerprint' => 'string',
            'remember_token' => 'string',
        ];
    }

    /**
     * Limit the query to tenantless users, such as console and reseller users.
     *
     * A tenant user may hold the same email as a tenantless user, so the tenant
     * scopes come off rather than being left to the ambient tenant context.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    #[Scope]
    protected function tenantless(Builder $query): Builder
    {
        $query->withoutGlobalScopes([TenantScope::class, TeamScope::class]);

        if (! TenantSchema::enabled()) {
            return $query;
        }

        return $query->whereNull($this->qualifyColumn(TenantSchema::column()));
    }

    /**
     * Limit the query to the user every supplied identifier names.
     *
     * With no identifier the query would match every user, so one is required.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     *
     * @throws InvalidArgumentException
     */
    #[Scope]
    protected function identifiedBy(Builder $query, ?string $email = null, ?string $username = null): Builder
    {
        throw_if($email === null && $username === null, InvalidArgumentException::class, 'An email or a username is required to identify a user.');

        return $query
            ->when($email !== null, fn (Builder $query): Builder => $query->where($this->qualifyColumn('email'), $email))
            ->when($username !== null, fn (Builder $query): Builder => $query->where($this->qualifyColumn('username'), $username));
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    #[Scope]
    protected function verified(Builder $query): Builder
    {
        return $query->whereNotNull($this->qualifyColumn('email_verified_at'));
    }

    /**
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    #[Scope]
    protected function unverified(Builder $query): Builder
    {
        return $query->whereNull($this->qualifyColumn('email_verified_at'));
    }

    /**
     * Limit the query to users holding the given tenant's admin role.
     *
     * The role is matched on its tenant column rather than through the ambient
     * tenant context, so the console gets the same answer as the tenant's own
     * panel even though it has no current tenant.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    #[Scope]
    protected function administratorOf(Builder $query, Model $tenant): Builder
    {
        return $query->whereHas('roles', fn (Builder $roles): Builder => $roles
            ->withoutGlobalScopes()
            ->where($roles->qualifyColumn(TenantSchema::column()), $tenant->getKey())
            ->where($roles->qualifyColumn('name'), Config::string('vendra-permission.admin_role'))
            ->where($roles->qualifyColumn('guard_name'), 'web'));
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if ($panel->getId() === 'admin') {
            return $this->hasRole(RoleEnum::Admin);
        }

        /*
        | Console and reseller panel grants live in their own packages, which
        | register a PanelAccessResolver for their panel id. Identity only
        | asks the registry here — the same container lookup the tenant
        | helpers use — so vendra-user never names their tables. A panel no
        | package claimed stays denied.
        */
        return resolve(PanelAccessRegistry::class)->canAccess($this, $panel) ?? false;
    }

    public function getFilamentName(): string
    {
        return $this->username ?? $this->email;
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
     * Get the tenants the user may sign into, through a pivot named after the tenant model.
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
            set: fn (string $value) => Str::lower(mb_trim($value)),
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
