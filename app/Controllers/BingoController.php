<?php

namespace App\Controllers;

use Lepton\Core\Application;
use Lepton\Controller\BaseController;
use Lepton\Boson\Model;
use Liquid\{Liquid, Template};
use Lepton\Authenticator\AccessControlAttributes\LoginRequired;
use Lepton\Authenticator\UserAuthenticator;
use App\Models\{Challenge, PhotoGroup, Team, Photo, BingoSettings, TeamHasChallenge, TeamHasPhoto, KingLocation, King};

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

        $now = new \DateTime();
        $hidden_challenges = [];
        foreach($challenges as $challenge) {
            if ($challenge->visibility_time != null){
                $visibility_time = new \DateTime($challenge->visibility_time);
                if ($visibility_time > $now) {
                    $hidden_challenges[] = $challenge->id;
                }
            }
        }

        $team = $user->team;
        $teamchallenges = TeamHasChallenge::filter(team: $team);

        $completed_challenges = array();
        foreach($teamchallenges as $teamchallenge){
            $c_id = $teamchallenge->challenge->id;
            if (!isset($completed_challenges[$c_id])) $completed_challenges[$c_id] = 0;
            $completed_challenges[$c_id]++;
        }

        $teamphotos = TeamHasPhoto::filter(team: $team);
        $completed_photos = array();
        foreach($teamphotos as $teamphoto){
            $completed_photos[] = $teamphoto->photo->id;
        }


        $photogroups = PhotoGroup::all();

        $photogroups = PhotoGroup::all();

        $team_points = $team->calculatePoints();

        return $this->render("challenges", [
            "team" => $team, 
            "team_points" => $team_points,
            "completed_photos" => $completed_photos, 
            "completed_challenges" => $completed_challenges, 
            "challenges" => $challenges, 
            "photogroups" => $photogroups,
            "hidden_challenges" => $hidden_challenges
        ]);
    }


    #[LoginRequired(1)]
    public function index(){
        $user = (new UserAuthenticator())->getLoggedUser();
        
        $king = null;
        if ($user->level == 2) {
            $king = King::filter(user: $user)->first();
        }

        return $this->render("index", [
            "team" => $user->team, 
            "user" => $user,
            "king" => $king
        ]);
    }

    #[LoginRequired(level: 2)]
    public function shareLocation()
    {
        $user = (new UserAuthenticator())->getLoggedUser();
        
        $error = null;
        if (!isset($_FILES["photo"]) || $_FILES["photo"]["error"] != 0) {
            $error = "Errore nel caricamento del file (Codice Error: " . ($_FILES['photo']['error'] ?? 'N/A') . ")";
        } else {
            $target_dir = "resources/uploads/kings/";
            if (!file_exists($target_dir)) {
                if (!mkdir($target_dir, 0777, true)) {
                    $target_dir = "resources/uploads/";
                }
            }

            $extension = pathinfo($_FILES["photo"]["name"], PATHINFO_EXTENSION);
            $path = $target_dir . $user->id . "_" . time() . "." . $extension;
            if (move_uploaded_file($_FILES["photo"]["tmp_name"], $path)) {
                $king = King::filter(user: $user)->first();
                
                $kingLoc = KingLocation::new();
                $kingLoc->king = $king;
                $kingLoc->path = $path;
                $kingLoc->location_name = $_POST["location_name"] ?? "Posizione ignota";
                $kingLoc->timestamp = date("Y-m-d H:i:s");
                $kingLoc->save();

                $teamName = $user->team ? $user->team->name : "King";
                $this->notifyTelegram($path, $teamName, $kingLoc->location_name, "king", $king->name);
            } else {
                $error = "Errore critico: Impossibile spostare il file caricato in " . $path;
            }
        }

        if ($error) {
            $king_locations = iterator_to_array(KingLocation::all()->order_by(["timestamp" => "DESC"]));
            return $this->render("index", [
                "team" => $user->team, 
                "user" => $user,
                "king_locations" => $king_locations,
                "error" => $error
            ]);
        }

        return $this->redirect("");
    }

    #[LoginRequired(1)]
    public function kings()
    {
        $user = (new UserAuthenticator())->getLoggedUser();
        
        $kings_data = [];
        foreach (King::all() as $king) {
            $last_loc = $king->locations->order_by(["timestamp" => "DESC"])->first();
            $location = null;
            if ($last_loc) {
                $location = [
                    'path' => $last_loc->path,
                    'location_name' => $last_loc->location_name,
                    'timestamp' => $last_loc->timestamp
                ];
            }
            $kings_data[] = [
                'name' => $king->name,
                'last_location' => $location
            ];
        }
        
        return $this->render("kings", [
            "user" => $user,
            "kings" => $kings_data,
            "team" => $user->team
        ]);
    }

    #[LoginRequired(level: 1)]
    public function changeName()
    {
        $user = (new UserAuthenticator())->getLoggedUser();
        if ($user->level == 2) {
            $king = King::filter(user: $user)->first();
            if (!$king) {
                $king = King::new();
                $king->user = $user;
                $king->name = $user->username;
            }
            $king->name = $_POST["team_name"];
            $king->save();
            return $this->render(
                "Site/toaster",
                ["message" => "Nome del Re cambiato con successo!"],
                headers: ['HX-Trigger' => 'showToast']
            );
        } elseif ($user->team) {
            $team = $user->team;
            $team->name = $_POST["team_name"];
            $team->save();
            return $this->render(
                "Site/toaster",
                ["message" => "Nome del team cambiato con successo!"],
                headers: ['HX-Trigger' => 'showToast']
            );
        }

        return $this->render(
            "Site/toaster",
            ["message" => "Impossibile cambiare il nome: profilo non trovato."],
            headers: ['HX-Trigger' => 'showToast']
        );
    }

    #[LoginRequired(1)]
    public function challengeInfo($id)
    {
        return $this->render("challenge_info", $this->getChallengeInfoData($id));
    }

    private function getChallengeInfoData($id)
    {
        $challenge = Challenge::get($id);
        $photos_all = [];
        $has_photo = false;
        $photo = null;

        if($challenge->show_photo){
            $condition = $challenge->achieve_condition;
            $photo_id = json_decode($condition)->target;
            $photo = Photo::get($photo_id);
            $user = (new UserAuthenticator())->getLoggedUser();
            $team = $user->team;

            $photos = TeamHasPhoto::filter(photo: $photo, team: $team);
            if($photos->count()){
                foreach($photos as $p) $photos_all[] = $p;
                $photo = $photos_all[0];
                $has_photo = true;
            }
        }
        return ["has_photo" => $has_photo, "photos" => $photos_all, "photo" => $photo, "challenge" => $challenge];
    }

    #[LoginRequired(1)]
    public function photoInfo($id)
    {
        $photo = Photo::get($id);

        $user = (new UserAuthenticator())->getLoggedUser();
        $team = $user->team;

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
        if (isset($_POST["username"]) && isset($_POST["password"])) {
            $authenticator = new UserAuthenticator();
            if (!$authenticator->login($_POST["username"], $_POST["password"])) {
                return $this->render(
                    "Site/loginForm",
                    [
                        "login_invalid" => true,
                        "login_message" => "Username e/o password errati"
                    ]
                );
            } else {
                $user = (new UserAuthenticator())->getLoggedUser();
                if($user && $user->level === null){
                    $user->level = 1;
                    $user->save();
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
    public function logout()
    {
        $authenticator = new UserAuthenticator();
        $authenticator->logout();
        return $this->redirect("");
    }





    #[LoginRequired(level: 1)]
    public function uploadPhotoChallenge()
    {
        $user = (new UserAuthenticator())->getLoggedUser();
        $team = $user->team;
        
        if (!isset($_POST["challenge_id"])) {
            return $this->render("Site/toaster", ["message" => "Errore: ID sfida mancante."], headers: ['HX-Trigger' => 'showToast']);
        }

        $challenge_id = $_POST["challenge_id"];
        $challenge = Challenge::get($challenge_id);
        
        if (!$challenge) {
            return $this->render("Site/toaster", ["message" => "Errore: Sfida non trovata."], headers: ['HX-Trigger' => 'showToast']);
        }

        $condition = json_decode($challenge->achieve_condition);
        if ($condition->type != "photo") {
            return;
        }

        $target = $condition->target;
        if (!is_int($target)) return;

        $photo = Photo::get($target);

        $existingCount = TeamHasChallenge::filter(team: $team, challenge: $challenge)->count();
        $isMultiple = ($challenge->max_completions ?? 1) > 1;
        $allow_multiple = $isMultiple && ($existingCount < ($challenge->max_completions ?? 1));

        $this->update_photo_challenge($team, $photo, $allow_multiple);

        if ($existingCount < ($challenge->max_completions ?? 1)) {
            $teamChallenge = TeamHasChallenge::new();
            $teamChallenge->team = $team;
            $teamChallenge->challenge = $challenge;
            $teamChallenge->save();
        }

        $this->update_challenges($team);

        return $this->render(
            "Site/toaster",
            ["message" => "Foto caricata con successo!"],
            headers: ['HX-Trigger' => 'showToast']
        );
    }
 

    #[LoginRequired(level: 1)]
    private function update_photo_challenge($team, $photo, $allow_multiple = false, $type = "challenge", $extra = null){

        $target_dir = "resources/uploads/";
        $extension = pathinfo($_FILES["photo"]["name"], PATHINFO_EXTENSION);
        $path = $target_dir . $team->id . "_" . $photo->id . "_" . time() . "." . $extension;

        move_uploaded_file($_FILES["photo"]["tmp_name"], $path);

        $timestamp =  date("Y-m-d H:i:s");

        $currentPhotos = TeamHasPhoto::filter(team: $team, photo: $photo);
        if(!$allow_multiple && $currentPhotos->count()){
            $photos_arr = [];
            foreach($currentPhotos as $p) $photos_arr[] = $p;
            usort($photos_arr, function($a, $b) { return strtotime($b->timestamp) - strtotime($a->timestamp); });
            
            $currentPhoto = $photos_arr[0];
            $currentPhoto->path = $path;
            $currentPhoto->timestamp = $timestamp;
            $currentPhoto->save();

            $this->notifyTelegram($path, $team->name, $photo->title, $type, $extra);
            return;
        }
            $teamPhoto = TeamHasPhoto::new();
            $teamPhoto->team = $team;
            $teamPhoto->photo = $photo;
            $teamPhoto->path = $path;
            $teamPhoto->timestamp = $timestamp;
            $teamPhoto->save();

            $this->notifyTelegram($path, $team->name, $photo->title, $type, $extra);
    }


    #[LoginRequired(level: 1)]
    public function uploadPhoto()
    {
        $user = (new UserAuthenticator())->getLoggedUser();
        $team = $user->team;
        
        if (!isset($_POST["photo_id"])) {
            return $this->render("Site/toaster", ["message" => "Errore: ID foto mancante."], headers: ['HX-Trigger' => 'showToast']);
        }

        $photo_id = $_POST["photo_id"];
        $photo = Photo::get($photo_id);
        
        if (!$photo) {
            return $this->render("Site/toaster", ["message" => "Errore: Foto non trovata."], headers: ['HX-Trigger' => 'showToast']);
        }

        $type = "challenge";
        $extra = null;
        if ($photo->group) {
            if ($photo->group->is_multiplier) {
                $type = "multiplier";
                $extra = $photo->group->name;
            } else {
                $type = "locale";
            }
        }

        $this->update_photo_challenge($team, $photo, type: $type, extra: $extra);

        $this->update_challenges($team);

        return $this->render(
            "Site/toaster",
            ["message" => "Foto caricata con successo!"],
            headers: ['HX-Trigger' => 'showToast']
        );
    }



    private function add_challenge($team, $challenge){
        $existingCount = TeamHasChallenge::filter(team: $team, challenge: $challenge)->count();
        if ($existingCount >= ($challenge->max_completions ?? 1)) {
            return;
        }

        $teamChallenge = TeamHasChallenge::new();
        $teamChallenge->team = $team;
        $teamChallenge->challenge = $challenge;
        $teamChallenge->save();

        $caption = "⚔️ Sfida completata!\n\n👥 Squadra: {$team->name}\n🎯 Sfida: {$challenge->title}";
        $this->notifyTelegram(null, null, null, "message", $caption);
    }


    #[LoginRequired(level: 1)]
    private function update_challenges($team){

        $all_challenges = Challenge::all();

        foreach($all_challenges as $challenge){
            if ($challenge->show_photo == 1) continue;

            $condition = json_decode($challenge->achieve_condition);

            // Consecutive check
            if ($challenge->is_consecutive && $condition->type != "manual") {
                $required_ids = [];
                if ($condition->type == "photo") {
                    $required_ids = is_array($condition->target) ? $condition->target : [$condition->target];
                } elseif ($condition->type == "photogroup") {
                    $photogroup = PhotoGroup::get($condition->target);
                    if ($photogroup) {
                        foreach($photogroup->photos as $p) $required_ids[] = (int)$p->id;
                    }
                }
                
                $numRequired = count($required_ids);
                if ($numRequired > 0) {
                    $lastUploads = TeamHasPhoto::filter(team: $team)->order_by(["timestamp" => "DESC"]);
                    $i = 0;
                    $uploadedInSequence = [];
                    foreach ($lastUploads as $upload) {
                        if ($i >= $numRequired) break;
                        $uploadedInSequence[] = (int)$upload->photo->id;
                        $i++;
                    }
                    
                    if (count($uploadedInSequence) == $numRequired && count(array_diff($required_ids, $uploadedInSequence)) == 0) {
                        $existingCount = TeamHasChallenge::filter(team: $team, challenge: $challenge)->count();
                        if ($existingCount < ($challenge->max_completions ?? 1)) {
                            $this->add_challenge($team, $challenge);
                        }
                    }
                }
                continue; 
            }

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

    #[LoginRequired(level: 1)]
    public function removePhotoChallenge($thp_id, $challenge_id)
    {
        $user = (new UserAuthenticator())->getLoggedUser();
        $team = $user->team;
        $thp = TeamHasPhoto::get($thp_id);
        $challenge = Challenge::get($challenge_id);

        if ($thp && $team && $thp->team->id == $team->id) {
            $thp->delete();
            
            // Remove one instance of TeamHasChallenge to revoke points
            $thc = TeamHasChallenge::filter(team: $team, challenge: $challenge)->first();
            if ($thc) {
                $thc->delete();
            }

            // Return challengeInfo to refresh modal content, adding a toaster header
            return $this->render(
                "challenge_info", 
                $this->getChallengeInfoData($challenge_id),
                headers: ['HX-Trigger' => 'showToast']
            );
        }

        return $this->render(
            "Site/toaster",
            ["message" => "Impossibile rimuovere la foto."],
            headers: ['HX-Trigger' => 'showToast']
        );
    }

    #[LoginRequired(level: 1)]
    public function viewPhotos($id){
        $team = Team::get($id);
        $teamphotos = TeamHasPhoto::filter(team: $team);
        return $this->render("standings_info", ["team" => $team, "photos" => $teamphotos]);
    }

    private function notifyTelegram($path, $teamName, $objectName, $type = "challenge", $extra = null) {
        try {
            $botTokenObj = BingoSettings::get(name: "bot_token");
            $chatIdObj = BingoSettings::get(name: "telegram_group_id");
            
            if (!$botTokenObj || !$chatIdObj) return;
            
            $botToken = $botTokenObj->value;
            $chatId = $chatIdObj->value;

            if (!$botToken || !$chatId) return;

            $caption = "";
            if ($type == "king") {
                $emoji = "👑";
                $kingNameLower = strtolower($extra);
                if (str_contains($kingNameLower, "cuori")) $emoji = "♥️";
                elseif (str_contains($kingNameLower, "quadri")) $emoji = "♦️";
                elseif (str_contains($kingNameLower, "fiori")) $emoji = "♣️";
                elseif (str_contains($kingNameLower, "picche")) $emoji = "♠️";

                $caption = "$emoji Nuova posizione del Re di $extra\n\n📍 Luogo: $objectName";
            } elseif ($type == "multiplier") {
                $emoji = "👑";
                $photoLower = strtolower($objectName);
                if (str_contains($photoLower, "cuori")) $emoji = "♥️";
                elseif (str_contains($photoLower, "quadri")) $emoji = "♦️";
                elseif (str_contains($photoLower, "fiori")) $emoji = "♣️";
                elseif (str_contains($photoLower, "picche")) $emoji = "♠️";

                $caption = "👑 Sfida dei Re completata!\n\n👥 Squadra: $teamName\n$emoji Re di $objectName";
            } elseif ($type == "challenge") {
                $caption = "📸 Nuova foto caricata!\n\n👥 Squadra: $teamName\n🎯 Sfida: $objectName";
            } elseif ($type == "locale") {
                $caption = "📸 Nuova foto caricata!\n\n👥 Squadra: $teamName\n📍 Locale: $objectName";
            } else {
                $caption = "📸 Nuova foto caricata!\n\n👥 Squadra: $teamName\n📍 $objectName";
            }
            
            if ($type == "message") {
                $url = "https://api.telegram.org/bot$botToken/sendMessage";
                $data = [
                    'chat_id' => $chatId,
                    'text' => $extra
                ];
            } else {
                $url = "https://api.telegram.org/bot$botToken/sendPhoto";
                
                $data = [
                    'chat_id' => $chatId,
                    'photo' => new \CURLFile(realpath($path)),
                    'caption' => $caption
                ];
            }

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_exec($ch);
            curl_close($ch);
        } catch (\Exception $e) {
            // Silently fail
        }
    }
}
