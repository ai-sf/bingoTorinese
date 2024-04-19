<?php

namespace App\Models;

use Lepton\Boson\Model;
use Lepton\Boson\DataTypes\{CharField, NumberField, ForeignKey, PrimaryKey, ReverseRelation};

class PhotoGroup extends Model
{
    protected static $tableName = "bingo_photo_groups";

    #[PrimaryKey] protected $id;
    #[CharField(max_length: 128)] protected $name;
    #[ReverseRelation(Photo::class, "group")] protected $photos;
}
