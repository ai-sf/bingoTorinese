<?php

namespace App\Models;

use Lepton\Boson\Model;
use Lepton\Boson\DataTypes\{CharField, DateTimeField, ForeignKey, PrimaryKey};

class KingLocation extends Model
{
    protected static $tableName = "bingo_king_locations";

    #[PrimaryKey] protected $id;
    #[ForeignKey(King::class)] protected $king;
    #[CharField(max_length: 256)] protected $path;
    #[CharField(max_length: 256, null: true)] protected $location_name;
    #[DateTimeField] protected $timestamp;
}
