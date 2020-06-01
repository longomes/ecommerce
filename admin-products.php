<?php

use \Hcode\PageAdmin;
use \Hcode\Model\User;
use \Hcode\Model\Product;

$app->get('/admin/products', function() {
    User::verifyLogin();

    $search = (isset($_GET['search'])) ? $_GET['search'] : '';
	
	$idpage = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;
	
	if (trim($search) != '') {
		$products = Product::getPagesSearch($search, $idpage);
	} else {
		$products = Product::getPages($idpage);
	}

	$pages = [];

	for ($x=0; $x < $products['pages']; $x++) {
		$build = [
			'page' => $x+1
		];

		if (trim($search)) $build['search'] = $search;

		array_push($pages, [
			'href' => '/admin/products?' . http_build_query($build),
			'text' => $x+1
		]);
	}

    $page = new PageAdmin;
    $page->setTpl('products', [
        'products' => $products['data'],
		'search' => $search,
		'pages' => $pages
    ]);
});

$app->get('/admin/products/create', function() {
    User::verifyLogin();

    $page = new PageAdmin;
    $page->setTpl('products-create');
});

$app->post('/admin/products/create', function() {
    User::verifyLogin();
    
    $product = new Product;
    $product->setValues($_POST);
    $product->save();

    header('Location: /admin/products');
    exit;
});

$app->get('/admin/products/:idproduct', function($idproduct) {
    User::verifyLogin();
    
    $product = new Product;
    $product->getById((int)$idproduct);

    $page = new PageAdmin;
    $page->setTpl('products-update', [
        'product' => $product->getValues()
    ]);
});

$app->post('/admin/products/:idproduct', function($idproduct) {
    User::verifyLogin();
    
    $product = new Product;
    $product->getById((int)$idproduct);
    $product->setValues();
    $product->save();
    $product->setPhoto($_FILES['file']);

    header('Location: /admin/products');
    exit;
});

$app->get('/admin/products/:idproduct/delete', function($idproduct) {
    User::verifyLogin();
    
    $product = new Product;
    $product->getById((int)$idproduct);
    $product->delete();

    header('Location: /admin/products');
    exit;
});