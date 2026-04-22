<?php

use App\Controllers\BingoController;
use App\Controllers\AdminController;

return [
    "" => [BingoController::class, "index"],
    "challenges" => [BingoController::class, "challenges"],
    "login" => [BingoController::class, "login"],
    "logout" => [BingoController::class, "logout"],
    "challenge/info/<int:id>" => [BingoController::class, "challengeInfo"],
    "challenge/upload_photo" => [BingoController::class, "uploadPhotoChallenge"],
    "challenge/remove_photo/<int:thp_id>/<int:challenge_id>" => [BingoController::class, "removePhotoChallenge"],
    "photo/info/<int:id>" => [BingoController::class, "photoInfo"],
    "photos/upload_photo" => [BingoController::class, "uploadPhoto"],

    "changeName" => [BingoController::class, "changeName"],
    "shareLocation" => [BingoController::class, "shareLocation"],
    "kings" => [BingoController::class, "kings"],
    "admin" => [AdminController::class, "admin"],
    "admin/toggleOpen" => [AdminController::class, "toggleOpen"],
    "admin/addAccount" => [AdminController::class, "adminAddAccount"],
    "admin/teams" => [AdminController::class, "adminTeams"],
    "admin/kings" => [AdminController::class, "adminKings"],
    "admin/admins" => [AdminController::class, "adminAdmins"],
    "admin/removeAccount/<int:id>" => [AdminController::class, "adminRemoveAccount"],
    "admin/setPassword/<int:id>" => [AdminController::class, "adminSetPassword"],
    "admin/challenges" => [AdminController::class, "adminChallenges"],
    "admin/addChallenge" => [AdminController::class, "adminAddChallenge"],
    "admin/editChallenge/<int:id>" => [AdminController::class, "adminEditChallenge"],
    "admin/removeChallenge/<int:id>" => [AdminController::class, "adminRemoveChallenge"],
    "admin/groups" => [AdminController::class, "adminGroups"],
    "admin/addGroup" => [AdminController::class, "adminAddGroup"],
    "admin/editGroup/<int:id>" => [AdminController::class, "adminEditGroup"],
    "admin/removeGroup/<int:id>" => [AdminController::class, "adminRemoveGroup"],
    "admin/groupPhotos/<int:group_id>" => [AdminController::class, "adminGroupPhotos"],
    "admin/addPhoto/<int:group_id>" => [AdminController::class, "adminAddPhoto"],
    "admin/editPhoto/<int:photo_id>/<int:group_id>" => [AdminController::class, "adminEditPhoto"],
    "admin/removePhoto/<int:photo_id>/<int:group_id>" => [AdminController::class, "adminRemovePhoto"],
    "admin/manual" => [AdminController::class, "adminManual"],
    "admin/toggleManual" => [AdminController::class, "adminToggleManual"],
    "admin/settings" => [AdminController::class, "adminSettings"],
    "admin/settings/update" => [AdminController::class, "updateSettings"],
    "standings" => [AdminController::class, "standings"],
    "team/viewPhotos/<int:id>" => [BingoController::class, "viewPhotos"]
 ];
