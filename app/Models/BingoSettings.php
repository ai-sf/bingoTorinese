<?php

namespace App\Models;

use Lepton\Boson\Model;
use Lepton\Boson\DataTypes\{CharField, NumberField, ForeignKey, PrimaryKey};

class BingoSettings extends Model
{
    protected static $tableName = "bingo_settings";

    #[PrimaryKey] protected $id;
    #[CharField] protected $name;
    #[CharField] protected $value;
}
