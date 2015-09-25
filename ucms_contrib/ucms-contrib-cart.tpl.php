<div id="ucms-cart">
  <h2><?php echo t("Your favorites"); ?></h2>
  <?php foreach ($items as $nid => $item): ?>
    <div class="ucms-cart-item" data-nid="<?php echo render($nid); ?>">
      <a class="ucms-cart-remove" href="<?php echo url('admin/cart/' . $nid . '/remove'); ?>">
        <?php echo t("Remove");?>
      </a>
      <?php echo render($item); ?>
    </div>
  <?php endforeach; ?>
  <a id="ucms-cart-trash" href="#"><?php echo t("Trash"); ?></a>
</div>