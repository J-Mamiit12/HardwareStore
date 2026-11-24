<?php
require __DIR__.'/includes/layout.php';
startCart();

function cartRedirect($message, $reference = '') {
    $_SESSION['cart_notice'] = $message;
    header('Location: cart.php'.($reference ? '?order='.$reference : ''), true, 303);
    exit;
}

$catalog = array();
foreach (catalogData() as $item) $catalog[$item['product_code']] = $item;
$cart =& $_SESSION['cart'];
$lines = array();
$total = 0;
$canOrder = (bool)$cart;
foreach ($cart as $code => $qty) {
    $item = isset($catalog[$code]) ? $catalog[$code] : null;
    if (!$item || $qty > $item['quantity']) $canOrder = false;
    $cents = $item ? (int)round($item['selling_price'] * 100) : 0;
    $lines[] = array('code' => $code, 'name' => $item ? $item['product_name'] : $code,
        'unit' => $item ? $item['unit'] : '', 'qty' => $qty, 'price_cents' => $cents);
    $total += $cents * $qty;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['token']) || $_POST['token'] !== cartToken()) {
        cartRedirect('Your cart has changed. Please review it again.');
    }
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    $code = isset($_POST['code']) && is_string($_POST['code']) ? $_POST['code'] : '';
    if ($action === 'order') {
        if (!$canOrder) cartRedirect('Review the quantities before ordering.');
        // Ihambing sa huling total na nakita ng shopper bago i-save ang order.
        $snapshot = json_encode($lines);
        if (!isset($_POST['review']) || $_POST['review'] !== hash('sha256', $snapshot)) {
            cartRedirect('Product details have changed. Please review the updated total.');
        }
        $reference = strtoupper(substr(hash('sha256', 'order'.cartToken()), 0, 20));
        $db = catalogConnection();
        $stmt = $db ? $db->prepare('INSERT INTO kiosk_orders (reference,total,items) VALUES (?,?,?) ON DUPLICATE KEY UPDATE reference=reference') : false;
        $amount = number_format($total / 100, 2, '.', '');
        if (!$stmt) cartRedirect('Could not save the order. Your cart is ready to retry.');
        $stmt->bind_param('sss', $reference, $amount, $snapshot);
        if (!$stmt->execute()) cartRedirect('Could not save the order. Your cart is ready to retry.');
        $cart = array();
        $_SESSION['cart_version'] = uniqid('', true);
        $_SESSION['print_order'] = $reference;
        cartRedirect('Order saved.', $reference);
    }
    if ($action === 'remove') {
        unset($cart[$code]);
    } elseif ($action === 'add' || $action === 'update') {
        $qty = isset($_POST['qty']) ? filter_var($_POST['qty'], FILTER_VALIDATE_INT) : false;
        if (!$qty || $qty < 1 || !isset($catalog[$code])) cartRedirect('Choose an available product and a valid quantity.');
        if ($action === 'add' && isset($cart[$code])) $qty += $cart[$code];
        if ($qty > $catalog[$code]['quantity']) cartRedirect('The requested quantity exceeds available stock.');
        $cart[$code] = $qty;
    } else {
        cartRedirect('Please select a cart action.');
    }
    $_SESSION['cart_version'] = uniqid('', true);
    cartRedirect($action === 'add' ? 'Added to your cart.' : 'Cart updated.');
}

$notice = isset($_SESSION['cart_notice']) ? $_SESSION['cart_notice'] : '';
unset($_SESSION['cart_notice']);
$lookup = param('lookup') === '1';
$reference = strtoupper(preg_replace('/[\s-]+/', '', param('order')));
$receipt = false;
if (preg_match('/^[A-F0-9]{20}$/D', $reference)) {
    $db = catalogConnection();
    $stmt = $db ? $db->prepare('SELECT k.items,k.total,k.created_at,s.sale_id FROM kiosk_orders k LEFT JOIN sales s ON s.receipt_no=k.reference COLLATE utf8mb4_unicode_ci WHERE k.reference=?') : false;
    if (!$stmt) {
        header('HTTP/1.1 503 Service Unavailable');
        headPage('Order lookup unavailable');
        echo '<h1>Order lookup unavailable</h1><p>Please try again shortly.</p><a href="cashier.php">Return to lookup</a>';
        endPage();
        exit;
    } else {
        $stmt->bind_param('s', $reference);
        $stmt->execute();
        $stmt->bind_result($savedItems, $savedTotal, $createdAt, $saleId);
        if ($stmt->fetch()) {
            $receipt = true;
            $lines = json_decode($savedItems, true);
            $total = round($savedTotal * 100);
        }
    }
}
if (!$receipt && ($lookup || $reference !== '')) {
    header('HTTP/1.1 404 Not Found');
    headPage('Order not found');
    echo '<h1>Order not found</h1><p>Check the reference on the printed slip.</p><a href="cashier.php">Look up another order</a>';
    endPage();
    exit;
}
$autoPrint = !$lookup && $receipt && isset($_SESSION['print_order']) && $_SESSION['print_order'] === $reference;
if ($autoPrint) unset($_SESSION['print_order']);
headPage($receipt ? 'Pre-receipt' : 'Your cart');
?>
<?php if ($lookup): ?>
    <a class="back" href="cashier.php">Look up another order</a>
<?php else: ?>
    <a class="back" href="browse.php">Continue shopping</a>
<?php endif; ?>
<h1><?php echo $receipt ? 'Hardware Store · Pre-receipt' : 'Your cart'; ?></h1>
<?php if ($notice): ?><p role="status"><?php echo h($notice); ?></p><?php endif; ?>
<?php if ($receipt): ?>
    <p><?php if ($saleId === null): ?><strong>UNPAID</strong> · Present this slip to the cashier for payment.<?php else: ?><strong>Processed at cashier</strong><?php endif; ?></p>
    <p>Order reference <strong><?php echo h($reference); ?></strong><br><?php echo h($createdAt); ?></p>
    <button type="button" id="print-receipt" <?php if ($autoPrint) echo 'data-autoprint'; ?>>Print pre-receipt</button>
<?php endif; ?>
<?php if (!$lines): ?><p>Your cart is empty.</p><?php else: ?>
<section class="detail">
    <?php foreach ($lines as $line): ?>
        <article class="cart-line">
            <div><strong><?php echo h($line['name']); ?></strong>
                <?php if ($receipt): ?><small><?php echo h($line['code']); ?></small><?php endif; ?>
                <p><?php echo $line['qty'].' × ₱'.number_format($line['price_cents']/100, 2).' / '.h($line['unit']); ?></p>
            </div>
            <strong>₱<?php echo number_format($line['price_cents']*$line['qty']/100, 2); ?></strong>
            <?php if (!$receipt): ?>
                <form class="cart-actions" method="post" action="cart.php">
                    <input type="hidden" name="token" value="<?php echo cartToken(); ?>">
                    <input type="hidden" name="code" value="<?php echo h($line['code']); ?>">
                    <label>Quantity <input type="number" name="qty" min="1" value="<?php echo $line['qty']; ?>" required></label>
                    <button name="action" value="update">Update</button>
                    <button name="action" value="remove" formnovalidate>Remove</button>
                </form>
            <?php endif; ?>
        </article>
    <?php endforeach; ?>
    <p class="price">Total ₱<?php echo number_format($total/100, 2); ?></p>
    <?php if (!$receipt): ?>
        <form method="post" action="cart.php">
            <input type="hidden" name="token" value="<?php echo cartToken(); ?>">
            <input type="hidden" name="review" value="<?php echo hash('sha256', json_encode($lines)); ?>">
            <?php if (!$canOrder): ?><p>One or more items are unavailable in this quantity. Update or remove them to continue.</p><?php endif; ?>
            <button name="action" value="order" <?php if (!$canOrder) echo 'disabled'; ?>>Get pre-receipt</button>
        </form>
    <?php endif; ?>
</section>
<?php endif; ?>
<?php endPage(); ?>
