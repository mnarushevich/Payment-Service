## Payment Service

# Service for Handling  Stripe Subscriptions & Payments

Laravel based application that integrates **Stripe** for handling subscriptions and (one-time) payments using **Laravel Cashier**.

## Features

- **Subscription Management**: Create, update, and cancel Stripe subscriptions.
- **One-Time Payments and Refunds**: Accept single payments using Stripe Checkout.
- **Customer Management**: Store and manage customer details linked to Stripe.
- **Webhook Handling**: Process Stripe events for automatic billing updates.

## Installation

### Prerequisites
Ensure you have the following installed:
- PHP 8.4+
- Composer
- Laravel 11+
- MySQL or SQLite (for testing DB)
- Redis

### Setup Steps

1. **Clone the repository**
   ```sh
   git clone <repository_url>
   cd <repository_name>
   ```

2. **Install dependencies**
   ```sh
   composer install
   make setup-hooks
   ```

3. **Set up environment variables**
   ```sh
   cp .env.example .env
   ```
   Update the `.env` file with your database credentials and Stripe keys:
   ```env
   STRIPE_KEY=your_stripe_public_key
   STRIPE_SECRET=your_stripe_secret_key
   STRIPE_WEBHOOK_SECRET=your_stripe_webhook_secret
   ```

4. **Run migrations**
   ```sh
   php artisan migrate
   ```

5. **Link storage (for invoice handling, if applicable)**
   ```sh
   php artisan storage:link
   ```

6. **Generate application key**
   ```sh
   php artisan key:generate
   ```

7. **Serve the application**
   ```sh
   php artisan serve
   ```

## Stripe Webhooks

To ensure your application correctly processes payments and subscriptions, set up Stripe webhooks:

```sh
php artisan cashier:webhook
```

Then, configure Stripe to send webhooks to:
```
https://your-domain.com/stripe/webhook
```

## Usage

### Creating a Subscription
To subscribe a user, use the following:
```php
$user = Auth::user();
$user->newSubscription('default', 'price_id')->create($paymentMethod);
```

### Cancelling a Subscription
```php
$user->subscription('default')->cancel();
```

### Checking Subscription Status
```php
if ($user->subscribed('default')) {
    // User is subscribed
}
```

### One-Time Payment
```php
$checkout = $user->charge(5000, 'payment_method_id');
```

## Security Considerations
- Always validate webhook signatures.
- Store Stripe API keys securely (never commit them to version control).
- Implement proper authentication and authorization for handling payments.

## License
This project is licensed under the MIT License.

---

For more information on Laravel Cashier and Stripe, refer to the official documentation:
- [Laravel Cashier Docs](https://laravel.com/docs/cashier)
- [Stripe API Docs](https://stripe.com/docs/api)
