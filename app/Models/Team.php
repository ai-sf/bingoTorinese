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
    #[ReverseRelation(User::class, "team")]   protected $users;

    public function calculatePoints() {
        // 1) Sfide completate
        $challenges = TeamHasChallenge::filter(team: $this);
        $base_points = 0;
        $multiplier_count = 0;
        $processed_challenges = [];
        $challenges_breakdown = [];

        foreach($challenges as $team_challenge){
            $c = $team_challenge->challenge;
            $c_id = $c->id;
            if (!isset($processed_challenges[$c_id])) $processed_challenges[$c_id] = 0;
            
            if ($processed_challenges[$c_id] < $c->max_completions) {
                $base_points += $c->points;
                $processed_challenges[$c_id]++;
                
                if (!isset($challenges_breakdown[$c_id])) {
                    // Per sfide con foto, cerca le foto caricate
                    $photos = [];
                    if ($c->show_photo) {
                        $condition = json_decode($c->achieve_condition);
                        if ($condition && $condition->type == "photo") {
                            $target = is_array($condition->target) ? $condition->target : [$condition->target];
                            foreach ($target as $t) {
                                $photo = Photo::get($t);
                                if ($photo) {
                                    $thps = TeamHasPhoto::filter(team: $this, photo: $photo);
                                    foreach ($thps as $thp) {
                                        $photos[] = str_replace('resources/', '', $thp->path);
                                    }
                                }
                            }
                        }
                    }
                    $challenges_breakdown[$c_id] = [
                        'title' => $c->title,
                        'points' => 0,
                        'count' => 0,
                        'has_photo' => $c->show_photo ? true : false,
                        'photos' => $photos
                    ];
                }
                $challenges_breakdown[$c_id]['points'] += $c->points;
                $challenges_breakdown[$c_id]['count']++;
            }
        }

        // 2) Per ogni foto caricata: se fa parte di un gruppo
        $uploaded_photos = TeamHasPhoto::filter(team: $this);
        $photo_ids = [];
        $locales_breakdown = [];
        $multipliers_breakdown = [];

        foreach($uploaded_photos as $up) {
            $p = $up->photo;
            if (!in_array($p->id, $photo_ids)) {
                $photo_ids[] = $p->id;
                $group = null;
                try {
                    $group = $p->group;
                } catch (\Exception $e) {
                    $group = null;
                }

                if ($group) {
                    $photoPath = str_replace('resources/', '', $up->path);
                    if ($group->is_multiplier) {
                        $multiplier_count++;
                        $multipliers_breakdown[] = [
                            'title' => $p->title . " (" . $group->name . ")",
                            'points' => "+10%",
                            'photo' => $photoPath
                        ];
                    } else {
                        $base_points += 10;
                        $locales_breakdown[] = [
                            'title' => $p->title . " (" . $group->name . ")",
                            'points' => 10,
                            'photo' => $photoPath
                        ];
                    }
                }
            }
        }

        // 3) Punti magici
        $magic_points_threshold = intval(BingoSettings::get(name: "magic_points_threshold")->value);
        $magic_points_final = intval(BingoSettings::get(name: "magic_points_final")->value);
        if ($base_points == $magic_points_threshold){
            $total_points = $magic_points_final;
        } else {
            $total_points = $base_points * (1.0 + (0.10 * $multiplier_count));
        }

        return [
            "points" => round($total_points),
            "base_points" => $base_points,
            "multiplier_count" => $multiplier_count,
            "breakdown" => array_values($challenges_breakdown),
            "locales" => $locales_breakdown,
            "multipliers" => $multipliers_breakdown
        ];
    }
}
