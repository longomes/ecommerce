<?php

error_reporting(E_ALL);
ini_set('display_errors', true);

session_start();

require_once("vendor/autoload.php");

use \Slim\Slim;
use \Hcode\Page;
use \Hcode\PageAdmin;
use \Hcode\Model\User;
use \Hcode\Model\Category;

$app = new Slim;

$app->config('debug', true);

$app->get('/', function() {    
	$page = new Page;
	$page->setTpl('main');
});

$app->get('/admin', function() {
	User::verifyLogin();
	
	$page = new PageAdmin;
	$page->setTpl('main');
});

$app->get('/admin/login', function() {    
	$page = new PageAdmin([
		'header' => false,
		'footer' => false
	]);
	$page->setTpl('login');
});

$app->post('/admin/login', function() {
	User::login($_POST['login'], $_POST['password']);
	header ('Location: /admin');
	exit;
});

$app->get('/admin/logout', function() {
	User::logout();
	header ('Location: /admin');
	exit;
});

$app->get('/admin/users', function() {
	User::verifyLogin();

	$users = User::listAll();

	$page = new PageAdmin;
	$page->setTpl('users', [
		'users' => $users
	]);
});

// Sign
$app->get('/admin/users/create', function() {
	User::verifyLogin();

	$page = new PageAdmin;
	$page->setTpl('users-create');
});

// Delete
$app->get('/admin/users/:iduser/delete', function($iduser) {
	User::verifyLogin();

	$user = new User;
	$user->getById((int)$iduser);
	$user->delete();

	header('Location: /admin/users');
	exit;
});

// Edit
$app->get('/admin/users/:iduser', function($iduser) {
	User::verifyLogin();

	$user = new User;
	$user->getById((int)$iduser);

	$page = new PageAdmin;
	$page->setTpl('users-update', [
		'user' => $user->getValues()
	]);
});

// Insert
$app->post('/admin/users/create', function() {
	User::verifyLogin();

	$_POST['inadmin'] = (isset($_POST['inadmin'])) ? 1 : 0;
	$_POST['despassword'] = password_hash(
		$_POST['despassword'], 
		PASSWORD_DEFAULT, 
		['cost' => 12]
	);

	$user = new User;
	$user->setValues($_POST);
	$user->save();

	header('Location: /admin/users');
	exit;
});

// Update
$app->post('/admin/users/:iduser', function($iduser) {
	User::verifyLogin();

	$_POST['inadmin'] = (isset($_POST['inadmin'])) ? 1 : 0;

	$user = new User;
	$user->getById((int)$iduser);
	$user->setValues($_POST);
	$user->update();

	header('Location: /admin/users');
	exit;
});

$app->get('/admin/forgot', function() {
	$page = new PageAdmin([
		'header' => false,
		'footer' => false
	]);
	$page->setTpl('forgot');
});

$app->post('/admin/forgot', function() {
	$user = User::getForgot($_POST['email']);

	header('Location: /admin/forgot/sent');
	exit;
});

$app->get('/admin/forgot/sent', function() {
	$page = new PageAdmin([
		'header' => false,
		'footer' => false
	]);
	$page->setTpl('forgot-sent');
});

$app->get('/admin/forgot/reset', function(){
	$user = User::validForgotDecrypt($_GET['code']);
	
	$page = new PageAdmin([
		'header' => false,
		'footer' => false
	]);
	
	$page->setTpl('forgot-reset', [
		'name' => $user['desperson'],
		'code' => $_GET['code']
	]);
});

$app->post('/admin/forgot/reset', function(){
	$forgot = User::validForgotDecrypt($_POST['code']);

	User::setForgotUsed($forgot['idrecovery']);

	$user = new User;
	$user->getById((int)$forgot['iduser']);
	$user->setPassword($_POST['password']);

	$page = new PageAdmin([
		'header' => false,
		'footer' => false
	]);
	
	$page->setTpl('forgot-reset-success');
});

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

$app->run();