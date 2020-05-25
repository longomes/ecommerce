<?php

session_start();

require_once("vendor/autoload.php");

use \Slim\Slim;
use \Hcode\Page;
use \Hcode\PageAdmin;
use \Hcode\Model\User;

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
$app->get('/admin/users/:iduser/delete', function($iduser){
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
$app->post('/admin/users/create', function(){
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
$app->post('/admin/users/:iduser', function($iduser){
	User::verifyLogin();

	$_POST['inadmin'] = (isset($_POST['inadmin'])) ? 1 : 0;

	$user = new User;
	$user->getById((int)$iduser);
	$user->setValues($_POST);
	$user->update();

	header('Location: /admin/users');
	exit;
});

$app->run();