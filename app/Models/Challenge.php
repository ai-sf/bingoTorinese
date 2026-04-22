<?php

namespace App\Models;

use Lepton\Boson\Model;
use Lepton\Boson\DataTypes\{CharField, NumberField, ForeignKey, PrimaryKey, ReverseRelation, JSONField, DateTimeField};

class Challenge extends Model
{
    protected static $tableName = "bingo_challenges";

    #[PrimaryKey] protected $id;
    #[CharField(max_length: 128)] protected $title;
    #[CharField(max_length: 256)] protected $description;
    #[JSONField(null: true)] protected $achieve_condition;
    #[NumberField] protected $show_photo;
    #[NumberField(default: 0)] protected $points;
    #[NumberField(default: 1)] protected $max_completions;
    #[NumberField(default: 0)] protected $is_consecutive;
    #[DateTimeField(null: true)] protected $visibility_time;

}

