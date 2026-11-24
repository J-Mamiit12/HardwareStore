<?php
require __DIR__.'/includes/layout.php';
headPage('Cashier order lookup');
?>
<h1>Cashier order lookup</h1>
<form class="detail search" method="get" action="cart.php">
    <label for="order-reference">Order reference on the pre-receipt</label>
    <input type="hidden" name="lookup" value="1">
    <div>
        <input id="order-reference" name="order" type="text" required
               autocomplete="off" spellcheck="false" placeholder="Enter the printed reference">
        <button>Find order</button>
    </div>
</form>
<?php endPage(); ?>
