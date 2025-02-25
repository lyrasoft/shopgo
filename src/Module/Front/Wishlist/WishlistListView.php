<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    MIT
 */

declare(strict_types=1);

namespace Lyrasoft\ShopGo\Module\Front\Wishlist;

use Lyrasoft\Luna\User\UserService;
use Lyrasoft\ShopGo\Repository\ProductRepository;
use Psr\Cache\InvalidArgumentException;
use Windwalker\Core\Application\AppContext;
use Windwalker\Core\Attributes\ViewModel;
use Windwalker\Core\Language\TranslatorTrait;
use Windwalker\Core\Router\Navigator;
use Windwalker\Core\Router\RouteUri;
use Windwalker\Core\View\View;
use Windwalker\Core\View\ViewModelInterface;
use Windwalker\DI\Attributes\Autowire;
use Windwalker\ORM\ORM;

/**
 * The WishlistListView class.
 */
#[ViewModel(
    layout: 'wishlist-list',
    js: 'wishlist-list.js'
)]
class WishlistListView implements ViewModelInterface
{
    use TranslatorTrait;

    /**
     * Constructor.
     */
    public function __construct(
        protected ORM $orm,
        #[Autowire]
        protected ProductRepository $repository,
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
     * @throws InvalidArgumentException
     */
    public function prepare(AppContext $app, View $view): RouteUri|array
    {
        if (!$this->userService->isLogin()) {
            return $this->nav->to('login')->withReturn();
        }

        $page = $app->input('page');
        $user = $this->userService->getUser();

        $items = $this->repository->getFrontListSelector($user)
            ->where('favorite.id', '!=', null)
            ->page($page)
            ->all();

        $this->prepareMetadata($app, $view);

        return compact('items');
    }

    protected function prepareMetadata(AppContext $app, View $view): void
    {
        $view->setTitle(
            $this->trans('shopgo.wishlist.title')
        );
    }
}
