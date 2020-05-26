<?php

namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;
use \Hcode\Mailer;

class Category extends Model
{
    public static function listAll()
    {
        $sql = new Sql;

        return $sql->select(
            'SELECT * FROM tb_categories 
            ORDER BY descategory'
        );
    }

    public function save()
    {
        $sql = new Sql;

        $results = $sql->select("CALL sp_categories_save(:idcategory, :descategory)", [
            ':idcategory' => $this->getidcategory(),
            ':descategory' => $this->getdescategory()
        ]);

        $this->setValues($results[0]);
    }

    public function getById($idcategory)
    {
        $sql = new Sql;
        
        $results = $sql->select(
            'SELECT * FROM tb_categories 
            WHERE idcategory = :idcategory',
            [
                ':idcategory' => $idcategory
            ]
        );

        if (!count($results)) 
            throw new \Exception('');
        
        $this->setValues($results[0]);
    }

    public function delete()
    {
        $sql = new Sql;

        $sql->query(
            'DELETE FROM tb_categories 
            WHERE idcategory = :idcategory',
            [
                ':idcategory' => $this->getidcategory()
            ]
        );


    }
}