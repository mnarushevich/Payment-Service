<?php

declare(strict_types=1);

use App\Http\Controllers\Checkout\CheckoutController;
use App\Http\Controllers\Customer\CreateCustomerController;
use App\Http\Controllers\HandleWebhookController;
use App\Http\Controllers\Payment\RefundChargeController;
use App\Http\Controllers\Payment\SingleChargeController;
use App\Http\Controllers\PaymentMethod\AddPaymentMethodController;
use App\Http\Controllers\Paymentmethod\GetPaymentMethodController;
use App\Http\Controllers\Subscription\CancelSubscriptionController;
use App\Http\Controllers\Subscription\CreateSubscriptionController;
use App\Http\Controllers\Subscription\EndSubscriptionTrialController;
use App\Http\Controllers\Subscription\ResumeSubscriptionController;
use Illuminate\Support\Facades\Route;

Route::get('/healthcheck', fn () => response()->json(['status' => 'ok']))->name('healthcheck');

Route::middleware(['auth.token'])->group(function (): void {
    Route::get('/payment-method/list', GetPaymentMethodController::class)->name('payment-method.list');
    Route::post('/payment-method/add', AddPaymentMethodController::class)->name('payment-method.add');

    Route::post('/customer', CreateCustomerController::class)->name('customer.create'); // TODO Convert to Kafka message listener

    Route::post('/subscription', CreateSubscriptionController::class)->name('subscription.create');
    Route::post('/subscription/cancel', CancelSubscriptionController::class)->name('subscription.cancel');
    Route::post('/subscription/resume', ResumeSubscriptionController::class)->name('subscription.resume');
    Route::post('/subscription/end-trial', EndSubscriptionTrialController::class)->name('subscription.end-trial');

    Route::post('/charge', SingleChargeController::class)->name('charge');
    Route::post('/charge/refund', RefundChargeController::class)->name('charge.refund');
});

Route::get('/payment/checkout', CheckoutController::class)->name('payment.checkout');

Route::get('/payment/success', fn () => response()->json(['message' => 'Payment was successful.']))->name('payment.success');

Route::get('/payment/cancel', fn () => response()->json(['message' => 'Payment was cancelled.']))->name('payment.cancel');

Route::post('/stripe/webhook', HandleWebhookController::class);
