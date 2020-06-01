<?php

use \Hcode\PageAdmin;
use \Hcode\Model\Category;
use \Hcode\Model\User;
use \Hcode\Model\Product;

$app->get('/admin/categories', function() {
	User::verifyLogin();

	$search = (isset($_GET['search'])) ? $_GET['search'] : '';
	
	$idpage = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;
	
	if (trim($search) != '') {
		$categories = Category::getPagesSearch($search, $idpage);
	} else {
		$categories = Category::getPages($idpage);
	}

	$pages = [];

	for ($x=0; $x < $categories['pages']; $x++) {
		$build = [
			'page' => $x+1
		];

		if (trim($search)) $build['search'] = $search;

		array_push($pages, [
			'href' => '/admin/users?' . http_build_query($build),
			'text' => $x+1
		]);
	}	

	$page = new PageAdmin;
	$page->setTpl('categories', [
		'categories' => $categories['data'],
		'search' => $search,
		'pages' => $pages
	]);
});

$app->get('/admin/categories/create', function() {
	User::verifyLogin();
	
	$page = new PageAdmin;
	$page->setTpl('categories-create');
});

$app->post('/admin/categories/create', function() {
	User::verifyLogin();

	$category = new Category;
	$category->setValues($_POST);
	$category->save();

	header('Location: /admin/categories');
	exit;
});

$app->get('/admin/categories/:idcategory/delete', function($idcategory) {
	User::verifyLogin();

	$category = new Category;
	$category->getById((int)$idcategory);
	$category->delete();

	header('Location: /admin/categories');
	exit;
});

$app->get('/admin/categories/:idcategory', function($idcategory) {
	User::verifyLogin();

	$category = new Category;
	$category->getById((int)$idcategory);

	$page = new PageAdmin;
	$page->setTpl('categories-update', [
		'category' => $category->getValues()
	]);
});

$app->post('/admin/categories/:idcategory', function($idcategory) {
	User::verifyLogin();

	$category = new Category;
	$category->getById((int)$idcategory);
	$category->setValues($_POST);
	$category->save();

	header('Location: /admin/categories');
	exit;
});

$app->get('/admin/categories/:idcategory/products', function($idcategory) {
	User::verifyLogin();
	
	$category = new Category;
	$category->getById((int)$idcategory);

	$page = new PageAdmin;
	$page->setTpl('categories-products', [
		'category' => $category->getValues(),
		'productsRelated' => $category->getRelated(),
		'productsNotRelated' => $category->getRelated(false)
	]);
});

$app->get('/admin/categories/:idcategory/products/:idproduct/add', function($idcategory, $idproduct) {
	User::verifyLogin();
	
	$category = new Category;
	$category->getById((int)$idcategory);
	
	$product = new Product;
	$product->getById($idproduct);

	$category->addProduct($product);
	
	header('Location: /admin/categories/' . $idcategory . '/products');
	exit;
});

$app->get('/admin/categories/:idcategory/products/:idproduct/remove', 
	function($idcategory, $idproduct) {
		User::verifyLogin();
		
		$category = new Category;
		$category->getById((int)$idcategory);
		
		$product = new Product;
		$product->getById($idproduct);

		$category->removeProduct($product);
		
		header('Location: /admin/categories/' . $idcategory . '/products');
		exit;
	}
);