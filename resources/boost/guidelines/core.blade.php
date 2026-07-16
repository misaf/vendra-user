## Vendra User

The `misaf/vendra-user` package owns user management, authentication, media handling, and multi-tenant membership and the Filament admin UI for users. Optional OAuth social login lives in the separate `misaf/vendra-socialite` add-on and must not be re-coupled here.

### Standards

### Translatable Persistence

- Making a persisted model field translatable is an explicit domain choice unless this package already requires it.
- Every field listed in a model's `$translatable` array must definitely use a JSON database column. Keep its model traits/casts, factories, validation, Filament locale UI, API serialization, and tests translation-aware.
- A field not listed in `$translatable` must use the appropriate scalar database type and must not use Spatie Translatable, translatable slug traits, locale switchers, translated callbacks, or translation-shaped array data.

- Keep user domain code inside `packages/vendra-user` using the `Misaf\VendraUser` namespace.
- Use this package for models, migrations, factories, seeders, policies, permission enums, observers, Filament resources, translations, config, and package bootstrapping.
- Follow the concrete models and neighboring files in this package; do not apply translation, media, slug, sorting, or soft-delete patterns unless the affected model already uses them.
- Tenant awareness is owned by `misaf/vendra-support` via the bound `TenantResolver`; consume it through `Misaf\VendraSupport\Support\TenantAwareness` and `BelongsToTenant`, not a `tenant_aware` config toggle.
- The `User` model implements Filament tenant membership (`HasTenants`, `teams()` / `tenants()`) while resolving the tenant model through `BelongsToTenant` from `misaf/vendra-support`. Keep the module independent of concrete providers and never reference `Misaf\VendraTenant`.
- Keep Filament resources thin by delegating forms to `Schemas/*Form.php` and tables to `Tables/*Table.php`.
- Because `UserResource` declares a `$cluster`, keep its complete resource tree under `src/Filament/Clusters/Resources/`, use the matching `Misaf\VendraUser\Filament\Clusters\Resources` namespace, and keep plugin discovery aligned. Any future resource without a cluster must instead live under `src/Filament/Resources/`.
- Keep `UserResource` in `CustomersCluster` and group it under `vendra-user::navigation.user_management`; optional user-domain resources such as User Profile must use the same cluster and navigation group.
- Follow Laravel comment style: document with PHPDoc (array shapes, generics, `@see`) and reserve inline comments for genuinely complex logic. Match the surrounding file and do not add comments that restate the code.
- Tag-consuming models must use `Misaf\VendraSupport\Traits\HasOptionalTags` as the single source of their `tags()` relationship and pivot metadata. Keep the package tag-agnostic: define a stable package-owned tag type, use `TagIntegration` for availability and UI integration, never import the concrete Vendra Tagger model/provider or define the relationship through Spatie `HasTags`, and keep Tagger in Composer `suggest` rather than `require`.
- Add or update Pest tests for policy coverage, config/navigation behavior, translation parity, model contracts, and user-visible Filament behavior.
- Keep tests purposeful and prevent unnecessary ones: cover behavior, contracts, and edge cases — not framework internals or trivially typed code. Do not duplicate coverage a focused test already proves, and do not add throwaway verification scripts when a test fits.
- Keep Pest architecture tests in `tests/ArchTest.php`: the `php`, `security`, and `laravel` presets plus `arch()->expect('Misaf\VendraUser')->not->toUse('Misaf\VendraTenant')`.
