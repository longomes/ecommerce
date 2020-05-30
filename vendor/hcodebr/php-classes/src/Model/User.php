<?php

namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;
use \Hcode\Mailer;

class User extends Model
{
    const SESSION = "User";
    const SECRET = 'HcodePhp7_Secret';
    const SECRET_IV = 'HcodePhp7_Secret_IV';
    const ERROR = 'UserError';
    const ERROR_REGISTER = 'UserErrorRegister';

    public static function getFromSession()
    {
        $user = new User;

        if (isset($_SESSION[User::SESSION]) && 
            (int)$_SESSION[User::SESSION]['iduser'] > 0) {                
                $user->setValues($_SESSION[User::SESSION]);                
            }
        
        return $user;
    }
    
    public static function checkLogin($inadmin = true)
    {
        if (!isset($_SESSION[User::SESSION]) || 
            !$_SESSION[User::SESSION] ||
            !(int)$_SESSION[User::SESSION]['iduser'] > 0) {
                // Not logged
                return false;
        } else {
            if ($inadmin === true && 
                (bool)$_SESSION[User::SESSION]['inadmin'] === true) {
                    return true;
            } elseif ($inadmin === false) {
                return true;
            } else {
                return false;
            }
        }
    }

    public static function login($login, $password)
    {
        $sql = new Sql;

        $results = $sql->select(
            "SELECT * FROM tb_users a 
            INNER JOIN tb_persons b ON a.idperson = b.idperson 
            WHERE deslogin = :deslogin", 
            [
                ':deslogin' => $login
            ]
        );        

        if (!count($results)) {
            throw new \Exception("Usuário inexistente ou senha inválida.");
        }

        $data = $results[0];

        if (!password_verify($password, $data['despassword'])) {
            throw new \Exception("Login ou senha inválida.");
        }

        $user = new User;
        
        $data['desperson'] = utf8_encode($data['desperson']);

        $user->setValues($data);

        $_SESSION[User::SESSION] = $user->getValues();
        
        return $user;
    }

    public static function verifyLogin($inadmin = true)
    {
        if (!User::checkLogin($inadmin)) {
            if ($inadmin) {
               header('Location: /admin/login'); 
            } else {
                header('Location: /login');
            }
            
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
            ':desperson' => utf8_decode($this->getdesperson()),
            ':deslogin' => $this->getdeslogin(),
            ':despassword' => User::getPasswordHash($this->getdespassword()),
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

        $data = $results[0];
        $data['desperson'] = utf8_encode($data['desperson']);

        $this->setValues($data);
    }

    public function update()
    {
        $sql = new Sql;
        
        $results = $sql->select("CALL sp_usersupdate_save(:iduser, :desperson, :deslogin, :despassword, :desemail, :nrphone, :inadmin)", [
            ':iduser' => $this->getiduser(),
            ':desperson' => utf8_decode($this->getdesperson()),
            ':deslogin' => $this->getdeslogin(),
            ':despassword' => User::getPasswordHash($this->getdespassword()),
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

    public static function getForgot($email, $inadmin = true)
    {
        $sql = new Sql;

        $results = $sql->select(
            'SELECT * FROM tb_persons a 
            INNER JOIN tb_users b USING(idperson) 
            WHERE a.desemail = :email',
            [
                ':email' => $email
            ]
        );

        if (!count($results)) 
            throw new \Exception('Não foi possível recuperar os dados.');
        
        $data = $results[0];
        
        $results2 = $sql->select(
            'CALL sp_userspasswordsrecoveries_create(:iduser, :desip)',
            [
                ':iduser' => $data['iduser'],
                ':desip' => $_SERVER['REMOTE_ADDR']
            ]
        );

        if (!count($results2)) 
            throw new \Exception('Não foi possível recuperar os dados');

        $dataRecovery = $results2[0];

        $encrypt = openssl_encrypt(
            $dataRecovery['idrecovery'],
            'AES-128-CBC',
            pack('a16', User::SECRET),
            0,
            pack('a16', User::SECRET_IV)
        );

        $code = base64_encode($encrypt);
        
        if ($inadmin) {
            $link = 'http://www.hcodecommerce.com.br/admin/forgot/reset?code=' . $code;
        } else {
            $link = 'http://www.hcodecommerce.com.br/forgot/reset?code=' . $code;
        }        
        
        $mailer = new Mailer(
            $data['desemail'], 
            $data['desperson'], 
            'Redefinir senha da Hcode Store', 
            'forgot',
            [
                'name' => $data['desperson'],
                'link' => $link
            ]
        );

        $mailer->send();
        return $data;
    }

    public static function validForgotDecrypt($code)
    {
        $idrecovery = openssl_decrypt(
            base64_decode($code), 
            'AES-128-CBC', 
            pack('a16', User::SECRET), 
            0, 
            pack('a16', User::SECRET_IV)
        );

        $sql = new Sql;
        
        $results = $sql->select(
            'SELECT * FROM tb_userspasswordsrecoveries a 
            INNER JOIN tb_users b USING(iduser) 
            INNER JOIN tb_persons c USING(idperson) 
            WHERE a.idrecovery = :idrecovery 
            AND a.dtrecovery IS NULL 
            AND DATE_ADD(a.dtregister, INTERVAL 1 HOUR) >= NOW()',
            [
                ':idrecovery' => $idrecovery
            ]
        );

        if (!count($results))
            throw new \Exception('Não foi possível recuperar a senha.');

        return $results[0];
    }

    public static function setForgotUsed($idrecovery)
    {
        $sql = new Sql;
        
        $sql->query(
            'UPDATE tb_userspasswordsrecoveries 
            SET dtrecovery = NOW() 
            WHERE idrecovery = :idrecovery',
            [
                ':idrecovery' => $idrecovery
            ]
        );
    }

    public function setPassword($password)
    {
        $sql = new Sql;

        $sql->query(
            'UPDATE tb_users 
            SET despassword = :despassword 
            WHERE iduser = :iduser', 
            [
                'iduser' => $this->getiduser(),
                ':despassword' => password_hash(
                    $password, 
                    PASSWORD_DEFAULT, 
                    [
                        'cost' => 12
                    ]
                )
            ]
        );
    }

    public static function setError($msg)
    {
        $_SESSION[User::ERROR] = $msg;
    }

    public static function getError()
    {
        $msg = (isset($_SESSION[User::ERROR]) && $_SESSION[User::ERROR]) ? 
                    $_SESSION[User::ERROR] : '';

        User::clearError();

        return $msg;
    }

    public static function clearError()
    {
        $_SESSION[User::ERROR] = NULL;
    }

    public static function setErrorRegister($msg)
    {
        $_SESSION[User::ERROR_REGISTER] = $msg;
    }

    public static function getErrorRegister()
    {
        $msg = (isset($_SESSION[User::ERROR_REGISTER]) && $_SESSION[User::ERROR_REGISTER]) ? 
                    $_SESSION[User::ERROR_REGISTER] : '';

        User::clearErrorRegister();

        return $msg;
    }

    public static function clearErrorRegister()
    {
        $_SESSION[User::ERROR_REGISTER] = NULL;
    }

    public static function checkLoginExists($login)
    {
        $sql = new Sql;
        $results = $sql->select(
            'SELECT * FROM tb_users 
            WHERE deslogin = :deslogin',
            [
                ':deslogin' => $login
            ]
        );

        return (count($results) > 0);
    }

    public static function getPasswordHash($password)
    {
        return password_hash($password, PASSWORD_DEFAULT, [
            'cost' => 12
        ]);
    }
}