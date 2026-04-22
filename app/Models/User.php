<?php

namespace App\Models;

use Lepton\Boson\Model;
use Lepton\Boson\DataTypes\{CharField, DateTimeField, NumberField, PrimaryKey, ReverseRelation, ForeignKey};

class User extends Model
{
    protected static $tableName = "bingo_users";

    #[PrimaryKey] protected $id;
    #[CharField] protected $username;
    #[CharField] protected $token;
    #[ForeignKey(Team::class)] protected $team;
    #[NumberField] protected $level;
}
