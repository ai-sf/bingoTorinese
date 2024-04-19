<?php

namespace App\Models;

use Lepton\Boson\Model;
use Lepton\Boson\DataTypes\{CharField, DateTimeField, NumberField, ForeignKey, ManyToMany, PrimaryKey, ReverseRelation};

class TeamHasChallenge extends Model
{
    protected static $tableName = "bingo_team_has_challenge";

    #[PrimaryKey] protected $id;
    #[ForeignKey(Team::class)] protected $team;
    #[ForeignKey(Challenge::class)] protected $challenge;
    #[DateTimeField] protected $timestamp;
}
