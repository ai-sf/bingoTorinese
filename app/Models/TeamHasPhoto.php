<?php

namespace App\Models;

use Lepton\Boson\Model;
use Lepton\Boson\DataTypes\{CharField, NumberField, ForeignKey, ManyToMany, PrimaryKey, ReverseRelation};

class TeamHasPhoto extends Model
{
    protected static $tableName = "bingo_team_has_photo";

    #[PrimaryKey] protected $id;
    #[ForeignKey(Team::class)] protected $team;
    #[ForeignKey(Photo::class)] protected $photo;
    #[CharField(max_length: 128)] protected $path;
}
