<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    MIT
 */

declare(strict_types=1);

namespace Lyrasoft\ShopGo\Module\Front\Order;

use Lyrasoft\Luna\User\UserService;
use Lyrasoft\ShopGo\Repository\OrderRepository;
use Lyrasoft\ShopGo\Traits\CurrencyAwareTrait;
use Windwalker\Core\Application\AppContext;
use Windwalker\Core\Attributes\ViewModel;
use Windwalker\Core\Language\TranslatorTrait;
use Windwalker\Core\Router\Navigator;
use Windwalker\Core\Router\RouteUri;
use Windwalker\Core\Security\Exception\UnauthorizedException;
use Windwalker\Core\View\View;
use Windwalker\Core\View\ViewModelInterface;
use Windwalker\Data\Collection;
use Windwalker\DI\Attributes\Autowire;
use Windwalker\ORM\ORM;

/**
 * The OrderListView class.
 */
#[ViewModel(
    layout: 'order-list',
    js: 'order-list.js'
)]
class OrderListView implements ViewModelInterface
{
    use TranslatorTrait;
    use CurrencyAwareTrait;

    /**
     * Constructor.
     */
    public function __construct(
        protected ORM $orm,
        #[Autowire]
        protected OrderRepository $repository,
        protected UserService $userService,
        protected Navigator $nav,
    ) {
        //
    }

    /**
     * Prepare View.
     *
     * @param  AppContext  $app   The web app context.
     * @param  View        $view  The view object.
     *
     * @return  RouteUri|array
     */
    public function prepare(AppContext $app, View $view): RouteUri|array
    {
        if (!$this->userService->isLogin()) {
            return $this->nav->to('login')->withReturn();
        }

        // Prepare Items
        $page = $app->input('page');

        $user = $this->userService->getUser();

        $items = $this->repository->getFrontListSelector($user)
            ->ordering('order.id', 'DESC')
            ->page($page)
            ->limit(15);

        $pagination = $items->getPagination();

        $this->prepareMetadata($app, $view);

        return compact('items', 'pagination');
    }

    public function prepareItem(Collection $item): object
    {
        return $this->repository->getEntityMapper()->toEntity($item);
    }

    protected function prepareMetadata(AppContext $app, View $view): void
    {
        $view->setTitle(
            $this->trans('shopgo.order.my.list.title')
        );
    }
}
