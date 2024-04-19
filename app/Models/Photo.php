<?php

namespace App\Models;

use Lepton\Boson\Model;
use Lepton\Boson\DataTypes\{CharField, NumberField, ForeignKey, ManyToMany, PrimaryKey, ReverseRelation};

class Photo extends Model
{
    protected static $tableName = "bingo_photos";

    #[PrimaryKey] protected $id;
    #[CharField(max_length: 128)] protected $title;
    #[ForeignKey(PhotoGroup::class, null:true)] protected $group;
    #[ReverseRelation(TeamHasPhoto::class, "photo")] protected $teamphotos;
}
