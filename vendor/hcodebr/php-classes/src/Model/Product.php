<?php

namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;

class Product extends Model
{
    public static function listAll()
    {
        $sql = new Sql;

        return $sql->select(
            'SELECT * FROM tb_products 
            ORDER BY desproduct'
        );
    }

    public static function checkList($list)
    {
        foreach ($list as &$row) {
            $p = new Product;
            $p->setValues($row);
            $row = $p->getValues();
        }

        return $list;
    }

    public function save()
    {
        $sql = new Sql;

        $results = $sql->select(
            "CALL sp_products_save(
                :idproduct, 
                :desproduct, 
                :vlprice, 
                :vlwidth, 
                :vlheight, 
                :vllength, 
                :vlweight, 
                :desurl
            )", 
            [
                ':idproduct' => $this->getidproduct(),
                ':desproduct' => $this->getdesproduct(),
                ':vlprice' => $this->getvlprice(),
                ':vlwidth' => $this->getvlwidth(), 
                ':vlheight' => $this->getvlheight(), 
                ':vllength' => $this->getvllength(), 
                ':vlweight' => $this->getvlweight(), 
                ':desurl' => $this->getdesurl()
            ]
        );

        $this->setValues($results[0]);
    }

    public function getById($idproduct)
    {
        $sql = new Sql;
        
        $results = $sql->select(
            'SELECT * FROM tb_products 
            WHERE idproduct = :idproduct',
            [
                ':idproduct' => $idproduct
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
            'DELETE FROM tb_products 
            WHERE idproduct = :idproduct',
            [
                ':idproduct' => $this->getidproduct()
            ]
        );
    }

    public function getValues()
    {
        $this->checkPhoto();

        $values = parent::getValues();

        return $values;
    }

    public function checkPhoto()
    {
        $url = '/resources/site/img/product.jpg';
        
        $file = $_SERVER['DOCUMENT_ROOT'] . 
            'resources' . DIRECTORY_SEPARATOR . 
            'site' . DIRECTORY_SEPARATOR . 
            'img' . DIRECTORY_SEPARATOR .
            'products' . DIRECTORY_SEPARATOR .
            $this->getidproduct() . ".jpg";

        if (file_exists($file)) {
            $url = '/resources/site/img/products/' . 
                $this->getidproduct() . '.jpg';
        }

        return $this->setdesphoto($url);        
    }

    public function setPhoto($file)
    {
        $extension = explode('.', $file['name']);
        $extension = end($extension);

        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                $image = imagecreatefromjpeg($file['tmp_name']);
            break;
            case 'gif':
                $image = imagecreatefromgif($file['tmp_name']);
            break;
            case 'png':
                $image = imagecreatefrompng($file['tmp_name']);
            break;            
        }

        $path = $_SERVER['DOCUMENT_ROOT'] . 
            'resources' . DIRECTORY_SEPARATOR . 
            'site' . DIRECTORY_SEPARATOR . 
            'img' . DIRECTORY_SEPARATOR .
            'products' . DIRECTORY_SEPARATOR .
            $this->getidproduct() . ".jpg";

        imagejpeg($image, $path);
        imagedestroy($image);

        $this->checkPhoto();
    }

    public function getFromURL($desurl)
    {
        $sql = new Sql;
        $rows = $sql->select(
            'SELECT * FROM tb_products 
            WHERE desurl = :desurl', 
            [
                ':desurl' => $desurl
            ]
        );

        $this->setValues($rows[0]);
    }

    public function getCategories()
    {
        $sql = new Sql;

        return $sql->select(
            'SELECT * FROM tb_categories a 
            INNER JOIN tb_productscategories b 
            ON a.idcategory = b.idcategory 
            WHERE b.idproduct = :idproduct',
            [
                ':idproduct' => $this->getidproduct()
            ]
        );
    }

    public static function getPages($page = 1, $itensPerPage = 10)
    {
        $start = ($page-1) * $itensPerPage;

        $sql = new Sql;

        $results = $sql->select(
            "SELECT SQL_CALC_FOUND_ROWS * FROM tb_products 
            ORDER BY desproduct 
            LIMIT {$start}, {$itensPerPage}"
        );

        $total = $sql->select('SELECT FOUND_ROWS() AS total');

        return [
            'data' => $results,
            'total' => (int) $total[0]['total'],
            'pages' => ceil($total[0]['total'] / $itensPerPage)
        ];
    }

    public static function getPagesSearch($search, $page = 1, $itensPerPage = 10)
    {
        $start = ($page-1) * $itensPerPage;

        $sql = new Sql;

        $results = $sql->select(
            "SELECT SQL_CALC_FOUND_ROWS * FROM tb_products 
            WHERE desproduct LIKE :search 
            ORDER BY desproduct 
            LIMIT {$start}, {$itensPerPage}",
            [
                ':search' => "%{$search}%"
            ]
        );

        $total = $sql->select('SELECT FOUND_ROWS() AS total');

        return [
            'data' => $results,
            'total' => (int) $total[0]['total'],
            'pages' => ceil($total[0]['total'] / $itensPerPage)
        ];
    }
}