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

$app->get('/categories/:idcategory', function($idcategory) {
	$numPage = (isset($_GET['page'])) ? (int) $_GET['page'] : 1;

	$category = new Category;
	$category->getById((int)$idcategory);
	
	$pagination = $category->getProductsPage($numPage);
	$pages = [];

	for ($i=1; $i <= $pagination['pages']; $i++) {
		array_push($pages, [ 
			'link' => '/categories/' . 
						$category->getidcategory() . 
						'?page=' . $i,
			'page' => $i
		]);
	}

	$page = new Page;
	$page->setTpl('category', [
		'category' => $category->getValues(),
		'products' => $pagination['data'],
		'pages' => $pages
	]);
});