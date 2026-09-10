<?php

declare(strict_types=1);

namespace Misaf\VendraUser\Tests\Feature;

use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Table(name: 'tags')]
final class UserTestTag extends Model
{
    use HasFactory;
}
