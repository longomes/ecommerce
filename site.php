<?php

use \Hcode\Page;
use \Hcode\Model\Category;

// Home
$app->get('/', function() {    
	$page = new Page;
	$page->setTpl('main');
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