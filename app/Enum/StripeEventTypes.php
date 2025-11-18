<?php

declare(strict_types=1);

namespace App\Enum;

enum StripeEventTypes: string
{
    case INVOICE_CREATED = 'invoice.created';
    case INVOICE_UPDATED = 'invoice.updated';
    case INVOICE_PAYMENT_SUCCEEDED = 'invoice.payment_succeeded';
    case INVOICE_PAYMENT_FAILED = 'invoice.payment_failed';
    case CHARGE_PENDING = 'charge.pending';
    case CHARGE_SUCCEEDED = 'charge.succeeded';
    case CUSTOMER_CREATED = 'customer.created';
    case CUSTOMER_DELETED = 'customer.deleted';
    case CUSTOMER_SUBSCRIPTION_CREATED = 'customer.subscription.created';
    case CUSTOMER_SUBSCRIPTION_DELETED = 'customer.subscription.deleted';
    case CUSTOMER_SUBSCRIPTION_UPDATED = 'customer.subscription.updated';
    case CHARGE_DISPUTE_CREATE = 'charge.dispute.create';
    case CHARGE_DISPUTE_CREATED = 'charge.dispute.created';
    case CHARGE_REFUND_UPDATED = 'charge.refund.updated';
    case CHARGE_FAILED = 'charge.failed';
    case PAYMENT_METHOD_ATTACHED = 'payment_method.attached';
    case PAYMENT_METHOD_DETACHED = 'payment_method.detached';
}
