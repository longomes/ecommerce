<?php

function formatPrice(float $vlprice)
{
    return number_format($vlprice, 2, ',', '.');
}

function dump() 
{
    echo '<pre>';

    foreach (func_get_args() as $arg) {        
        var_dump($arg);        
    }

    echo '</pre>';
}