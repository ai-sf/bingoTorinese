<?php

namespace App\Controllers;

use Lepton\Core\Application;
use Lepton\Controller\BaseController;
use Lepton\Boson\Model;
use Liquid\{Liquid, Template};
use Lepton\Authenticator\AccessControlAttributes\LoginRequired;
use Lepton\Authenticator\UserAuthenticator;
use App\Models\{Challenge, PhotoGroup, Team, Photo, BingoSettings, BingoUser, TeamHasChallenge, TeamHasPhoto};

class BingoController extends BaseController
{
    public string $baseLink = "";
    protected array $default_parameters = [
        "nav" => [
            [
                "title" => "Home",
                "link" => "",
                "icon" => "house",
                "min_level" => 1
            ],
            [
                "title" => "Teams",
                "link" => "teams",
                "icon" => "people-fill",
                "min_level" => 1,
            ],
            [
                "title" => "Sfide",
                "link" => "challenges",
                "icon" => "cup-straw",
                "min_level" => 1
            ]

        ]
    ];

    #[LoginRequired(1)]
    public function challenges()
    {

        $is_open = BingoSettings::get(name: "is_open");
        if($is_open->value == 0){
            return $this->render("closed");
        }
        $challenges = Challenge::all();
        $user = (new UserAuthenticator())->getLoggedUser();
        $bingoUser = BingoUser::get(user: $user);

        $team = $bingoUser->team;
        $teamchallenges = TeamHasChallenge::filter(team: $team);

        $completed_challenges = array();
        foreach($teamchallenges as $teamchallenge){
            $completed_challenges[] = $teamchallenge->challenge->id;
        }

        $teamphotos = TeamHasPhoto::filter(team: $team);
        $completed_photos = array();
        foreach($teamphotos as $teamphoto){
            $completed_photos[] = $teamphoto->photo->id;
        }


        $photogroups = PhotoGroup::all();
        return $this->render("challenges", ["team" => $team, "completed_photos" => $completed_photos, "completed_challenges" => $completed_challenges, "challenges" => $challenges, "photogroups" => $photogroups]);
    }


    #[LoginRequired(1)]
    public function index(){
        return $this->render("index");
    }

    #[LoginRequired(1)]
    public function challengeInfo($id)
    {
        $challenge = Challenge::get($id);
        if($challenge->show_photo){
            $condition = $challenge->achieve_condition;
            $photo_id = json_decode($condition)->target;
            $photo = Photo::get($photo_id);
            $user = (new UserAuthenticator())->getLoggedUser();
            $bingoUser = BingoUser::get(user: $user);
            $team = $bingoUser->team;

            $photos = TeamHasPhoto::filter(photo: $photo, team: $team);
            if($photos->count()){
                $photo = $photos->first();
                $has_photo = true;
            } else {
                $photo = null;
                $has_photo = false;
            }
        } else {
            $photo = null;
            $has_photo = false;
        }
        return $this->render("challenge_info", ["has_photo" => $has_photo, "photo" => $photo, "challenge" => $challenge]);
    }

    #[LoginRequired(1)]
    public function photoInfo($id)
    {
        $photo = Photo::get($id);

        $user = (new UserAuthenticator())->getLoggedUser();
        $bingoUser = BingoUser::get(user: $user);
        $team = $bingoUser->team;

        $photos = TeamHasPhoto::filter(photo: $photo, team: $team);
        if ($photos->count()) {
            $uploadedphoto = $photos->first();
            $has_photo = true;
        } else {
            $uploadedphoto = null;
            $has_photo = false;
        }
        return $this->render("photo_info", ["has_photo" => $has_photo, "uploaded_photo" => $uploadedphoto, "challenge" => $photo]);
    }


    public function login()
    {
        if (isset($_POST["email"]) && isset($_POST["password"])) {
            $authenticator = new UserAuthenticator();
            if (!$authenticator->login($_POST["email"], $_POST["password"])) {
                return $this->render(
                    "Site/loginForm",
                    [
                        "login_invalid" => true,
                        "login_message" => "Username e/o password errati"
                    ]
                );
            } else {
                $user = (new UserAuthenticator())->getLoggedUser();
                if(! BingoUser::get(user: $user)){
                    $bingoUser = BingoUser::new();
                    $bingoUser->user = $user;
                    $bingoUser->level = 1;
                    $bingoUser->save();

                }
                if (isset($_SESSION["redirect_url"])) {
                    $response = $this->redirect($_SESSION["redirect_url"], htmx: true, parse: false);
                    unset($_SESSION['redirect_url']);
                    return $response;
                }
                return $this->redirect("", htmx: true);
            }
        }
        return $this->render("Site/login");
    }


    #[LoginRequired(level: 1)]
    public function changeName($team_id)
    {
        $team = Team::get($team_id);
        $team->name = $_POST["team_name"];
        $team->save();
        return $this->render(
            "Site/toaster",
            ["message" => "Nome del team cambiato con successo!"],
            headers: ['HX-Trigger' => 'showToast']
        );
    }

    #[LoginRequired(level: 1)]
    public function logout()
    {
        $authenticator = new UserAuthenticator();
        $authenticator->logout();
        return $this->redirect("");
    }


    #[LoginRequired(level: 1)]
    public function teams()
    {
        $teams = Team::all();
        $user = (new UserAuthenticator())->getLoggedUser();
        $bingoUser = BingoUser::get(user: $user);
        if($bingoUser->team != null)
            return $this->render( 'teams', ["myteam" =>$bingoUser->team->id, "teams", "teams" => $teams]);
        else
            return $this->render( 'teams', ["teams", "teams" => $teams]);
    }


    #[LoginRequired(level: 1)]
    public function listUsers($id)
    {
        $team = Team::get($id);
        return $this->render("team_info", ["team_name" => $team->name, "members" => $team->users]);
    }


    #[LoginRequired(level: 1)]
    public function removeUser()
    {
        $user = (new UserAuthenticator())->getLoggedUser();
        $bingoUser = BingoUser::get(user: $user);
        $bingoUser->team = null;
        $bingoUser->save();
        return $this->render(
            "Site/toaster",
            ["message" => "Sei stato rimosso dal team!"],
            headers: ['HX-Trigger' => 'showToast']
        );
    }


    #[LoginRequired(level: 1)]
    public function addUser($team_id){
        $team = Team::get($team_id);
        $user = (new UserAuthenticator())->getLoggedUser();
        $bingoUser = BingoUser::get(user: $user);
        $bingoUser->team = $team;
        $bingoUser->save();
        return $this->render(
            "Site/toaster",
            ["message" => "Sei stato aggiunto al team!"],
            headers: ['HX-Trigger' => 'showToast']
        );
    }


    #[LoginRequired(level: 1)]
    public function uploadPhotoChallenge()
    {
        $user = (new UserAuthenticator())->getLoggedUser();
        $bingoUser = BingoUser::get(user: $user);
        $team = $bingoUser->team;

        $challenge = Challenge::get($_POST["challenge_id"]);
        $condition = json_decode($challenge->achieve_condition);
        if ($condition->type != "photo") {
            return;
        }

        $target = $condition->target;
        if (!is_int($target)) return;

        $photo = Photo::get($target);

        $this->update_photo_challenge($team, $photo);

        $teamChallenge = TeamHasChallenge::new();
        $teamChallenge->team = $team;
        $teamChallenge->challenge = $challenge;
        $teamChallenge->save();

        return $this->render(
            "Site/toaster",
            ["message" => "Foto caricata con successo!"],
            headers: ['HX-Trigger' => 'showToast']
        );
    }



    #[LoginRequired(level: 1)]
    private function update_photo_challenge($team, $photo){

        $target_dir = "resources/uploads/";
        $extension = pathinfo($_FILES["photo"]["name"], PATHINFO_EXTENSION);
        $path = $target_dir . $team->id . "_" . $photo->id . "_" . time() . "." . $extension;

        move_uploaded_file($_FILES["photo"]["tmp_name"], $path);


        $currentPhoto = TeamHasPhoto::filter(team: $team, photo: $photo);
        if($currentPhoto->count()){
            $currentPhoto = $currentPhoto->first();
            $currentPhoto->path = $path;
            $currentPhoto->save();
            return;
        } else {
            $teamPhoto = TeamHasPhoto::new();
            $teamPhoto->team = $team;
            $teamPhoto->photo = $photo;
            $teamPhoto->path = $path;
            $teamPhoto->save();
        }
    }


    #[LoginRequired(level: 1)]
    public function uploadPhoto()
    {
        $user = (new UserAuthenticator())->getLoggedUser();
        $bingoUser = BingoUser::get(user: $user);
        $team = $bingoUser->team;

        $photo = Photo::get($_POST["photo_id"]);

        $this->update_photo_challenge($team, $photo);

        $this->update_challenges($team, $photo);

        return $this->render(
            "Site/toaster",
            ["message" => "Foto caricata con successo!"],
            headers: ['HX-Trigger' => 'showToast']
        );
    }



    #[LoginRequired(level: 1)]
    private function add_challenge($team, $challenge){
        $teamChallenge = TeamHasChallenge::new();
        $teamChallenge->team = $team;
        $teamChallenge->challenge = $challenge;
        $teamChallenge->save();
    }


    #[LoginRequired(level: 1)]
    private function update_challenges($team){

        $all_challenges = Challenge::all();

        foreach($all_challenges as $challenge){
            $condition = json_decode($challenge->achieve_condition);
            if($condition->type == "photo"){

                if(is_array($condition->target)){
                    $teamPhotoCount = 0;
                    foreach($condition->target as $target){
                        $photo = Photo::get($target);
                        $teamPhotoCount += TeamHasPhoto::filter(team: $team, photo: $photo)->count();
                    }
                    if ($teamPhotoCount < count($condition->target)) {
                        continue;
                    }
                    $this->add_challenge($team, $challenge);
                }
                else{
                    $photo = Photo::get($condition->target);
                    $teamPhotoCount = TeamHasPhoto::filter(team: $team, photo: $photo)->count();
                    if ($teamPhotoCount == 0) {
                        continue;
                    }
                    $this->add_challenge($team, $challenge);
                }
            }
            if($condition->type == "photogroup"){
                    if($condition->condition == "all"){
                        $photogroup = PhotoGroup::get($condition->target);
                        $allphotos = $photogroup->photos->count();
                        $photogroupPhotos = $photogroup->photos;
                        $teamPhotoCount = 0;
                        foreach($photogroupPhotos as $photo){
                            $teamPhotoCount += TeamHasPhoto::filter(team: $team, photo: $photo)->count();
                        }
                        if($teamPhotoCount < $allphotos){
                            continue;
                        }
                        $this->add_challenge($team, $challenge);
                    }
                    if($condition->condition == "non_empty"){
                        $teamGroupCount = 0;
                        foreach($condition->target as $target){
                            $photogroup = PhotoGroup::get($target);
                            $photogroupPhotos = $photogroup->photos;
                            $teamPhotoCount = 0;
                            foreach($photogroupPhotos as $photo){
                                $teamPhotoCount += TeamHasPhoto::filter(team: $team, photo: $photo)->count();
                            }

                            if($teamPhotoCount > 0){
                                $teamGroupCount += 1;
                            }
                        }
                        if($teamGroupCount < count($condition->target)){
                            continue;
                        }
                        $this->add_challenge($team, $challenge);
                    }

            }
        }
    }


    #[LoginRequired(level: 3)]
    public function admin(){
        $user = (new UserAuthenticator())->getLoggedUser();
        $bingoUser = BingoUser::get(user: $user);
        if ($bingoUser->level < 3) {
            return $this->render(
                "Site/login",
            );
        }
        $is_open  = BingoSettings::get(name: "is_open");

        return $this->render("admin", ["is_open" => $is_open->value]);
    }


    #[LoginRequired(level: 3)]
    public function toggleOpen(){
        $user = (new UserAuthenticator())->getLoggedUser();
        $bingoUser = BingoUser::get(user: $user);
        if($bingoUser->level < 3){
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
    public function standings(){
        $teams = Team::all();
        $standings = [];
        foreach($teams as $team){
            $challenges = TeamHasChallenge::filter(team: $team);
            $standings[] = ["team" => $team, "points" => $challenges->count()];
        }

        usort($standings, function($a, $b){
            return $b["points"] - $a["points"];
        });
        return $this->render("standings", ["standings" => $standings]);
    }
}
