<div class="container">
  <div id="ucms-contrib-facets">
    <?php echo render($facets); ?>
  </div>
  <div id="ucms-contrib-results">
    <?php echo render($search); ?>
    <?php foreach ($items as $item): ?>
      <div class="ucms-contrib-result">
        <?php echo render($item); ?>
      </div>
    <?php endforeach; ?>
    <?php echo render($pager); ?>
  </div>
  <div id="ucms-contrib-cart">
    <?php echo render($favorites); ?>
  </div>
</div>