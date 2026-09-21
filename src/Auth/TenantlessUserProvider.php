<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Auth;

use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Misaf\VendraSupport\Tenancy\TenantSchema;

/**
 * Tenant users may share a tenantless user's email, so the console and reseller
 * guards scope {@see EloquentUserProvider::newModelQuery()}.
 */
final class TenantlessUserProvider extends EloquentUserProvider
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
        | tenantless identity, so returning the unscoped query is the correct
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
