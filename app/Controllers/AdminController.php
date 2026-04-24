<?php

namespace App\Controllers;

use Lepton\Core\Application;
use Lepton\Controller\BaseController;
use Lepton\Boson\Model;
use Liquid\{Liquid, Template};
use Lepton\Authenticator\AccessControlAttributes\LoginRequired;
use Lepton\Authenticator\UserAuthenticator;
use App\Models\{Challenge, PhotoGroup, Team, Photo, BingoSettings, TeamHasChallenge, TeamHasPhoto, User, KingLocation};

class AdminController extends BaseController
{

    public string $baseLink = "admin";
    protected array $default_parameters = [
        "nav" => [
            [
                "title" => "Dashboard",
                "link" => "admin",
                "icon" => "speedometer2",
                "min_level" => 3
            ],
            [
                "title" => "Team",
                "link" => "admin/teams",
                "icon" => "people",
                "min_level" => 3
            ],
            [
                "title" => "Re",
                "link" => "admin/kings",
                "icon" => "person-badge",
                "min_level" => 3
            ],
            [
                "title" => "Admin",
                "link" => "admin/admins",
                "icon" => "shield-lock",
                "min_level" => 3
            ],
            [
                "title" => "Gioco",
                "link" => "admin",
                "icon" => "controller",
                "min_level" => 3,
                "subnav" => [
                    ["title" => "Sfide", "link" => "admin/challenges"],
                    ["title" => "Manuale", "link" => "admin/manual"],
                    ["title" => "Gruppi", "link" => "admin/groups"]
                ]
            ],
            [
                "title" => "Classifica",
                "link" => "standings",
                "icon" => "trophy",
                "min_level" => 3
            ],
            [
                "title" => "Impostazioni",
                "link" => "admin/settings",
                "icon" => "gear",
                "min_level" => 3
            ]
        ]
    ];

    #[LoginRequired(level: 3)]
    public function admin(){
        $user = (new UserAuthenticator())->getLoggedUser();
        if ($user->level < 3) {
            return $this->render(
                "Site/login",
            );
        }
        $is_open  = BingoSettings::get(name: "is_open");

        return $this->render("admin", [
            "is_open" => $is_open->value
        ]);
    }

    #[LoginRequired(level: 3)]
    public function adminTeams() {
        $users = User::filter(level: 1);
        return $this->render("admin_users", [
            "title" => "Gestione Team",
            "users" => $users,
            "target_level" => 1
        ]);
    }

    #[LoginRequired(level: 3)]
    public function adminKings() {
        $users = User::filter(level: 2);
        return $this->render("admin_users", [
            "title" => "Gestione Account Re",
            "users" => $users,
            "target_level" => 2
        ]);
    }

    #[LoginRequired(level: 3)]
    public function adminAdmins() {
        $users = User::filter(level: 3);
        return $this->render("admin_users", [
            "title" => "Gestione Account Admin",
            "users" => $users,
            "target_level" => 3
        ]);
    }

    #[LoginRequired(level: 3)]
    public function adminChallenges(){
        $user = (new UserAuthenticator())->getLoggedUser();
        if ($user->level < 3) {
            return $this->render("Site/login");
        }

        $challenges = iterator_to_array(\App\Models\Challenge::all());
        $photos = iterator_to_array(\App\Models\Photo::all());
        $photogroups = iterator_to_array(\App\Models\PhotoGroup::all());

        return $this->render("admin_challenges", [
            "challenges" => $challenges,
            "photos" => $photos,
            "photogroups" => $photogroups
        ]);
    }

    #[LoginRequired(level: 3)]
    public function adminAddAccount() {
        $user = (new UserAuthenticator())->getLoggedUser();
        if ($user->level < 3) return $this->redirect("admin");

        $username = $_POST['username'];
        $password = $_POST['password'];
        $display_name = $_POST['display_name'] ?? $username;
        $level = intval($_POST['level'] ?? 1);

        $new_user = User::new();
        $new_user->username = $username;
        $new_user->token = password_hash($password, PASSWORD_DEFAULT);
        $new_user->level = $level;

        if ($level == 1) {
            $new_team = Team::new();
            $new_team->name = $display_name;
            $new_team->save();
            $new_user->team = $new_team;
        }

        $new_user->save();

        if ($level == 2) {
            $new_king = \App\Models\King::new();
            $new_king->user = $new_user;
            $new_king->name = $display_name;
            $new_king->save();
        }

        if (isset($_SERVER['HTTP_REFERER'])) {
            return $this->redirect($_SERVER['HTTP_REFERER'], parse: false);
        }
        return $this->redirect("admin");
    }

    #[LoginRequired(level: 3)]
    public function adminRemoveAccount($id) {
        $user = (new UserAuthenticator())->getLoggedUser();
        if ($user->level < 3) return $this->redirect("admin");

        $target = User::get($id);
        if ($target && $target->id != $user->id) {
            // Cleanup related models if necessary
            if ($target->level == 1 && $target->team) {
                // Team is kept? Usually yes, or we can delete it too. 
                // $target->team->delete();
            }
            $target->delete();
        }
        
        if (isset($_SERVER['HTTP_REFERER'])) {
            return $this->redirect($_SERVER['HTTP_REFERER'], parse: false);
        }
        return $this->redirect("admin");
    }

    #[LoginRequired(level: 3)]
    public function adminSetPassword($id) {
        $user = (new UserAuthenticator())->getLoggedUser();
        if ($user->level < 3) return $this->redirect("admin");

        $target = User::get($id);
        if ($target) {
            $target->token = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $target->save();
        }
        
        if (isset($_SERVER['HTTP_REFERER'])) {
            return $this->redirect($_SERVER['HTTP_REFERER'], parse: false);
        }
        return $this->redirect("admin");
    }


    #[LoginRequired(level: 3)]
    public function toggleOpen(){
        $user = (new UserAuthenticator())->getLoggedUser();
        if($user->level < 3){
            return $this->render(
                "Site/toaster",
                ["message" => "Non hai i permessi per eseguire questa azione!"],
                headers: ['HX-Trigger' => 'showToast']
            );
        }
        $is_open  = BingoSettings::get(name: "is_open");
        $is_open->value = intval(!$is_open->value);
        $is_open->save();
        return $this->render(
            "Site/toaster",
            ["message" => "Stato del gioco cambiato con successo!"],
            headers: ['HX-Trigger' => 'showToast']
        );
    }

    #[LoginRequired(level: 3)]
    public function adminAddChallenge() {
        $user = (new UserAuthenticator())->getLoggedUser();
        if ($user->level < 3) return $this->redirect("admin");

        $challenge = \App\Models\Challenge::new();
        $challenge->title = $_POST['title'];
        $challenge->description = $_POST['description'];
        $challenge->points = $_POST['points'];
        $challenge->max_completions = $_POST['max_completions'] ?? 1;
        $challenge->is_consecutive = isset($_POST['is_consecutive']) ? 1 : 0;
        $challenge->show_photo = isset($_POST['show_photo']) ? 1 : 0;
        
        if (!empty($_POST['visibility_time'])) {
            $dateTime = new \DateTime($_POST['visibility_time']);
            $challenge->visibility_time = $dateTime->format('Y-m-d H:i:s');
        }
        
        if ($challenge->show_photo) {
            $photo = \App\Models\Photo::new();
            $photo->title = $challenge->title;
            // group is automatically left null explicitly 
            $photo->save();
            $challenge->achieve_condition = json_encode(["type" => "photo", "target" => $photo->id]);
        } elseif (!empty($_POST['achieve_condition'])) {
            $challenge->achieve_condition = $_POST['achieve_condition'];
        }

        $challenge->save();
        return $this->redirect("admin/challenges");
    }

    #[LoginRequired(level: 3)]
    public function adminEditChallenge($id) {
        $user = (new UserAuthenticator())->getLoggedUser();
        if ($user->level < 3) return $this->redirect("admin");

        $challenge = \App\Models\Challenge::get($id);
        if (!$challenge) return $this->redirect("admin");

        $challenge->title = $_POST['title'];
        $challenge->description = $_POST['description'];
        $challenge->points = $_POST['points'];
        $challenge->max_completions = $_POST['max_completions'] ?? 1;
        $challenge->is_consecutive = isset($_POST['is_consecutive']) ? 1 : 0;
        $challenge->show_photo = isset($_POST['show_photo']) ? 1 : 0;
        
        if (!empty($_POST['visibility_time'])) {
            $dateTime = new \DateTime($_POST['visibility_time']);
            $challenge->visibility_time = $dateTime->format('Y-m-d H:i:s');
        } else {
            $challenge->visibility_time = null;
        }
        
        if ($challenge->show_photo) {
            $cond = json_decode($challenge->achieve_condition);
            if (!$cond || $cond->type !== 'photo') {
                $photo = \App\Models\Photo::new();
                $photo->title = $challenge->title;
                $photo->save();
                $challenge->achieve_condition = json_encode(["type" => "photo", "target" => $photo->id]);
            }
        } elseif (!empty($_POST['achieve_condition'])) {
            $challenge->achieve_condition = $_POST['achieve_condition'];
        } else {
            $challenge->achieve_condition = "";
        }

        $challenge->save();
        return $this->redirect("admin/challenges");
    }

    #[LoginRequired(level: 3)]
    public function adminRemoveChallenge($id) {
        $user = (new UserAuthenticator())->getLoggedUser();
        if ($user->level < 3) return $this->redirect("admin");

        $target = \App\Models\Challenge::get($id);
        if ($target) {
            $target->delete();
        }
        
        return $this->redirect("admin/challenges");
    }


    #[LoginRequired(level: 3)]
    public function standings(){
        $teams = Team::all();
        $standings = [];
        foreach($teams as $team){
            
            $team_points = $team->calculatePoints();
 
            $standings[] = [
                "team" => $team, 
                "points" => $team_points["points"],
                "base_points" => $team_points["base_points"],
                "multiplier_count" => $team_points["multiplier_count"],
                "breakdown" => $team_points["breakdown"],
                "locales" => $team_points["locales"],
                "multipliers" => $team_points["multipliers"]
            ];
        }

        usort($standings, function($a, $b){
            return ($b["points"] > $a["points"]) ? 1 : (($b["points"] < $a["points"]) ? -1 : 0);
        });
        return $this->render("admin_standings", ["standings" => $standings]);
    }

    #[LoginRequired(level: 3)]
    public function adminGroups(){
        $user = (new UserAuthenticator())->getLoggedUser();
        if ($user->level < 3) return $this->render("Site/login");
        
        $photogroups = \App\Models\PhotoGroup::all();
        return $this->render("admin_groups", ["photogroups" => $photogroups]);
    }

    #[LoginRequired(level: 3)]
    public function adminAddGroup() {
        $user = (new UserAuthenticator())->getLoggedUser();
        if ($user->level < 3) return $this->redirect("admin");

        $group = \App\Models\PhotoGroup::new();
        $group->name = $_POST['name'];
        $group->is_multiplier = isset($_POST['is_multiplier']) ? 1 : 0;
        $group->save();

        return $this->redirect("admin/groups");
    }

    #[LoginRequired(level: 3)]
    public function adminEditGroup($id) {
        $user = (new UserAuthenticator())->getLoggedUser();
        if ($user->level < 3) return $this->redirect("admin");

        $group = \App\Models\PhotoGroup::get($id);
        if ($group) {
            $group->name = $_POST['name'];
            $group->is_multiplier = isset($_POST['is_multiplier']) ? 1 : 0;
            $group->save();
        }
        
        return $this->redirect("admin/groups");
    }

    #[LoginRequired(level: 3)]
    public function adminRemoveGroup($id) {
        $user = (new UserAuthenticator())->getLoggedUser();
        if ($user->level < 3) return $this->redirect("admin");

        $target = \App\Models\PhotoGroup::get($id);
        if ($target) {
            $target->delete();
        }
        
        return $this->redirect("admin/groups");
    }

    #[LoginRequired(level: 3)]
    public function adminGroupPhotos($group_id) {
        $user = (new UserAuthenticator())->getLoggedUser();
        if ($user->level < 3) return $this->redirect("admin");

        $group = \App\Models\PhotoGroup::get($group_id);
        if (!$group) return $this->redirect("admin/groups");

        $photos = \App\Models\Photo::filter(group: $group);

        return $this->render("admin_group_photos", [
            "photogroup" => $group,
            "photos" => $photos
        ]);
    }

    #[LoginRequired(level: 3)]
    public function adminAddPhoto($group_id) {
        $user = (new UserAuthenticator())->getLoggedUser();
        if ($user->level < 3) return $this->redirect("admin");

        $group = \App\Models\PhotoGroup::get($group_id);
        if ($group) {
            $photo = \App\Models\Photo::new();
            $photo->title = $_POST['title'];
            $photo->group = $group;
            $photo->save();
        }

        return $this->redirect("admin/groupPhotos/" . $group_id);
    }

    #[LoginRequired(level: 3)]
    public function adminEditPhoto($photo_id, $group_id) {
        $user = (new UserAuthenticator())->getLoggedUser();
        if ($user->level < 3) return $this->redirect("admin");

        $photo = \App\Models\Photo::get($photo_id);
        if ($photo) {
            $photo->title = $_POST['title'];
            $photo->save();
        }

        return $this->redirect("admin/groupPhotos/" . $group_id);
    }

    #[LoginRequired(level: 3)]
    public function adminRemovePhoto($photo_id, $group_id) {
        $user = (new UserAuthenticator())->getLoggedUser();
        if ($user->level < 3) return $this->redirect("admin");

        $photo = \App\Models\Photo::get($photo_id);
        if ($photo) {
            $photo->delete();
        }

        return $this->redirect("admin/groupPhotos/" . $group_id);
    }

    #[LoginRequired(level: 3)]
    public function adminManual() {
        $teams = Team::all();
        $all_challenges = Challenge::all();
        $manual_challenges = [];
        foreach ($all_challenges as $c) {
            $cond = json_decode($c->achieve_condition);
            if ($cond && $cond->type === 'manual') {
                $manual_challenges[] = $c;
            }
        }
        
        $team_challenges = [];
        foreach ($teams as $t) {
            $team_challenges[$t->id] = [];
            foreach ($manual_challenges as $c) {
                $has_challenge = TeamHasChallenge::filter(team: $t, challenge: $c)->count();
                if($has_challenge){
                    array_push($team_challenges[$t->id], $c->id); 
                }
            }
        }

        return $this->render("admin_manual", [
            "teams" => $teams,
            "manual_challenges" => $manual_challenges,
            "team_challenges" => $team_challenges
        ]);
    }

    #[LoginRequired(level: 3)]
    public function adminToggleManual() {
        $team_id = intval($_POST['team_id'] ?? 0);
        $challenge_id = intval($_POST['challenge_id'] ?? 0);
        
        $team = Team::get($team_id);
        $challenge = Challenge::get($challenge_id);
        
        if ($team && $challenge) {
            $has_completed = false;
            $existing = TeamHasChallenge::filter(team: $team, challenge: $challenge);
            foreach ($existing as $e) {
                $e->delete();
                $has_completed = true;
            }
            
            if ($has_completed) {
                $is_completed = false;
            } else {
                $thc = TeamHasChallenge::new(team: $team, challenge: $challenge);
                $thc->save();
                $is_completed = true;
            }

            // We return just the updated toggle switch to HTMX
            return new \Lepton\Http\Response\HttpResponse(200);
        }
        
        return new \Lepton\Http\Response\HttpResponse(400);
    }

    #[LoginRequired(level: 3)]
    public function adminSettings() {
        $settings = iterator_to_array(BingoSettings::all());
        return $this->render("admin_settings", [
            "settings" => $settings
        ]);
    }

    #[LoginRequired(level: 3)]
    public function updateSettings() {
        foreach ($_POST['settings'] as $id => $value) {
            $setting = BingoSettings::get($id);
            if ($setting) {
                $setting->value = $value;
                $setting->save();
            }
        }
        return $this->redirect("admin/settings");
    }
    #[LoginRequired(level: 3)]
    public function adminResetGame() {
        $user = (new UserAuthenticator())->getLoggedUser();
        if ($user->level < 3) return $this->redirect("admin");

        // 1. Delete TeamHasPhoto records and files
        $thps = TeamHasPhoto::all();
        foreach ($thps as $thp) {
            if ($thp->path && file_exists($thp->path)) {
                @unlink($thp->path);
            }
            $thp->delete();
        }

        // 2. Delete KingLocation records and files
        $klocs = KingLocation::all();
        foreach ($klocs as $kloc) {
            if ($kloc->path && file_exists($kloc->path)) {
                @unlink($kloc->path);
            }
            $kloc->delete();
        }

        // 3. Delete all TeamHasChallenge records
        $thcs = TeamHasChallenge::all();
        foreach ($thcs as $thc) {
            $thc->delete();
        }

        return $this->render(
            "Site/toaster",
            ["message" => "Gioco resettato con successo! Tutti i dati e le foto sono stati eliminati."],
            headers: ['HX-Trigger' => 'showToast']
        );
    }
}
