<?php
require __DIR__.'/includes/layout.php';

$items = catalogData();
$departments = array();
$featured = array();

// Sa isang ikot ng catalog, bilangin ang items per department at kunin ang featured products.
foreach ($items as $product) {
    $name = $product['category_name'];
    if (!isset($departments[$name])) {
        $departments[$name] = 0;
    }
    $departments[$name]++;
    if ($product['is_featured']) {
        $featured[] = $product;
    }
}
ksort($departments);

headPage('Browse departments');
?>
<section class="welcome">
    <div class="welcome-copy">
        <h1>Find your<br><em>hardware.</em></h1>
        <form action="browse.php" class="search">
            <label for="q">Search the store catalog</label>
            <div>
                <input id="q" name="q" type="search" placeholder="Name, keyword, or item code">
                <button>Search</button>
            </div>
        </form>
    </div>
    <?php $slides = array_values(array_filter($featured, 'photo')); ?>
    <?php if ($slides): ?>
    <?php foreach ($slides as $position => $slide): ?>
    <a class="spotlight" <?php if ($position > 0) echo 'hidden'; ?> href="<?php echo url('product.php', array('code' => $slide['product_code'])); ?>">
        <img src="<?php echo h(photo($slide)); ?>" width="180" height="180" alt="">
        <span><small>Featured product</small><strong><?php echo h($slide['product_name']); ?></strong></span>
        <b><?php echo amount($slide); ?></b>
    </a>
    <?php endforeach; ?>
    <?php else: ?>
    <div class="workbench" aria-hidden="true">
        <div class="tape">
            <i class="blade"></i>
            <i class="case"><i class="dial"></i></i>
        </div>
    </div>
    <?php endif; ?>
</section>

<div class="sectionhead">
    <h2>Shop by department</h2>
    <a href="browse.php">Browse all <?php echo count($items); ?> products</a>
</div>
<nav class="departments" aria-label="Departments">
    <?php foreach ($departments as $name => $count): ?>
        <a href="<?php echo url('browse.php', array('category' => $name)); ?>">
            <i class="department-icon"><?php echo departmentIcon($name); ?></i>
            <strong><?php echo h($name); ?></strong>
            <span><?php echo $count.' '.($count === 1 ? 'product' : 'products'); ?></span>
        </a>
    <?php endforeach; ?>
</nav>

<?php if ($items): ?>
    <h2>Featured products</h2>
    <section class="grid featured-products">
        <?php foreach ($items as $product) {
            tile($product, array());
        } ?>
    </section>
<?php endif; ?>
<?php endPage(); ?>
