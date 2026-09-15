<?php

declare(strict_types=1);

require dirname(__DIR__, 2).'/shared/request.php';
require dirname(__DIR__, 2).'/shared/store.php';
require dirname(__DIR__, 2).'/shared/webhook_caller.php';
require dirname(__DIR__, 1).'/shared/config.php';
require dirname(__DIR__, 1).'/shared/line_items.php';

$reference = fake_api_read_query('ref');
$session = fake_api_store_read(GLOBEX_PROVIDER)[$reference] ?? null;

if (null === $session) {
    http_response_code(404);
    echo 'Unknown checkout session.';
    exit;
}

$successUrl = is_string($session['success_url'] ?? null) ? $session['success_url'] : '';
$cancelUrl = is_string($session['cancel_url'] ?? null) ? $session['cancel_url'] : '';

if ('POST' === ($_SERVER['REQUEST_METHOD'] ?? 'GET')) {
    try {
        fake_api_call_webhook('PAYMENT_WEBHOOK_SECRET', 'X-Payment-Signature', 'payment-authorized', ['paymentReference' => $reference]);
    } catch (RuntimeException $e) {
        http_response_code(502);
        echo htmlspecialchars($e->getMessage());
        exit;
    }

    fake_api_store_transition_status(GLOBEX_PROVIDER, $reference, 'authorized');

    header('Location: '.$successUrl);
    exit;
}

/** @var list<array{price_data: array{currency: string, unit_amount: int, product_data: array{name: string}}, quantity: int}> $lineItems */
$lineItems = is_array($session['line_items'] ?? null) ? $session['line_items'] : [];
$amountInCents = fake_globex_line_items_amount_in_cents($lineItems);
$currency = strtoupper(fake_globex_line_items_currencies($lineItems)[0] ?? 'eur');
$total = number_format($amountInCents / 100, 2).' '.$currency;

header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Globex Corporation — Secure Payment</title>
    <link rel="stylesheet" href="/checkout.css">
</head>
<body>

<div class="checkout">
    <div class="checkout__banner">
        Sandbox Environment
    </div>

    <div class="checkout__header">
        <h1>Globex Corporation</h1>
        <div class="checkout__amount"><?php echo htmlspecialchars($total); ?></div>
    </div>

    <div class="checkout__body">
        <div class="checkout__lines">
            <?php foreach ($lineItems as $lineItem) {
                $unitAmount = $lineItem['price_data']['unit_amount'] ?? 0;
                $quantity = $lineItem['quantity'] ?? 1;
                $label = $lineItem['price_data']['product_data']['name'] ?? '';
                $lineTotal = number_format($unitAmount * $quantity / 100, 2).' '.$currency;
                ?>
                <div class="checkout__line">
                    <span class="checkout__line-label"><?php echo htmlspecialchars($label); ?> <span class="checkout__line-qty">×<?php echo $quantity; ?></span></span>
                    <span class="checkout__line-amount"><?php echo htmlspecialchars($lineTotal); ?></span>
                </div>
            <?php } ?>
        </div>

        <div class="checkout__row">
            <span class="checkout__label">Payment Reference</span>
            <span class="checkout__value"><?php echo htmlspecialchars($reference); ?></span>
        </div>

        <form method="post">
            <button type="submit" class="checkout__button checkout__button--pay">Pay</button>
        </form>
        <a href="<?php echo htmlspecialchars($cancelUrl); ?>" class="checkout__button checkout__button--cancel">Cancel</a>

        <div class="checkout__footer">
            <strong>Disclaimer:</strong> This is a simulated environment. No real funds are processed.<br>
        </div>
    </div>
</div>

</body>
</html>
