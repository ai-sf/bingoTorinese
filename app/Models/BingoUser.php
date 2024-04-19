<?php

namespace App\Models;

use Lepton\Boson\Model;
use Lepton\Boson\DataTypes\{CharField, NumberField, ForeignKey, PrimaryKey};

class BingoUser extends Model
{
    protected static $tableName = "bingo_users";

    #[PrimaryKey] protected $id;
    #[ForeignKey(User::class)] protected $user;
    #[ForeignKey(Team::class)] protected $team;
    #[NumberField] protected $level;
}
