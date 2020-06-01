<?php

use \Hcode\PageAdmin;
use \Hcode\Model\User;

$app->get('/admin/users', function() {
	User::verifyLogin();

	$search = (isset($_GET['search'])) ? $_GET['search'] : '';
	
	$idpage = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;
	
	if (trim($search) != '') {
		$users = User::getPagesSearch($search, $idpage);
	} else {
		$users = User::getPages($idpage);
	}

	$pages = [];

	for ($x=0; $x < $users['pages']; $x++) {
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
	$page->setTpl('users', [
		'users' => $users['data'],
		'search' => $search,
		'pages' => $pages
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