<?php

namespace App\Models;

use Lepton\Boson\Model;
use Lepton\Boson\DataTypes\{CharField, NumberField, ForeignKey, PrimaryKey, ReverseRelation, JSONField};

class Challenge extends Model
{
    protected static $tableName = "bingo_challenges";

    #[PrimaryKey] protected $id;
    #[CharField(max_length: 128)] protected $title;
    #[CharField(max_length: 256)] protected $description;
    #[JSONField(null: true)] protected $achieve_condition;
    #[NumberField] protected $show_photo;

}

