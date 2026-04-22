<?php

namespace App\Models;

use Lepton\Boson\Model;
use Lepton\Boson\DataTypes\{CharField, ForeignKey, PrimaryKey, ReverseRelation};

class King extends Model
{
    protected static $tableName = "bingo_kings";

    #[PrimaryKey] protected $id;
    #[ForeignKey(User::class)] protected $user;
    #[CharField(max_length: 256)] protected $name;
    #[ReverseRelation(KingLocation::class, "king")] protected $locations;
}
