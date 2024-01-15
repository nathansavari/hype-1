<?php

declare(strict_types=1);

namespace Application\Lib;

use Exception;
use Application\Lib\DatabaseConnection;

require_once('lib/database.php');

class User
{
    public string $name;
    public string $email;
    public string $createdAt;
}

class UserRepository
{
    private static ?DatabaseConnection $db = null;
    private static string $algo;

    //On initialise la base de donnée et la variable d'environnement 'algo'

    function __construct()
    {
        if (is_null(self::$db)) {
            self::$db = new DatabaseConnection();
            self::$algo = parse_ini_file('.env')['ALGO'];
        }
    }

    // On recherche l'utilisateur

    public function connectUser(string $email, string $password): string
    {
        try {

            //On sécurise un peu +

            $email = htmlspecialchars($email);

            //On vérifie si l'utilisateur existe bel et bien

            $response = $this->verifyUser($email);

            if ($response !== true) {
                return json_encode(array("error" => 'User does not exist'));
            }

            //On hash le mot de passe

            $hash = hash(self::$algo, $password);

            $query = self::$db->getConnected()->prepare('SELECT id FROM customers WHERE email = :email AND password = :password');
            $query->bindParam(':email', $email);
            $query->bindParam(':password', $hash);
            $query->execute();

            if ($query->rowCount() === 0) {
                return json_encode(array("error" => 'Invalid credentials'));
            }

            $row = $query->fetch();
            $_SESSION['last_access'] = time();
            $_SESSION['isConnected'] = true;
            $_SESSION['userId'] = session_create_id(json_encode($row['id']) . '-');

            return json_encode(array("message" => 'User connected'));
        } catch (Exception $e) {
            return json_encode(["error" => $e->getMessage()]);
        }
    }

    // On récupère des infos sur l'utilisateur

    public function getUserInfos(): string
    {
        try {
            $isConnected = $this->verifySession();

            if ($isConnected === false) {
                return json_encode(array("error" => 'Not connected'));
            }

            $id = strstr($_SESSION['userId'], '-', true);

            $query = self::$db->getConnected()->prepare('SELECT email, name, createdAt FROM customers WHERE id = :id');
            $query->bindParam(':id', $id);
            $query->execute();


            $row = $query->fetch();

            $user = new User;
            $user->email = $row['email'];
            $user->name = $row['name'];
            $user->createdAt = $row['createdAt'];

            return json_encode($user);
        } catch (Exception $e) {
            return json_encode(["error" => $e->getMessage()]);
        }
    }

    //Création d'un utilisateur

    public function createUser(string $email, string $password, string $name): string
    {
        try {

            //On sécurise un peu +

            $email = htmlspecialchars($email);
            $name = htmlspecialchars($name);

            //On vérifie que l'utilisateur n'existe pas déjà

            $response = $this->verifyUser($email);

            if ($response === true) {
                return json_encode(array("error" => 'User already exists'));
            }

            $hash = hash(self::$algo, $password);

            $query = self::$db->getConnected()->prepare('INSERT INTO customers (email, password, name) VALUES ( :email, :password, :name);
            ');
            $query->bindParam(':email', $email);
            $query->bindParam(':password', $hash);
            $query->bindParam(':name', $name);
            $query->execute();

            return json_encode(array("message" => 'User created'));
        } catch (Exception $e) {
            return json_encode(["error" => $e->getMessage()]);
        }
    }

    //Vérification si l'utilisateur existe

    private function verifyUser(string $email): bool
    {
        try {
            $query = self::$db->getConnected()->prepare('SELECT email FROM customers WHERE email = :email');
            $query->bindParam(':email', $email);
            $query->execute();

            return $query->rowCount() > 0;
        } catch (Exception $e) {
            return json_encode(["error" => $e->getMessage()]);
        }
    }

    //Vérification de la session utilisateur

    private function verifySession(): bool
    {
        if (!isset($_SESSION['last_access'])  || (time() - $_SESSION['last_access']) > 86400) {
            $this->logoutUser();
        }

        return $_SESSION['isConnected'];
    }

    //On déconnecte l'utilisateur

    public function logoutUser(): void
    {
        $_SESSION['isConnected'] = false;
        session_destroy();
    }
}
