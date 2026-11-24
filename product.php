<?php
require __DIR__.'/includes/layout.php';

$code = param('code');
$detail = null;

// Item code ang panghanap dahil puwedeng magkapareho ang pangalan ng dalawang produkto.
foreach (catalogData() as $product) {
    if ($product['product_code'] === $code) {
        $detail = $product;
        break;
    }
}

if (!$detail) {
    header('HTTP/1.1 404 Not Found');
}
headPage($detail ? $detail['product_name'] : 'Product unavailable');
?>
<a class="back" href="<?php echo url('browse.php', filters()); ?>">← Back to products</a>

<?php if (!$detail): ?>
    <h1>Product unavailable</h1>
    <p>This item is outside the active catalog.</p>
<?php else: ?>
    <?php $image = photo($detail); ?>
    <section class="detail">
        <?php if ($image): ?>
            <img src="<?php echo h($image); ?>" alt="<?php echo h($detail['product_name']); ?>">
        <?php endif; ?>
        <p class="meta"><?php echo h($detail['category_name']); ?></p>
        <h1><?php echo h($detail['product_name']); ?></h1>
        <p class="price">
            <?php echo amount($detail); ?> <small>/ <?php echo h($detail['unit']); ?></small>
        </p>
        <?php if ($detail['quantity'] >= 1): ?>
            <form class="cart-actions" method="post" action="cart.php">
                <input type="hidden" name="token" value="<?php echo cartToken(); ?>">
                <input type="hidden" name="code" value="<?php echo h($code); ?>">
                <label>Quantity <input type="number" name="qty" min="1" max="<?php echo (int)$detail['quantity']; ?>" value="1" required></label>
                <button name="action" value="add">Add to cart</button>
            </form>
        <?php endif; ?>
        <dl>
            <dt>Item code</dt>
            <dd><?php echo h($detail['product_code']); ?></dd>
            <dt>Availability</dt>
            <dd><?php echo $detail['quantity'] > 0 ? 'In stock' : 'Out of stock'; ?></dd>
            <dt>On hand</dt>
            <dd><?php echo (int)$detail['quantity'].' '.h($detail['unit']); ?></dd>
        </dl>
        <?php if ($image): ?>
            <small>Reference photo. Brand, size, and packaging may vary.</small>
        <?php endif; ?>
        <?php if (trim((string)$detail['description']) !== ''): ?>
            <h2>Product information</h2>
            <p><?php echo h($detail['description']); ?></p>
        <?php endif; ?>
    </section>
<?php endif; ?>
<?php endPage(); ?>
