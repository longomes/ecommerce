<?php

namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;

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
        Category::updateFile();
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

        Category::updateFile();
    }

    public static function updateFile()
    {
        $categories = Category::listAll();
        
        $html = [];

        foreach ($categories as $row) {
            array_push($html, 
                '<li><a href="/categories/' . 
                $row['idcategory'] . '">' . $row['descategory'] . '</a></li>'
            );
        }

        $file = $_SERVER['DOCUMENT_ROOT'] . 'views' . DIRECTORY_SEPARATOR . 'categories-menu.html';
        file_put_contents($file, implode(PHP_EOL, $html));
    }

    public function getRelated($related = true)
    {
        $sql = new Sql;

        if ($related) {
            return $sql->select(
                'SELECT * FROM tb_products WHERE idproduct IN(
                    SELECT a.idproduct 
                    FROM tb_products a 
                    INNER JOIN tb_productscategories b 
                    ON a.idproduct = b.idproduct 
                    WHERE b.idcategory = :idcategory
                )',
                [
                    ':idcategory' => $this->getidcategory()
                ]
            );
        } else {
            return $sql->select(
                'SELECT * FROM tb_products WHERE idproduct NOT IN(
                    SELECT a.idproduct 
                    FROM tb_products a  
                    INNER JOIN tb_productscategories b 
                    ON a.idproduct = b.idproduct 
                    WHERE b.idcategory = :idcategory
                )',
                [
                    ':idcategory' => $this->getidcategory()
                ]
            );
        }
    }

    public function getProductsPage($page = 1, $itensPerPage = 8)
    {
        $start = ($page-1) * $itensPerPage;

        $sql = new Sql;
        $results = $sql->select(
            "SELECT sql_calc_found_rows * FROM tb_products a 
            INNER JOIN tb_productscategories b 
            ON a.idproduct = b.idproduct 
            INNER JOIN tb_categories c 
            On c.idcategory = b.idcategory 
            WHERE c.idcategory = :idcategory 
            LIMIT {$start}, {$itensPerPage}",
            [
                ':idcategory' => $this->getidcategory()
            ]
        );

        $total = $sql->select('SELECT found_rows() as total');

        return [
            'data' => Product::checkList($results),
            'total' => (int) $total[0]['total'],
            'pages' => ceil($total[0]['total'] / $itensPerPage)
        ];
    }

    public function addProduct(Product $product)
    {
        $sql = new Sql;
        $sql->query(
            'INSERT INTO tb_productscategories (idcategory, idproduct) 
            VALUES (:idcategory, :idproduct)',
            [
                ':idcategory' => $this->getidcategory(),
                ':idproduct' => $product->getidproduct()
            ]
        );
    }

    public function removeProduct(Product $product)
    {
        $sql = new Sql;
        $sql->query(
            'DELETE FROM tb_productscategories 
            WHERE idcategory = :idcategory 
            AND idproduct = :idproduct',
            [
                ':idcategory' => $this->getidcategory(),
                ':idproduct' => $product->getidproduct()
            ]
        );
    }
}