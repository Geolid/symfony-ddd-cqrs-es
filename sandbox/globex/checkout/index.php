<?php

declare(strict_types=1);

require dirname(__DIR__, 2).'/shared/store.php';
require dirname(__DIR__, 2).'/shared/webhook_caller.php';

if ('POST' === ($_SERVER['REQUEST_METHOD'] ?? 'GET')) {
    $reference = filter_var($_POST['ref'] ?? '', \FILTER_UNSAFE_RAW) ?: '';
    $returnUrl = filter_var($_POST['returnUrl'] ?? '', \FILTER_UNSAFE_RAW) ?: '';

    try {
        fake_api_call_webhook('PAYMENT_WEBHOOK_SECRET', 'X-Payment-Signature', 'payment-authorized', ['paymentReference' => $reference]);
    } catch (RuntimeException $e) {
        http_response_code(502);
        echo htmlspecialchars($e->getMessage());
        exit;
    }

    fake_api_store_transition_status('globex-charges', $reference, 'authorized');

    header('Location: '.$returnUrl);
    exit;
}

$reference = filter_var($_GET['ref'] ?? '', \FILTER_UNSAFE_RAW) ?: '';
$total = number_format((filter_var($_GET['total'] ?? 0, \FILTER_VALIDATE_INT) ?: 0) / 100, 2).' €';
$returnUrl = filter_var($_GET['returnUrl'] ?? '', \FILTER_UNSAFE_RAW) ?: '';

header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Globex Corporation — Secure Payment</title>
    <style>
        :root {
            --globex-blue: #635bff;
            --globex-blue-hover: #0a2540;
            --page-bg: #f6f9fc;
            --card-bg: #ffffff;
            --text-dark: #30313d;
            --text-light: #697386;
            --border: #e6ebf1;
        }

        body {
            background-color: var(--page-bg);
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            color: var(--text-dark);
            margin: 0;
            padding: 2rem 1rem;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            -webkit-font-smoothing: antialiased;
        }

        .checkout {
            background: var(--card-bg);
            width: 100%;
            max-width: 420px;
            border-radius: 8px;
            box-shadow: 0 15px 35px rgba(50, 50, 93, .1), 0 5px 15px rgba(0, 0, 0, .07);
            overflow: hidden;
        }

        .checkout__banner {
            background-color: #fef3c7;
            color: #b45309;
            text-align: center;
            font-weight: 600;
            font-size: 0.75rem;
            padding: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .checkout__header {
            padding: 2rem 2rem 1rem;
            text-align: center;
        }

        .checkout__header h1 {
            margin: 0;
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--text-dark);
        }

        .checkout__amount {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--globex-blue);
            margin: 1rem 0;
        }

        .checkout__body {
            padding: 0 2rem 2rem;
        }

        .checkout__row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid var(--border);
            font-size: 0.9rem;
        }

        .checkout__row:last-of-type {
            border-bottom: none;
            margin-bottom: 1.5rem;
        }

        .checkout__label {
            color: var(--text-light);
        }

        .checkout__value {
            font-weight: 600;
            font-family: ui-monospace, monospace;
        }

        .checkout__button {
            display: block;
            text-align: center;
            width: 100%;
            border-radius: 4px;
            padding: 1rem;
            font-size: 1rem;
            font-weight: 600;
        }

        .checkout__button--pay {
            background-color: var(--globex-blue);
            color: white;
            border: none;
            cursor: pointer;
            transition: all 0.15s ease;
            box-shadow: 0 4px 6px rgba(50, 50, 93, .11), 0 1px 3px rgba(0, 0, 0, .08);
        }

        .checkout__button--pay:hover {
            background-color: var(--globex-blue-hover);
            transform: translateY(-1px);
            box-shadow: 0 7px 14px rgba(50, 50, 93, .1), 0 3px 6px rgba(0, 0, 0, .08);
        }

        .checkout__button--cancel {
            margin-top: 0.75rem;
            color: #df1b41;
            text-decoration: none;
            font-size: 0.9rem;
        }

        .checkout__button--cancel:hover {
            text-decoration: underline;
        }

        .checkout__footer {
            margin-top: 1.5rem;
            text-align: center;
            font-size: 0.75rem;
            color: var(--text-light);
            line-height: 1.5;
            padding-top: 1rem;
            border-top: 1px dashed var(--border);
        }
    </style>
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
        <div class="checkout__row">
            <span class="checkout__label">Payment Reference</span>
            <span class="checkout__value"><?php echo htmlspecialchars($reference); ?></span>
        </div>

        <form method="post">
            <input type="hidden" name="ref" value="<?php echo htmlspecialchars($reference); ?>">
            <input type="hidden" name="returnUrl" value="<?php echo htmlspecialchars($returnUrl); ?>">
            <button type="submit" class="checkout__button checkout__button--pay">Authorize & Pay</button>
        </form>
        <a href="<?php echo htmlspecialchars($returnUrl); ?>" class="checkout__button checkout__button--cancel">Cancel</a>

        <div class="checkout__footer">
            <strong>Disclaimer:</strong> This is a simulated environment. No real funds are processed.<br>
        </div>
    </div>
</div>

</body>
</html>
