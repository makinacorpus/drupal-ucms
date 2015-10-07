<div id="ucms-contrib-cart">
  <a id="ucms-cart-toggle" href="#"><?php echo t("Close pane"); ?></a>

  <div id="ucms-cart">
    <h2><?php echo t("Your favorites"); ?></h2>

    <div id="ucms-cart-list">
      <?php foreach ($items as $nid => $item): ?>
        <div class="ucms-cart-item" data-nid="<?php echo render($nid); ?>">
          <?php echo render($item); ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
  <div id="ucms-cart-trash">
    <span><?php echo t('Trash'); ?></span>
  </div>
</div>
