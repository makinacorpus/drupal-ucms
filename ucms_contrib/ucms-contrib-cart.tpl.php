<div id="ucms-contrib-cart">
  <a id="ucms-cart-toggle" href="#">
    <span class="glyphicon glyphicon-chevron-right"></span>
  </a>

  <div id="ucms-cart" class="container-fluid">
    <div class="row">
      <div class="col-md-12">
        <h2 class="element-invisible"><?php echo t("Your favorites"); ?></h2>
        <?php echo render($actions); ?>
      </div>
      <div id="ucms-cart-list">
        <?php foreach ($items as $nid => $item): ?>
          <div class="ucms-cart-item col-md-6" data-nid="<?php echo render($nid); ?>">
            <?php echo render($item); ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <div id="ucms-cart-trash">
    <span class="glyphicon glyphicon-trash"></span>
  </div>
</div>
