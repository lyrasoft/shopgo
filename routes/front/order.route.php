<?php

declare(strict_types=1);

namespace App\Routes;

use Lyrasoft\ShopGo\Module\Front\Order\OrderController;
use Lyrasoft\ShopGo\Module\Front\Order\OrderItemView;
use Lyrasoft\ShopGo\Module\Front\Order\OrderListView;
use Windwalker\Core\Router\RouteCreator;

/** @var RouteCreator $router */

$router->group('order')
    ->register(function (RouteCreator $router) {
        $router->any('order_list', '/order/list')
            ->controller(OrderController::class)
            ->view(OrderListView::class);

        $router->any('order_item', '/order/item/{no}')
            ->controller(OrderController::class)
            ->view(OrderItemView::class);
    });
