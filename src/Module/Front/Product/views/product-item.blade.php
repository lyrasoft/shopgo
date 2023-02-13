<?php

declare(strict_types=1);

namespace App\View;

/**
 * Global variables
 * --------------------------------------------------------------
 * @var $app       AppContext      Application context.
 * @var $vm        \Lyrasoft\ShopGo\Module\Front\Product\ProductItemView The view model object.
 * @var $uri       SystemUri       System Uri information.
 * @var $chronos   ChronosService  The chronos datetime service.
 * @var $nav       Navigator       Navigator object to build route.
 * @var $asset     AssetService    The Asset manage service.
 * @var $lang      LangService     The language translation service.
 */

use Lyrasoft\ShopGo\Module\Front\Product\ProductItemView;
use Lyrasoft\Luna\Entity\Category;
use Lyrasoft\ShopGo\Entity\Discount;
use Lyrasoft\ShopGo\Entity\Product;
use Lyrasoft\ShopGo\Entity\ProductAttribute;
use Lyrasoft\ShopGo\Entity\ProductFeature;
use Lyrasoft\ShopGo\Entity\ProductTab;
use Lyrasoft\ShopGo\Entity\ProductVariant;
use Lyrasoft\ShopGo\Script\ShopGoScript;
use Lyrasoft\ShopGo\Service\ProductAttributeService;
use Unicorn\Image\ImagePlaceholder;
use Unicorn\Script\UnicornScript;
use Unicorn\Script\VueScript;
use Windwalker\Core\Application\AppContext;
use Windwalker\Core\Asset\AssetService;
use Windwalker\Core\DateTime\ChronosService;
use Windwalker\Core\Language\LangService;
use Windwalker\Core\Router\Navigator;
use Windwalker\Core\Router\SystemUri;

/**
 * @var $item                 Product
 * @var $variant              ProductVariant
 * @var $category             Category
 * @var $discounts            Discount[]
 * @var $attrGroups           Category[]
 * @var $attribute            ProductAttribute
 * @var $tabs                 ProductTab[]
 * @var $features             ProductFeature[]
 * @var $additionalPurchases  ProductVariant[]
 */

$imagePlaceholder = $app->service(ImagePlaceholder::class);
$attributeService = $app->service(ProductAttributeService::class);

$shopGoScript = $app->service(ShopGoScript::class);
$shopGoScript->vueUtilities();
$shopGoScript->wishlistButton();
$shopGoScript->productCart();

$vueScript = $app->service(VueScript::class);
$vueScript->vue();
$vueScript->animate();

$uniScript = $app->service(UnicornScript::class);
$uniScript->data('product.item.props', [
    'product' => $item,
    'features' => $features,
    'mainVariant' => $variant,
    'discounts' => $discounts,
]);
$uniScript->data('image.default', $imagePlaceholder->placeholderSquare());

$uniScript->addRoute('@product_ajax');

$shopGoScript = $app->service(ShopGoScript::class);

$shopGoScript->swiper();
$shopGoScript->swiper(
    '.l-additional-purchases__slides',
    [
        'navigation' => [
            'nextEl' => '.swiper-button-next',
            'prevEl' => '.swiper-button-prev',
        ],
        'slidesPerView' => 4,
        'spaceBetween' => 15,
        'rewind' => true
    ]
);

?>

@extends('global.body')

@section('content')

    <div class="l-product-item container my-5">

        <div id="product-item-app" class="row">
            <div class="col-lg-6 l-product-item__images l-product-images mb-4 mb-lg-0">
                <div class="l-product-images__hero border ratio ratio-1x1">
                    <img :src="imageView" alt="Cover">
                </div>

                <div class="l-product-images__nav mt-2 c-product-images swiper"
                    ref="swiper">
                    <div class="swiper-wrapper">
                        <div v-for="image of images" class="c-product-image border swiper-slide"
                            style="cursor: pointer"
                            @mouseenter="imageView = image.url"
                        >
                            <div class="c-product-images__inner ratio ratio-1x1">
                                <img class=""
                                    :src="image.url"
                                    :alt="image.title || 'image'">
                            </div>
                        </div>
                    </div>
                    <div class="swiper-button-prev"></div>
                    <div class="swiper-button-next"></div>
                    <div class="swiper-pagination"></div>
                </div>
            </div>
            <div class="col-lg-6 l-product-item__info l-product-info">
                {{-- Header --}}
                <header class="l-product-info__header d-flex flex-column gap-2">
                    {{-- Todo: tags --}}

                    <div class="d-flex align-items-center">
                        {{-- Title --}}
                        <h1 class="l-product-info__title m-0">{{ $item->getTitle() }}</h1>

                        {{-- Wishlist --}}
                        <div class="ms-auto l-product-info__favorite">
                            <x-wishlist-button class="fs-4" :id="$item->getId()"
                                :added="(int) (bool) $wishlist">
                            </x-wishlist-button>
                        </div>
                    </div>

                    {{-- Category --}}
                    @if ($category)
                        <div class="l-product-info__category">
                            {{ $category->getTitle() }}
                        </div>
                    @endif

                    <div class="d-flex justify-content-between">
                        <div class="">
                            {{-- Model --}}
                            <div class="l-product-info__model c-info d-inline-block">
                                <span class="c-info__label fw-bold">
                                    型號
                                </span>

                                <span class="c-info__value">
                                    {{ $item->getModel() }}
                                </span>
                            </div>
                            /
                            {{-- SKU --}}
                            <div class="l-product-info__sku c-info d-inline-block">
                                <span class="c-info__label fw-bold">
                                    SKU
                                </span>

                                <span class="c-info__value">
                                    {{ $variant->getSku() }}
                                </span>
                            </div>
                        </div>

                        <div>
                            <span v-if="outOfStock" class="badge bg-danger">
                                @{{ currentVariant.outOfStockText || mainVariant.outOfStockText || '庫存不足' }}
                            </span>
                        </div>
                    </div>
                </header>

                {{-- Price --}}
                <div class="l-product-info__pricing l-pricing mt-4">
                    @if ($item->getOriginPrice())
                        <div class="l-pricing__final c-price--origin fs-5">
                            <span class="c-price__label">
                                市價
                            </span>
                            <del class="c-price__value">
                                {{ $vm->formatPrice($item->getOriginPrice()) }}
                            </del>
                        </div>
                    @endif

                    <div v-if="hasSubVariants && !currentVariant"
                        class="l-pricing__range c-price c-price--range fs-4 fw-bold">
                        @if ($minPrice !== $maxPrice)
                            {{ $vm->formatPrice($minPrice, true) }}
                            -
                        @endif
                        {{ $vm->formatPrice($maxPrice, true) }}
                    </div>

                    <template v-else>
                        <div class="l-pricing__final c-price c-price--base fs-5"
                            v-if="hasDiscount">
                            <span class="c-price__label">
                                售價
                            </span>
                            <del class="c-price__value">
                                @{{ $formatPrice(currentVariant.priceSet.base.price, true) }}
                            </del>
                        </div>

                        <div class="l-pricing__final c-price c-price--final fs-4 fw-bold">
                            <span class="c-price__label">
                                @{{ hasDiscount ? '優惠價' : '售價' }}
                            </span>
                            <span class="c-price__value">
                                @{{ $formatPrice(currentVariant.priceSet.final.price, true) }}
                            </span>
                        </div>
                    </template>
                </div>

                <hr />

                {{-- Intro --}}
                <div class="l-product-info__intro l-intro">
                    {!! $item->getIntro() !!}
                </div>

                {{-- Attributes --}}
                <div class="l-product-info__basic-info l-basic-info mt-4">
                    @foreach ($attrGroups as $group)
                        @if (!in_array('intro', $group->getParams()['position'] ?? [], true))
                            @continue
                        @endif

                        @php($attributes = $group->getParams()['attributes'] ?? [])

                        @foreach ($attributes as $attribute)
                            <div class="c-info">
                                <span class="c-info__label fw-bold">
                                    {{ $attribute->getTitle() }}
                                </span>

                                <span class="c-info__value">
                                    {{ $attributeService->renderValue($attribute) }}
                                </span>
                            </div>
                        @endforeach
                    @endforeach
                </div>

                {{-- Discounts --}}
                <div v-if="discounts.length" class="l-product-info__discounts l-discounts mt-4">
                    <h5>多件折扣</h5>

                    <div v-if="currentVariant">
                        <div v-for="discount of discountNotices">
                            <div>
                                購買 @{{ discount.minProductQuantity }} 件以上，每個只要
                                @{{ $formatPrice(discount.price, true) }}
                            </div>
                        </div>
                    </div>
                    <div v-else>
                        請先選擇規格
                    </div>
                </div>

                @if (count($features))
                    <div class="l-product-info__features l-features mt-4">
                        <div class="l-feature-item mb-3" v-for="feature of features">
                            <div class="l-feature-item__title mb-2">
                                <strong>@{{ feature.title }}</strong>
                            </div>
                            <div class="l-feature-item__options d-flex gap-1">
                                <button class="btn btn-outline-primary btn-sm"
                                    v-for="option of feature.options"
                                    @click="toggleOption(option, feature)"
                                    :class="{ active: isSelected(option, feature) }"
                                >
                                    @{{ option.text }}
                                </button>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Actions --}}
                <div class="l-product-info__actions l-actions mt-4">

                    {{-- Quantity --}}
                    <div class="l-actions__quantity">
                        <div class="input-group">
                            <button type="button" class="btn btn-secondary"
                                @click="quantity--">
                                <i class="fa fa-minus"></i>
                            </button>
                            <input type="text" class="form-control" v-model.number="quantity"
                                data-role="quantity"
                            />
                            <button type="button" class="btn btn-secondary"
                                @click="quantity++">
                                <i class="fa fa-plus"></i>
                            </button>
                        </div>
                    </div>

                    {{-- Buttons --}}
                    <div class="l-actions__buttons mt-3 d-grid d-lg-flex gap-2">
                        <button type="button" class="btn btn-primary btn-lg flex-fill"
                            data-task="buy"
                            :data-id="product.id"
                            :data-variant-id="currentVariant?.id"
                            :disabled="!currentVariant || outOfStock">
                            <i class="fa fa-cart-shopping"></i>
                            立即購買
                        </button>

                        <button type="button" class="btn btn-outline-primary btn-lg flex-fill"
                            data-task="add-to-cart"
                            :data-id="product.id"
                            :data-variant-id="currentVariant?.id"
                            :disabled="!currentVariant || outOfStock">
                            <i class="fa fa-cart-plus"></i>
                            加入購物車
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Additional Purchases --}}
        @if (count($additionalPurchases))
            <div class="l-additional-purchases mt-5">
                <h3>加價購</h3>

                <div class="l-additional-purchases__slides swiper">
                    <div class="l-additional-purchases__wrapper swiper-wrapper">
                        @foreach ($additionalPurchases as $additionalPurchase)
                            <div class="c-additional-purchase-product card swiper-slide">
                                <div class="card-body d-flex gap-2">
                                    <div class="c-additional-purchase-product__cover "
                                        style="width: 75px">
                                        <div class="ratio ratio-1x1">
                                            <img src="{{ $additionalPurchase->getCover() }}" alt="cover">
                                        </div>
                                    </div>
                                    <div>
                                        <h6 class="c-additional-purchase-product__title mb-1">
                                            {{ $additionalPurchase->product->title }}
                                        </h6>

                                        @if (!$additionalPurchase->isPrimary())
                                            <div class="c-additional-purchase-product__variant text-muted">
                                                {{ $additionalPurchase->getTitle() }}
                                            </div>
                                        @endif

                                        <div class="c-additional-purchase-product__price">
                                            {{ $vm->formatPrice($additionalPurchase->getPrice(), true) }}
                                        </div>

                                        <div class="c-additional-purchase-product__actions mt-2">
                                            <button class="btn btn-outline-primary btn-sm"
                                                data-task="addon"
                                                data-ap-map-id="{{ $additionalPurchase->ap_map->id }}"
                                            >
                                                加購
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="swiper-button-prev"></div>
                    <div class="swiper-button-next"></div>
                </div>
            </div>
        @endif

        {{-- Content --}}
        <div class="mt-5">
            <x-tabs>

                <x-tab name="description" title="商品介紹">
                    {!! $item->getDescription() !!}
                </x-tab>

                <x-tab name="attributes" title="商品規格">
                    @include('product-attributes')
                </x-tab>

                @foreach ($tabs as $tab)
                    <x-tab :name="'tab-' . $tab->getId()" :title="$tab->getTitle()">
                        @include('product-tab', compact('tab'))
                    </x-tab>
                @endforeach
            </x-tabs>
        </div>

    </div>

@stop
