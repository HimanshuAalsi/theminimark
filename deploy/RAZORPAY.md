# Razorpay payments (India)

The Minimark uses [Razorpay Checkout](https://razorpay.com/docs/payments/payment-gateway/web-integration/standard/) for **INR** payments: UPI, cards, netbanking, and wallets.

## 1. Razorpay account

1. Sign up at [https://razorpay.com](https://razorpay.com)
2. Complete KYC to accept **live** payments
3. Dashboard → **Account & Settings** → **API Keys**
   - **Test mode**: for testing (no real money)
   - **Live mode**: for production after KYC

You need:

- **Key ID** (starts with `rzp_test_` or `rzp_live_`)
- **Key Secret** (shown once — store safely)

## 2. Database migration (existing sites)

In **phpMyAdmin**, import:

`backend/sql/migration_payment_razorpay.sql`

Skip if you created the database from an updated `schema.sql` that already includes `razorpay_order_id`.

## 3. Server config

Edit `public_html/api/config.local.php`:

```php
'razorpay' => [
    'enabled' => true,
    'key_id' => 'rzp_test_xxxxxxxx',      // or rzp_live_ for production
    'key_secret' => 'your_secret_here',
    'company_name' => 'The Minimark',
],
```

Upload changed PHP files:

- `api/lib/razorpay.php`
- `api/lib/orders.php`
- `api/index.php`
- `api/bootstrap.php`

## 4. Rebuild and upload frontend

```bash
npm run build --prefix frontend
```

Upload `frontend/dist/*` to `public_html/`.

## 5. Test

1. Use **test keys** and [Razorpay test cards/UPI](https://razorpay.com/docs/payments/payments/test-card-upi-details/)
2. Add items → **Checkout** → **Pay with Razorpay**
3. Complete payment in the popup
4. Confirm order success and in phpMyAdmin: `orders.status` = `paid`

API checks:

- `GET https://theminimark.com/api/v1/payments/razorpay/config` → `"enabled": true`

## 6. Go live

1. Switch Dashboard to **Live mode**
2. Replace keys in `config.local.php` with **live** `key_id` and `key_secret`
3. Test a small real payment

## Fallback without Razorpay

If `razorpay.enabled` is `false` or keys are empty, checkout uses the old **“Place order”** flow (order saved as `pending`, no online payment).

## Security

- Never put **Key Secret** in the Vue app or Git
- Only **Key ID** is sent to the browser (required by Razorpay)
- Payment signatures are verified on the server before marking orders paid
