<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Auth;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Misaf\VendraSupport\Tenancy\TenantSchema;

/**
 * Platform-scoped Eloquent user provider.
 *
 * Tenant users may share an email with a platform identity, so the
 * `console` and `reseller` guards must never resolve a row that belongs
 * to a tenant. Every lookup funnels through {@see EloquentUserProvider::newModelQuery()},
 * so constraining that single point scopes identifier, credential, and
 * remember-token retrieval to `tenant_id IS NULL` without duplicating the
 * framework's credential handling, hashing, or rehash behavior.
 */
final class PlatformUserProvider extends EloquentUserProvider
{
    /**
     * @template TModel of Model
     *
     * @param  TModel|null  $model
     * @return Builder<TModel>
     */
    protected function newModelQuery($model = null)
    {
        /** @var Model&Authenticatable $modelInstance */
        $modelInstance = $model ?? $this->createModel();

        $query = parent::newModelQuery($modelInstance);

        /*
        | No tenant column means no tenant rows exist to be confused with a
        | platform identity, so returning the unscoped query is the correct
        | answer rather than a fail-open hole: `users` is deliberately absent
        | from the TenantTableRegistry, so `vendra-tenant:enable` never
        | retrofits the column onto it, and an install that migrated without a
        | tenant provider will never grow one. Failing closed here would
        | instead deny every console and reseller login on such an install with
        | no recovery path.
        */
        if (! TenantSchema::hasTenantColumn($modelInstance->getTable())) {
            return $query;
        }

        return $query->whereNull($modelInstance->qualifyColumn(TenantSchema::column()));
    }
}
