<?php

declare(strict_types=1);

namespace Application\Lib;

use Exception;
use PDO;

class DatabaseConnection
{
    public static ?PDO $db = null;

    public static function getConnected(): PDO
    {
        try {
            //On récupère les variables dans le fichier .env

            $env = parse_ini_file('.env');
            $user = $env["USER"];
            $pass = $env["PASS"];

            if (self::$db === null) {
                self::$db = new PDO('mysql:host=localhost:3360;dbname=project1;charset=utf8', $user, $pass);
                self::getConnected();
            }

            return self::$db;
        } catch (Exception $e) {
            return json_encode(["error" => $e->getMessage()]);
        }
    }
}
