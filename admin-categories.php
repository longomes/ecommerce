<?php

use \Hcode\PageAdmin;
use \Hcode\Model\Category;

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

$app->get('/categories/:idcaategory', function($idcategory) {
	$category = new Category;
	$category->getById((int)$idcategory);

	$page = new Page;
	$page->setTpl('category', [
		'category' => $category->getValues(),
		'products' => []
	]);
});