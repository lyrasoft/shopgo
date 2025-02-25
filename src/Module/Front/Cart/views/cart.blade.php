<?php

declare(strict_types=1);

namespace App\View;

/**
 * Global variables
 * --------------------------------------------------------------
 * @var $app       AppContext      Application context.
 * @var $vm        object          The view model object.
 * @var $uri       SystemUri       System Uri information.
 * @var $chronos   ChronosService  The chronos datetime service.
 * @var $nav       Navigator       Navigator object to build route.
 * @var $asset     AssetService    The Asset manage service.
 * @var $lang      LangService     The language translation service.
 */

use Lyrasoft\Luna\User\UserService;
use Lyrasoft\ShopGo\Script\ShopGoScript;
use Lyrasoft\ShopGo\Service\LocationService;
use Lyrasoft\ShopGo\ShopGoPackage;
use Unicorn\Image\ImagePlaceholder;
use Unicorn\Script\UnicornScript;
use Unicorn\Script\VueScript;
use Windwalker\Core\Application\AppContext;
use Windwalker\Core\Asset\AssetService;
use Windwalker\Core\DateTime\ChronosService;
use Windwalker\Core\Language\LangService;
use Windwalker\Core\Router\Navigator;
use Windwalker\Core\Router\SystemUri;

$shopGo = $app->service(ShopGoPackage::class);
$imagePlaceholder = $app->service(ImagePlaceholder::class);
$userService = $app->service(UserService::class);

$shopGoScript = $app->service(ShopGoScript::class);
$shopGoScript->vueUtilities();
$shopGoScript->productCart();

$vueScript = $app->service(VueScript::class);
$vueScript->vue();
$vueScript->animate();

$locationService = $app->service(LocationService::class);

$uniScript = $app->service(UnicornScript::class);
$uniScript->data('cart.props', [
    'user' => $userService->isLogin() ? $userService->getUser() : null,
    'checkoutData' => $checkoutData ?: new \stdClass(),
]);
$uniScript->data('image.default', $imagePlaceholder->placeholderSquare());
$uniScript->data('location.labels', $locationService->getSelectorLabels());
$uniScript->data('partial.checkout', (bool) $shopGo->config('checkout.partial_checkout'));

$uniScript->addRoute('@home');
$uniScript->addRoute('@cart_ajax');
$uniScript->addRoute('@address_ajax');

$uniScript->translate('unicorn.select.placeholder');
?>

@extends('global.body')

@section('content')
    <div class="l-cart-page container my-5">
        <cart-app v-cloak>
            <form id="cart-form" ref="form" action="{{ $nav->to('checkout_shipping') }}" method="post"
                style="--sidebar-offsets-top: 90px; --sidebar-offsets-bottom: 30px">
                <div class="row">
                    <div class="col-lg-8 l-cart-page__content">
                        {{-- Header --}}
                        <header class="d-flex align-items-center justify-content-between mb-4">
                            <div class="d-flex align-items-center gap-2">
                                <h3 class="m-0">@lang('shopgo.cart.title')</h3>
                                <div v-if="partialCheckout" class="form-check">
                                    <input id="input-toggle-all" type="checkbox" class="form-check-input"
                                        ref="toggleAllInput"
                                        @click="toggleChecked"
                                    />
                                    <label for="input-toggle-all" class="form-check-label">
                                        @lang('shopgo.cart.toggle.all')
                                    </label>
                                </div>
                                <div v-if="loading" class="spinner spinner-border-sm spinner-border"
                                    data-cloak>

                                </div>
                            </div>

                            <div>
                                <a href="javascript://"
                                    @click="clearCart">
                                    <i class="fa fa-times"></i>
                                    @lang('shopgo.cart.button.remove.all')
                                </a>
                            </div>
                        </header>

                        {{-- Body Loading --}}
                        <div data-loading>
                            <div class="d-flex py-5">
                                <span class="spinner spinner-grow spinner-lg mx-auto"></span>
                            </div>
                        </div>

                        <div class="l-cart-data" data-cloak>

                            {{-- Cart Items --}}
                            <x-cart-items></x-cart-items>

                            {{-- Addresses --}}
                            <div class="">
                                <address-form type="payment"
                                    title="@lang('shopgo.cart.payment.data.title')"
                                    :user="user"
                                    v-model="paymentData"
                                    ref="paymentForm"
                                ></address-form>
                                <address-form type="shipping"
                                    title="@lang('shopgo.cart.shipping.data.title')"
                                    :user="user"
                                    v-model="shippingData"
                                    :sync-data="paymentData"
                                    ref="shippingForm"
                                ></address-form>
                            </div>

                            {{-- Shippings --}}
                            <div class="l-shippings mb-4">
                                <h3>@lang('shopgo.cart.shipping.title')</h3>

                                <div v-if="shippings.length > 0">
                                    <shipping-item v-for="(shipping, i) of shippings" :key="shipping.id"
                                        style="animation-duration: .1s"
                                        :shipping="shipping"
                                        :i="i"
                                        :selected="shippingId === shipping.id"
                                        @selected="shippingId = shipping.id"
                                    >
                                    </shipping-item>
                                </div>
                                <div v-else class="card bg-light">
                                    <div class="card-body py-5 text-center">
                                        <template v-if="loading">
                                            <span class="spinner spinner-border"></span>
                                        </template>
                                        <template v-else-if="shippingData.locationId">
                                            @lang('shopgo.cart.text.no.shippings')
                                        </template>
                                        <template v-else>
                                            @lang('shopgo.cart.text.select.location.first')
                                        </template>
                                    </div>
                                </div>
                            </div>

                            {{-- Payments --}}
                            <div class="l-payments mb-4">
                                <h3>@lang('shopgo.cart.payment.data.title')</h3>

                                <div v-if="payments.length > 0">
                                    <payment-item v-for="(payment, i) of payments" :key="payment.id"
                                        style="animation-duration: .1s"
                                        :payment="payment"
                                        :i="i"
                                        :selected="paymentId === payment.id"
                                        @selected="paymentId = payment.id"
                                    >
                                    </payment-item>
                                </div>
                                <div v-else class="card bg-light">
                                    <div class="card-body py-5 text-center">
                                        <template v-if="loading">
                                            <span class="spinner spinner-border"></span>
                                        </template>
                                        <template v-else-if="shippingData.shippingId">
                                            @lang('shopgo.cart.text.no.payments')
                                        </template>
                                        <template v-else>
                                            @lang('shopgo.cart.text.select.shipping.first')
                                        </template>
                                    </div>
                                </div>
                            </div>

                            {{-- Note --}}
                            <div class="l-checkout-note card mb-4">
                                <div class="card-body">
                                    <h5 class="card-title mb-3">
                                        @lang('shopgo.cart.field.note')
                                    </h5>

                                    <textarea rows="4"
                                        class="form-control"
                                        v-model="note"
                                        name="checkout[note]"
                                        placeholder="@lang('shopgo.cart.field.note.placeholder')"
                                    ></textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Sidebar --}}
                    <div class="col-lg-4 l-cart-page__sidebar">
                        <div class="l-cart-sidebar position-sticky"
                            style="top: var(--sidebar-offsets-top, 90px);"
                        >
                            <div class="card">
                                {{-- Code Input --}}
                                <div class="card-body l-cart-coupons border-bottom">
                                    <h5>@lang('shopgo.cart.label.discount.code')</h5>
                                    <div class="d-flex gap-2">
                                        <input type="text" class="form-control" v-model="code" />
                                        <button type="button" class="btn btn-secondary text-nowrap"
                                            style="min-width: 100px"
                                            @click="addCode"
                                            :disabled="code === '' || loading"
                                            disabled
                                        >
                                            @lang('shopgo.cart.button.use.discount.code')
                                        </button>
                                    </div>

                                    {{-- Coupons --}}
                                    <div v-if="coupons.length" data-cloak class="list-group list-group-flush mt-4">
                                        <div v-for="coupon of coupons" class="list-group-item border-top d-flex">
                                            <div>
                                                <div>
                                                    <strong>
                                                        @{{ coupon.title }}
                                                    </strong>
                                                </div>
                                                <div class="small text-muted">
                                                    @{{ coupon.code }}
                                                </div>
                                            </div>

                                            <div class="ms-auto">
                                                <a href="javascript://"
                                                    class="link-secondary"
                                                    v-tooltip
                                                    title="@lang('shopgo.cart.button.remove.discount.code')"
                                                    @click="removeCode(coupon.id)">
                                                    <i class="fa fa-trash"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Totals Loading --}}
                                <div v-if="!loaded" class="card-body">
                                    <div class="card-text placeholder-glow d-flex my-2">
                                        <span class="placeholder col-4"></span>
                                        <span class="placeholder col-3 ms-auto"></span>
                                    </div>
                                </div>

                                {{-- Totals --}}
                                <div v-if="loaded" data-cloak class="card-body l-cart-totals text-end">
                                    <div class="l-cart-total d-flex justify-content-between gap-1 mb-1 w-100">
                                        <div class="l-cart-total__label">
                                            @lang('shopgo.cart.label.total')
                                        </div>

                                        <div v-if="totals.total" class="l-cart-total__value">
                                            @{{ $formatPrice(totals.total.price, true) }}
                                        </div>
                                    </div>

                                    <div class="l-cart-total d-flex justify-content-between gap-1 mb-1 w-100"
                                        v-for="total of filteredTotals">
                                        <div class="l-cart-total__label d-flex gap-2">
                                            <div>
                                                @{{ total.label }}
                                            </div>
                                            <div
                                                v-if="total.params.type === 'coupon' || total.params.subtype === 'code'">
                                                <small>(@{{ total.params.code }})</small>
                                            </div>
                                        </div>

                                        <div class="l-cart-total__value">
                                            @{{ $formatPrice(total.price, true) }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Checkbox --}}
                            <div class="card mt-3 position-sticky"
                                style="bottom: 0;">
                                <div class="card-body d-grid gap-3">
                                    {{-- Grand Total --}}
                                    <div v-if="loaded"
                                        class="l-cart-total d-flex justify-content-between gap-1 w-100 fs-5 fw-bold"
                                        data-cloak>
                                        <div class="l-cart-total__label">
                                            @lang('shopgo.cart.label.grand.total')
                                        </div>

                                        <div v-if="totals.grand_total" class="l-cart-total__value text-end">
                                            <div>
                                                @{{ $formatPrice(totals.grand_total.price, true) }}
                                            </div>
                                            <div v-if="$currency.isSubCurrency()" class="mt-1 small text-muted fw-normal">
                                                (@{{ $currency.formatMainCurrency(totals.grand_total.price, true) }})
                                            </div>
                                        </div>
                                    </div>

                                    {{-- Shipping / Payment Info --}}
                                    <div v-if="loaded" class="d-flex justify-content-between"
                                        data-cloak>
                                        <div>
                                            <i class="fa fa-truck"></i>
                                            @{{ selectedShipping?.title || '@lang('shopgo.message.no.shipping.selected')' }}
                                        </div>

                                        <div>
                                            <i class="fa fa-credit-card"></i>
                                            @{{ selectedPayment?.title || '@lang('shopgo.message.no.payment.selected')' }}
                                        </div>
                                    </div>

                                    {{-- Loading --}}
                                    <div v-if="!loaded">
                                        <div class="card-text placeholder-glow d-flex mb-1" style="height: 1.25rem;">
                                            <span class="placeholder col-3"></span>
                                            <span class="placeholder col-4 ms-auto"></span>
                                        </div>
                                    </div>

                                    {{-- Loading --}}
                                    <div v-if="!loaded">
                                        <div class="card-text placeholder-glow d-flex">
                                            <span class="placeholder col-3"></span>
                                            <span class="placeholder col-3 ms-auto"></span>
                                        </div>
                                    </div>

                                    {{-- Checkout Button --}}
                                    <button type="button" class="btn btn-primary btn-lg"
                                        :disabled="loading || !canCheckout"
                                        disabled
                                        @click="checkout"
                                    >
                                        <div data-cloak>
                                            <template v-if="loading">
                                                <span class="spinner spinner-grow spinner-grow-sm"></span>
                                            </template>
                                            <template v-else>
                                                @lang('shopgo.cart.button.process.checkout')
                                            </template>
                                        </div>
                                        <div v-if="!loading" data-loading>
                                            <span class="spinner spinner-grow spinner-grow-sm"></span>
                                        </div>
                                    </button>
                                </div>
                            </div>
                        </div>
                        {{-- End Sidebar--}}
                    </div>
                </div>

                <div class="d-none">
                    <x-csrf></x-csrf>
                </div>
            </form>
        </cart-app>
    </div>

    <x-address-form></x-address-form>
    <x-shipping-item></x-shipping-item>
    <x-payment-item></x-payment-item>
    <x-vue.cascade-select></x-vue.cascade-select>
@stop
