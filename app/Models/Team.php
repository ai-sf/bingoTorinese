<?php

namespace App\Models;

use Lepton\Boson\Model;
use Lepton\Boson\DataTypes\{CharField, DateTimeField, ReverseRelation, PrimaryKey};

class Team extends Model
{
    protected static $tableName = "bingo_teams";

    #[PrimaryKey] protected $id;
    #[CharField()] protected $name;
    #[ReverseRelation(TeamHasPhoto::class, "photo")] protected $teamphotos;
    #[ReverseRelation(TeamHasChallenge::class, "challenge")] protected $teamchallenges;
    #[ReverseRelation(BingoUser::class, "team")]   protected $users;
}
