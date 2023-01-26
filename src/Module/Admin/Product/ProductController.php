<?php

/**
 * Part of starter project.
 *
 * @copyright  Copyright (C) 2021 __ORGANIZATION__.
 * @license    __LICENSE__
 */

declare(strict_types=1);

namespace App\Module\Admin\Product;

use App\Entity\Product;
use App\Entity\ProductFeature;
use App\Entity\ProductVariant;
use App\Module\Admin\Product\Form\EditForm;
use App\Repository\ProductRepository;
use App\Service\VariantService;
use Unicorn\Controller\CrudController;
use Unicorn\Controller\GridController;
use Unicorn\Repository\Event\PrepareSaveEvent;
use Windwalker\Core\Application\AppContext;
use Windwalker\Core\Attributes\Controller;
use Windwalker\Core\Attributes\JsonApi;
use Windwalker\Core\Router\Navigator;
use Windwalker\Data\Collection;
use Windwalker\DI\Attributes\Autowire;
use Windwalker\ORM\Event\AfterSaveEvent;
use Windwalker\ORM\Event\BeforeSaveEvent;
use Windwalker\ORM\ORM;

use function Windwalker\collect;

/**
 * The ProductController class.
 */
#[Controller()]
class ProductController
{
    public function save(
        AppContext $app,
        CrudController $controller,
        Navigator $nav,
        #[Autowire] ProductRepository $repository,
    ): mixed {
        $form = $app->make(EditForm::class);

        $controller->prepareSave(
            function (PrepareSaveEvent $event) use ($app) {
                $data = &$event->getData();
            }
        );

        $controller->beforeSave(
            function (BeforeSaveEvent $event) use ($app) {
                $data = &$event->getData();
            }
        );

        $controller->afterSave(
            function (AfterSaveEvent $event) use ($app) {
                $orm = $event->getORM();
                $data = $event->getData();
                $entity = $event->getEntity();

                $variantData = $app->input('item')['variant'];

                // MainVariant
                $mainVariant = $orm->findOneOrCreate(
                    ProductVariant::class,
                    ['product_id' => $data['id'], 'primary' => 1]
                );

                $mainVariant = $orm->hydrateEntity($variantData, $mainVariant);

                $orm->updateOne(ProductVariant::class, $mainVariant);

                // Sub Variants
                $variants = $app->input('variants');

                $variants = collect(
                    json_decode($variants, true, 512, JSON_THROW_ON_ERROR)
                );

                $variants = $variants->map(fn ($variant) => $orm->toEntity(ProductVariant::class, $variant));

                $orm->sync(
                    ProductVariant::class,
                    $variants,
                    ['product_id' => $data['id'], 'primary' => 0],
                    ['id']
                );
            }
        );

        $uri = $app->call([$controller, 'save'], compact('repository', 'form'));

        switch ($app->input('task')) {
            case 'save2close':
                return $nav->to('product_list');

            case 'save2new':
                return $nav->to('product_edit')->var('new', 1);

            case 'save2copy':
                $controller->rememberForClone($app, $repository);
                return $nav->self($nav::WITHOUT_VARS)->var('new', 1);

            default:
                return $uri;
        }
    }

    public function delete(
        AppContext $app,
        #[Autowire] ProductRepository $repository,
        CrudController $controller
    ): mixed {
        return $app->call([$controller, 'delete'], compact('repository'));
    }

    public function filter(
        AppContext $app,
        #[Autowire] ProductRepository $repository,
        GridController $controller
    ): mixed {
        return $app->call([$controller, 'filter'], compact('repository'));
    }

    public function batch(
        AppContext $app,
        #[Autowire] ProductRepository $repository,
        GridController $controller
    ): mixed {
        $task = $app->input('task');
        $data = match ($task) {
            'publish' => ['state' => 1],
            'unpublish' => ['state' => 0],
            default => null
        };

        return $app->call([$controller, 'batch'], compact('repository', 'data'));
    }

    public function copy(
        AppContext $app,
        #[Autowire] ProductRepository $repository,
        GridController $controller
    ): mixed {
        return $app->call([$controller, 'copy'], compact('repository'));
    }

    #[JsonApi]
    public function ajax(AppContext $app): mixed
    {
        $task = $app->input('task');

        return $app->call([$this, $task]);
    }

    public function getFeatureOptions(ORM $orm): Collection
    {
        return $orm->from(ProductFeature::class)
            ->where('state', 1)
            ->all(ProductFeature::class);
    }

    /**
     * @param  AppContext      $app
     * @param  ORM             $orm
     * @param  VariantService  $variantService
     *
     * @return  array<ProductVariant>
     */
    public function generateVariants(AppContext $app, ORM $orm, #[Autowire] VariantService $variantService): array
    {
        $productId = $app->input('product_id');
        $featureOptionGroup = $app->input('options') ?? [];
        $currentHashes = (array) ($app->input('currentHashes') ?? []);

        $featureOptionGroup = array_filter($featureOptionGroup, static fn ($options) => $options !== []);

        $optionGroups = $variantService->sortOptionsGroup($featureOptionGroup);

        $oldVariants = $orm->findList(
            ProductVariant::class,
            ['product_id' => $productId ?: 0, 'primary' => 0]
        )->all();

        $variants = [];

        foreach ($optionGroups as $optionGroup) {
            usort(
                $optionGroup,
                static fn ($a, $b) => strcmp($a['value'], $b['value'])
            );

            $values = array_map(static fn ($option) => $option['value'], $optionGroup);
            $texts = array_map(static fn ($option) => $option['text'], $optionGroup);

            $hash = $variantService::hash($values);

            if (in_array($hash, $currentHashes, true)) {
                continue;
            }

            $variant = new ProductVariant();
            $variant->setProductId($productId);
            $variant->setHash($hash);
            $variant->setTitle(implode(' / ', $texts));
            // $variant->model = $product->model === ''
            //     ? $product->model
            //     : $product->model . '-' . implode('-', $optionGroup);
            $variant->getDimension(); // Pre-create ValueObject
            $variant->setSubtract(true);
            $variant->setState(1);
            $variant->setOptions($values);

            $variants[] = $variant;
        }

        return $variants;
    }
}
