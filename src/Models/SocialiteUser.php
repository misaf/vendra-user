<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Models;

use DutchCodingCompany\FilamentSocialite\Models\SocialiteUser as DutchCodingCompanySocialiteUser;
use Misaf\VendraTenant\Traits\BelongsToTenant;

final class SocialiteUser extends DutchCodingCompanySocialiteUser
{
    use BelongsToTenant;
}
