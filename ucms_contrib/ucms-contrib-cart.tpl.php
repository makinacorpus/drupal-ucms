<div id="ucms-cart">
  <h2><?php echo t("Your favorites"); ?></h2>
  <?php echo render($actions); ?>
  <div>
    <?php foreach ($items as $nid => $item): ?>
      <div class="ucms-cart-item" data-nid="<?php echo render($nid); ?>">
        <?php echo render($item); ?>
      </div>
    <?php endforeach; ?>
  </div>
  <div class="bottom">
    <a id="ucms-cart-toggle" href="#"><?php echo t("Close pane"); ?></a>
    <a id="ucms-cart-trash" href="#"><?php echo t("Trash"); ?></a>
  </div>
</div>