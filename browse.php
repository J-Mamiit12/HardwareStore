<?php
require __DIR__.'/includes/layout.php';

$state = filters();
$matches = array();
$departments = array();

foreach (catalogData() as $product) {
    // Kunin muna ang lahat ng department para kumpleto ang dropdown kahit may active filter.
    $departments[$product['category_name']] = true;
    if ($state['category'] !== '' && $product['category_name'] !== $state['category']) {
        continue;
    }
    if ($state['stock'] === '1' && $product['quantity'] <= 0) {
        continue;
    }
    if ($state['q'] !== '') {
        $searchText = $product['product_name'].' '.$product['product_code'].' '.
            $product['category_name'].' '.$product['description'];
        if (stripos($searchText, $state['q']) === false) {
            continue;
        }
    }
    $matches[] = $product;
}
ksort($departments);

// Nakaayos na by name ang catalog. Mag-sort lang ulit kapag presyo ang pinili.
if (in_array($state['sort'], array('low', 'high'), true)) {
    $direction = $state['sort'] === 'low' ? 1 : -1;
    usort($matches, function ($a, $b) use ($direction) {
        $left = (float)$a['selling_price'];
        $right = (float)$b['selling_price'];
        if ($left == $right) {
            return strcmp($a['product_name'], $b['product_name']);
        }
        return ($left < $right ? -1 : 1) * $direction;
    });
}

headPage('Browse products');
?>
<div class="catalog-heading">
    <a class="back" href="index.php">← Departments</a>
    <h1><?php echo h($state['category'] !== '' ? $state['category'] : 'All products'); ?></h1>
</div>

<form class="filters" action="browse.php">
    <label>
        Search
        <input name="q" type="search" value="<?php echo h($state['q']); ?>" placeholder="Name or item code">
    </label>
    <label>
        Department
        <select name="category">
            <option value="">All departments</option>
            <?php foreach (array_keys($departments) as $name): ?>
                <option <?php if ($name === $state['category']) echo 'selected'; ?>><?php echo h($name); ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <label>
        Sort
        <select name="sort">
            <option value="">Name A–Z</option>
            <option value="low" <?php if ($state['sort'] === 'low') echo 'selected'; ?>>Lowest price</option>
            <option value="high" <?php if ($state['sort'] === 'high') echo 'selected'; ?>>Highest price</option>
        </select>
    </label>
    <label class="check">
        <input type="checkbox" name="stock" value="1" <?php if ($state['stock'] === '1') echo 'checked'; ?>>
        In stock only
    </label>
    <noscript><button>Apply</button></noscript>
    <a href="browse.php">Reset</a>
</form>

<p class="count catalog-count" role="status"><?php echo count($matches).' '.(count($matches) === 1 ? 'product' : 'products'); ?></p>
<div id="catalog-results">
<?php if (!$matches): ?>
    <p>No products match these filters. Try another keyword or reset the filters.</p>
<?php endif; ?>
<section class="grid">
    <?php foreach ($matches as $product) {
        tile($product, $state);
    } ?>
</section>
</div>
<?php endPage(); ?>
