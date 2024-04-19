<?php

namespace App\Models;

use Lepton\Boson\Model;
use Lepton\Boson\DataTypes\{CharField, DateTimeField, NumberField, PrimaryKey, ReverseRelation};

class User extends Model
{
    protected static $tableName = "users";

    #[PrimaryKey] protected $id;
    #[CharField] protected $name;
    #[CharField] protected $surname;
    #[CharField] protected $email;
    #[CharField] protected $token;
    #[ReverseRelation(BingoUser::class, "user")] protected $bingoUser;
}
