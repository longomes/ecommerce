<?php

use \Hcode\PageAdmin;
use \Hcode\Model\User;
use \Hcode\Model\Order;
use \Hcode\Model\OrderStatus;

$app->get('/admin/orders/:idorder/delete', function($idorder) {
    User::verifyLogin();

    $order = new Order;
    $order->getById($idorder);
    $order->delete();
    
    header('Location: /admin/orders');
    exit;
});

$app->get('/admin/orders/:idorder/status', function($idorder) {
    User::verifyLogin();

    $order = new Order;
    $order->getById($idorder);
    
    $page = new PageAdmin;
    $page->setTpl('order-status', [
        'order' => $order->getValues(),
        'status' => OrderStatus::listAll(),
        'msgSuccess' => Order::getSuccess(),
        'msgError' => Order::getError(),
    ]);
});

$app->post('/admin/orders/:idorder/status', function($idorder) {
    User::verifyLogin();

    if (!isset($_POST['idstatus']) || !(int)$_POST['idstatus'] > 0) {
        Order::setError('Informe o status atual.');

        header("Location: /admin/orders/{$idorder}/status");
        exit;
    }

    $order = new Order;
    $order->getById($idorder);
    $order->setidstatus((int)$_POST['idstatus']);
    $order->save();

    Order::setSuccess('Status atualizado com sucesso.');

    header("Location: /admin/orders/{$idorder}/status");
    exit;
});

$app->get('/admin/orders/:idorder', function($idorder) {
    User::verifyLogin();

    $order = new Order;
    $order->getById($idorder);

    $cart = $order->getCart();
    
    $page = new PageAdmin;
    $page->setTpl('order', [
        'order' => $order->getValues(),
        'cart' => $cart->getValues(),
        'products' => $cart->getProducts()
    ]);
});

$app->get('/admin/orders', function() {
    User::verifyLogin();

    $search = (isset($_GET['search'])) ? $_GET['search'] : '';
	
	$idpage = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;
	
	if (trim($search) != '') {
		$orders = Order::getPagesSearch($search, $idpage);
	} else {
		$orders = Order::getPages($idpage);
	}

	$pages = [];

	for ($x=0; $x < $orders['pages']; $x++) {
		$build = [
			'page' => $x+1
		];

		if (trim($search)) $build['search'] = $search;

		array_push($pages, [
			'href' => '/admin/orders?' . http_build_query($build),
			'text' => $x+1
		]);
	}

    $page = new PageAdmin;
    $page->setTpl('orders', [
        'orders' => $orders['data'],
        'search' => $search,
		'pages' => $pages
    ]);
});