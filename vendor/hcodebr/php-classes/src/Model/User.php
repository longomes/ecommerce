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

    public static function listAll()
    {
        $sql = new Sql;
        return $sql->select(
            "SELECT * FROM tb_users a 
            INNER JOIN tb_persons b 
            USING(idperson) ORDER BY b.desperson");
    }

    public function save()
    {
        $sql = new Sql;

        $results = $sql->select("CALL sp_users_save(:desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)", [
            ':desperson' => $this->getdesperson(),
            ':deslogin' => $this->getdeslogin(),
            ':despassword' => $this->getdespassword(),
            ':desemail' => $this->getdesemail(),
            ':nrphone' => $this->getnrphone(),
            ':inadmin' => $this->getinadmin()
        ]);

        $this->setValues($results[0]);
    }

    public function getById($iduser)
    {
        $sql = new Sql;

        $results = $sql->select(
            "SELECT * FROM tb_users a 
            INNER JOIN tb_persons b 
            USING(idperson) WHERE a.iduser = :iduser", [
            ':iduser' => $iduser
        ]);

        $this->setValues($results[0]);
    }

    public function update()
    {
        $sql = new Sql;
        
        $results = $sql->select("CALL sp_usersupdate_save(:iduser, :desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)", [
            ':iduser' => $this->getiduser(),
            ':desperson' => $this->getdesperson(),
            ':deslogin' => $this->getdeslogin(),
            ':despassword' => $this->getdespassword(),
            ':desemail' => $this->getdesemail(),
            ':nrphone' => $this->getnrphone(),
            ':inadmin' => $this->getinadmin()
        ]);

        $this->setValues($results[0]);
    }

    public function delete()
    {
        $sql = new Sql;
        $sql->query("CALL sp_users_delete(:iduser)", [
            ':iduser' => $this->getiduser()
        ]);
    }
}