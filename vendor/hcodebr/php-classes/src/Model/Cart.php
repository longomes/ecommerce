<?php

namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;
use \Hcode\Model\User;

class Cart extends Model
{
    const SESSION = 'Cart';
    const SESSION_ERROR = 'CartError';

    public static function getFromSession()
    {
        $cart = new Cart;

        if (isset($_SESSION[Cart::SESSION]) && 
            (int)$_SESSION[Cart::SESSION]['idcart'] > 0) {
                $cart->getById((int)$_SESSION[Cart::SESSION]['idcart']);
        } else {
            $cart->getFromSessionID();

            // No session
            if (!(int)$cart->getidcart() > 0) {
                $data = [
                    'dessessionid' => session_id()
                ];
                
                if (User::checkLogin(false)) {
                    $user = User::getFromSession();
                    $data['iduser'] = $user->getiduser();
                }
                
                $cart->setValues($data);
                $cart->save();
                $cart->setToSession();
            }
        }

        return $cart;
    }

    public function setToSession()
    {
        $_SESSION[Cart::SESSION] = $this->getValues();
    }

    public function getFromSessionID()
    {
        $sql = new Sql;

        $results = $sql->select(
            'SELECT * FROM tb_carts 
            WHERE dessessionid = :dessessionid',
            [
                ':dessessionid' => session_id()
            ]
        );

        if (count($results)) $this->setValues($results[0]);
    }

    public function getById(int $idcart)
    {
        $sql = new Sql;

        $results = $sql->select(
            'SELECT * FROM tb_carts 
            WHERE idcart = :idcart',
            [
                ':idcart' => $idcart
            ]
        );

        if (count($results)) $this->setValues($results[0]);
    }

    public function save()
    {
        $sql = new Sql;
        
        $results = $sql->select(
            'CALL sp_carts_save(
                :idcart, 
                :dessessionid,
                :iduser,
                :deszipcode,
                :vlfreight,
                :nrdays
            )',
            [
                ':idcart' => $this->getidcart(), 
                ':dessessionid'=> $this->getdessessionid(),
                ':iduser'=> $this->getiduser(),
                ':deszipcode'=> $this->getdeszipcode(),
                ':vlfreight'=> $this->getvlfreight(),
                ':nrdays'=> $this->getnrdays()
            ]
        );

        $this->setValues($results[0]);
    }

    public function addProduct(Product $product)
    {
        $sql = new Sql;

        $sql->query(
            'INSERT INTO tb_cartsproducts (idcart, idproduct) 
            VALUES (:idcart, :idproduct)',
            [
                ':idcart' => $this->getidcart(),
                ':idproduct' => $product->getidproduct()
            ]
        );

        $this->getCalculateTotal();
    }

    public function removeProduct(Product $product, $all = false)
    {
        $sql = new Sql;

        if ($all) {
            $sql->query(
                'UPDATE tb_cartsproducts 
                SET dtremoved = NOW() 
                WHERE idcart = :idcart 
                AND idproduct = :idproduct 
                AND dtremoved IS NULL LIMIT 1',
                [
                    ':idcart' => $this->getidcart(),
                    ':idproduct' => $product->getidproduct()
                ]
            );
        } else {
            $sql->query(
                'UPDATE tb_cartsproducts 
                SET dtremoved = NOW() 
                WHERE idcart = :idcart 
                AND idproduct = :idproduct 
                AND dtremoved IS NULL LIMIT 1',
                [
                    ':idcart' => $this->getidcart(),
                    ':idproduct' => $product->getidproduct()
                ]
            );
        }

        $this->getCalculateTotal();
    }

    public function getProducts()
    {
        $sql = new Sql;

        $results = $sql->select(
            'SELECT b.idproduct, b.desproduct, b.vlprice, b.vlwidth, b.vlheight, b.vllength, b.vlweight, b.desurl, 
            COUNT(*) AS nrqtd, SUM(b.vlprice) AS vltotal 
            FROM tb_cartsproducts a 
            INNER JOIN tb_products b ON a.idproduct = b.idproduct 
            WHERE a.idcart = :idcart 
            AND a.dtremoved IS NULL 
            GROUP BY b.idproduct, b.desproduct, b.vlprice, b.vlwidth, b.vlheight, b.vllength, b.vlweight, b.desurl 
            ORDER BY b.desproduct',
            [
                ':idcart' => $this->getidcart()
            ]
        );

        return Product::checkList($results);
    }

    public function getProductsTotals()
    {
        $sql = new Sql;
        
        $results = $sql->select(
            'SELECT SUM(vlprice) AS vlprice, 
            SUM(vlwidth) AS vlwidth, SUM(vlheight) AS vlheight, SUM(vllength) AS vllength, 
            SUM(vlweight) AS vlweight, COUNT(*) AS nrqtd 
            FROM db_ecommerce.tb_products a 
            INNER JOIN tb_cartsproducts b ON a.idproduct = b.idproduct 
            WHERE b.idcart = :idcart AND b.dtremoved IS NULL',
            [
                ':idcart' => $this->getidcart()
            ]
        );

        if (count($results)) return $results[0];
        return [];
    }

    public function setFreight($nrzipcode)
    {
        $nrzipcode = str_replace('-', '', $nrzipcode);
        $totals = $this->getProductsTotals();

        // verify dimensions
        // rules in:
        // https://www.correios.com.br/enviar-e-receber/precisa-de-ajuda/limites-de-dimensoes-e-peso

        if ($totals['vllength'] < 15.0) $totals['vllength'] = '15.0';
        if ($totals['vlwidth'] < 10.0) $totals['vlwidth'] = '10.0';
        if ($totals['vlheight'] < 1.0) $totals['vlheight'] = '1.0';

        if ($totals['nrqtd'] > 0) {
            $qs = http_build_query([
                'nCdEmpresa' => '',
                'sDsSenha' => '',
                'nCdServico' => '40010',
                'sCepOrigem' => '09750730',
                'sCepDestino' => $nrzipcode,
                'nVlPeso' => $totals['vlweight'],
                'nCdFormato' => '1',
                'nVlComprimento' => $totals['vllength'],
                'nVlAltura' => $totals['vlheight'],
                'nVlLargura' => $totals['vlwidth'],
                'nVlDiametro' => '0',
                'sCdMaoPropria' => 'S',
                'nVlValorDeclarado' => $totals['vlprice'],
                'sCdAvisoRecebimento' => 'S'
            ]);

            $url = 'http://ws.correios.com.br/calculador/CalcPrecoPrazo.asmx/CalcPrecoPrazo?';
            $xml = simplexml_load_file($url . $qs);

            $result = (array) $xml->Servicos->cServico;

            if ($result['MsgErro'] == '') {
                Cart::clearMsgError();
            } else {
                Cart::setMsgError($result['MsgErro']);
            }           

            $this->setnrdays($result['PrazoEntrega']);
            $this->setvlfreight(Cart::changeValueFormat($result['Valor']));
            $this->setdeszipcode($nrzipcode);
            $this->save();

            return $result;
        }
    }

    public static function changeValueFormat($value)
    {
        $value = str_replace('.', '', $value);
        $value = str_replace(',', '.', $value);

        return number_format($value, 2);
    }

    public static function setMsgError($msg)
    {
        $_SESSION[Cart::SESSION_ERROR] = $msg;
    }

    public static function getMsgError()
    {
        $msg = (isset($_SESSION[Cart::SESSION_ERROR])) ? 
                    $_SESSION[Cart::SESSION_ERROR] : false;
        
        Cart::clearMsgError();
        return $msg;
    }

    public static function clearMsgError()
    {
        $_SESSION[Cart::SESSION_ERROR] = NULL;
    }

    public function updateFreight()
    {
        if ($this->getdeszipcode() != '') {
            $this->setFreight($this->getdeszipcode());
        }
    }

    public function getValues()
    {
        $this->getCalculateTotal();
        return parent::getValues();
    }

    public function getCalculateTotal()
    {
        $this->updateFreight();

        $totals = $this->getProductsTotals();

        $this->setvlsubtotal($totals['vlprice']);
        $this->setvltotal($totals['vlprice'] + $this->getvlfreight());

    }
}