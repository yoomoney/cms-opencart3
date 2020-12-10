<?php

echo $header;
echo $column_left;
echo $column_right;

?>
<div class="container">
    <?php echo $content_top; ?>

    <p><a href="<?php echo $orderLink; ?>">Заказ №<?php echo $orderId; ?></a> был создан</p>

    <?php echo $content_bottom; ?>
</div>
<?php echo $footer; ?>
