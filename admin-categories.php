<?php

use \Hcode\PageAdmin;
use \Hcode\Model\Category;
use \Hcode\Model\User;
use \Hcode\Model\Product;

$app->get('/admin/categories', function() {
	$categories = Category::listAll();

	$page = new PageAdmin;
	$page->setTpl('categories', [
		'categories' => $categories
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