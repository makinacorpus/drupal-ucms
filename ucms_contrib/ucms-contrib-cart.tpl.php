<div id="ucms-cart">
  <h2><?php echo t("Your favorites"); ?></h2>
  <?php foreach ($items as $nid => $item): ?>
    <div class="ucms-cart-item" data-nid="<?php echo render($nid); ?>">
      <?php echo render($item); ?>
    </div>
  <?php endforeach; ?>
  <a id="ucms-cart-trash" href="#"><?php echo t("Trash"); ?></a>
</div>