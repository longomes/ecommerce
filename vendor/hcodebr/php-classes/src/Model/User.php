<?php

namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;

class User extends Model
{
    const SESSION = "User";

    public static function login($login, $password)
    {
        $sql = new Sql;

        $results = $sql->select("SELECT * FROM tb_users WHERE deslogin = :LI", [
            ':LI' => $login
        ]);        

        if (!count($results)) {
            throw new \Exception("Usuário inexistente ou senha inválida.");
        }

        $data = $results[0];

        if (!password_verify($password, $data['despassword'])) {
            throw new \Exception("Login ou senha inválida.");
        }

        $user = new User;
        $user->setValues($data);

        $_SESSION[User::SESSION] = $user->getValues();
        
        return $user;
    }

    public static function verifyLogin($inadmin = true)
    {
        if (!isset($_SESSION[User::SESSION]) || 
            !$_SESSION[User::SESSION] ||
            !(int)$_SESSION[User::SESSION]['iduser'] > 0 ||
            (bool)$_SESSION[User::SESSION]['inadmin'] !== $inadmin) {
                header('Location: /admin/login');
                exit;
        }

    }

    public static function logout()
    {
        $_SESSION[USER::SESSION] = NULL;
    }
}