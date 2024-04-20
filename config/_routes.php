<?php

use App\Controllers\BingoController;

return [
    "" => [BingoController::class, "index"],
    "challenges" => [BingoController::class, "challenges"],
    "login" => [BingoController::class, "login"],
    "logout" => [BingoController::class, "logout"],
    "challenge/info/<int:id>" => [BingoController::class, "challengeInfo"],
    "challenge/upload_photo" => [BingoController::class, "uploadPhotoChallenge"],
    "photo/info/<int:id>" => [BingoController::class, "photoInfo"],
    "photos/upload_photo" => [BingoController::class, "uploadPhoto"],
    "teams" => [BingoController::class, "teams"],
    "team/listUsers/<int:id>" => [BingoController::class, "listUsers"],
    "team/removeUser" => [BingoController::class, "removeUser"],
    "team/addUser/<int:team_id>" => [BingoController::class, "addUser"],
    "team/changeName/<int:team_id>" => [BingoController::class, "changeName"],
    "admin" => [BingoController::class, "admin"],
    "admin/toggleOpen" => [BingoController::class, "toggleOpen"],
    "standings" => [BingoController::class, "standings"]
 ];
