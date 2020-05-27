<?php

use \Hcode\Page;
use \Hcode\Model\Category;
use \Hcode\Model\Product;

// Home
$app->get('/', function() {
	$products = Product::listAll();
	
	$page = new Page;
	$page->setTpl('main',[
		'products' => Product::checkList($products)
	]);
});

$app->get('/categories/:idcaategory', function($idcategory) {
	$category = new Category;
	$category->getById((int)$idcategory);

	$page = new Page;
	$page->setTpl('category', [
		'category' => $category->getValues(),
		'products' => []
	]);
});