## Vendra User

The `misaf/vendra-user` package owns user management, authentication, media handling, and multi-tenant membership and the Filament admin UI for users. Optional OAuth social login lives in the separate `misaf/vendra-socialite` add-on and must not be re-coupled here.

### Standards

- Keep user domain code inside `packages/vendra-user` using the `Misaf\VendraUser` namespace.
- Use this package for models, migrations, factories, seeders, policies, permission enums, observers, Filament resources, translations, config, and package bootstrapping.
- Follow the concrete models and neighboring files in this package; do not apply translation, media, slug, sorting, or soft-delete patterns unless the affected model already uses them.
- Tenant awareness is owned by `misaf/vendra-support` via the bound `TenantResolver`; consume it through `Misaf\VendraSupport\Support\TenantAwareness` and `BelongsToTenant`, not a `tenant_aware` config toggle.
- The `User` model implements Filament tenant membership (`HasTenants`, `teams()` / `tenants()`) while resolving the tenant model through `BelongsToTenant` from `misaf/vendra-support`. Keep the module independent of concrete providers and never reference `Misaf\VendraTenant`.
- Keep Filament resources thin by delegating forms to `Schemas/*Form.php` and tables to `Tables/*Table.php`.
- Follow Laravel comment style: document with PHPDoc (array shapes, generics, `@see`) and reserve inline comments for genuinely complex logic. Match the surrounding file and do not add comments that restate the code.
- Integrate optional user tags only through `HasOptionalTags` and `TagIntegration` from `misaf/vendra-support`. Reserve the `user` type; never import Vendra Tagger or Spatie Tags, and keep Tagger in Composer `suggest` rather than `require`.
- Add or update Pest tests for policy coverage, config/navigation behavior, translation parity, model contracts, and user-visible Filament behavior.
- Keep tests purposeful and prevent unnecessary ones: cover behavior, contracts, and edge cases — not framework internals or trivially typed code. Do not duplicate coverage a focused test already proves, and do not add throwaway verification scripts when a test fits.
- Keep Pest architecture tests in `tests/ArchTest.php`: the `php`, `security`, and `laravel` presets plus `arch()->expect('Misaf\VendraUser')->not->toUse('Misaf\VendraTenant')`.
