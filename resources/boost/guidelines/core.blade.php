## Vendra User

The `misaf/vendra-user` package owns user management, authentication, media handling, and multi-tenant membership and the Filament admin UI for users. Optional OAuth social login lives in the separate `misaf/vendra-socialite` add-on and must not be re-coupled here.

### Standards

- Keep user domain code inside `packages/vendra-user` using the `Misaf\VendraUser` namespace.
- Use this package for models, migrations, factories, seeders, policies, permission enums, observers, Filament resources, translations, config, and package bootstrapping.
- Follow existing model conventions where they apply: tenant ownership, translated `name` / `description` / `slug`, soft deletes, sortable `position`, media collections, factories, and typed relationships.
- Tenant awareness is owned by `misaf/vendra-support` via the bound `TenantResolver`; consume it through `Misaf\VendraSupport\Support\TenantAwareness` and `BelongsToTenant`, not a `tenant_aware` config toggle.
- This module is multi-tenancy core: the `User` model implements tenant membership (`HasTenants`, `teams()` / `tenants()`), so it intentionally references the concrete tenant provider. Keep that coupling deliberate and minimal — new domain code that is not about tenant membership should still derive tenancy from the support layer and avoid `Misaf\VendraTenant`.
- Keep Filament resources thin by delegating forms to `Schemas/*Form.php` and tables to `Tables/*Table.php`.
- Follow Laravel comment style: document with PHPDoc (array shapes, generics, `@see`) and reserve inline comments for genuinely complex logic. Match the surrounding file and do not add comments that restate the code.
- Integrate optional user tags only through `HasOptionalTags` and `TagIntegration` from `misaf/vendra-support`. Reserve the `user` type; never import Vendra Tagger or Spatie Tags, and keep Tagger in Composer `suggest` rather than `require`.
- Add or update Pest tests for policy coverage, config/navigation behavior, translation parity, model contracts, and user-visible Filament behavior.
- Keep tests purposeful and prevent unnecessary ones: cover behavior, contracts, and edge cases — not framework internals or trivially typed code. Do not duplicate coverage a focused test already proves, and do not add throwaway verification scripts when a test fits.
- Keep Pest architecture tests in `tests/ArchTest.php`: the `php`, `security`, and `laravel` presets plus the standard presets (this module is multi-tenancy core, so it does not assert a `not->toUse('Misaf\VendraTenant')` expectation).
