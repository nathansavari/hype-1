<?php

//Ce fichier va servir de router

declare(strict_types=1);

namespace Application\Index;

use Application\Lib\UserRepository;

require_once('lib/user.php');

session_start();

$init = new UserRepository;

//On récupère la valeur "action" du body

$action = null;

if (isset($_POST["action"])) {
    $action = $_POST["action"];
}

//Le router traite les infos selon les requêtes

switch ($action) {

    default:
        echo json_encode(array("error" => "Error, no action defined"));
        break;

    case 'signin':
        if (isset($_POST["email"]) && isset($_POST["password"])) {

            $response = $init->connectUser($_POST["email"], $_POST["password"]);
            print_r($response);
        } else {
            echo json_encode(array("error" => 'Please fill up all the infos'));
        }

        break;

    case 'signup':
        if (isset($_POST["email"]) && isset($_POST["password"]) && isset($_POST["name"])) {

            $response = $init->createUser($_POST["email"], $_POST["password"], $_POST["name"]);
            print_r($response);
        } else {
            echo json_encode(array("error" => 'Please fill up all the infos'));
        }

        break;

    case 'getInfos':
        $response = $init->getUserInfos();
        print_r($response);

        break;

    case 'logOut':
        $init->logoutUser();
        break;

    case null:
        echo json_encode(array("error" => "Error, no action defined"));
        break;
}
