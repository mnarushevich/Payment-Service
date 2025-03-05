To install and configure Stripe CLI locally, follow the steps below:

1. Install Stripe CLI:
```yaml
brew install stripe/stripe-cli/stripe
```
2. Login to your Stripe account:
```yaml
stripe login
```
3. Start the Stripe CLI:
```yaml
stripe listen --forward-to localhost:4242/webhook
```
4. Copy the webhook signing secret from the CLI and add it to your `.env` file:
```yaml
STRIPE_WEBHOOK_SECRET=whsec_...
```
5. Add the webhook endpoint to your Stripe account:
```yaml
stripe listen --forward-to stripe listen --forward-to http://localhost:8702/api/v1/stripe/webhook
```
6. Test the webhook by sending a test event:
```yaml
stripe trigger payment_intent.created
```
7. Check the logs to see if the webhook was received:
```yaml
stripe logs tail
```

