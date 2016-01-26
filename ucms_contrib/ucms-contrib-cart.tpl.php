<div id="ucms-contrib-cart">
  <div id="ucms-cart" class="container-fluid">
    <div class="col-md-12">
      <h2 class="element-invisible"><?php echo t("Your favorites"); ?></h2>
      <?php echo render($actions); ?>
      <?php echo render($display); ?>
    </div>
    <div id="ucms-cart-list">
      <?php echo render($items); ?>
    </div>
  </div>
  <div id="ucms-cart-trash">
    <span class="glyphicon glyphicon-trash"></span>
  </div>
</div>