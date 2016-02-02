<div class="container">
  <div id="ucms-contrib-facets">
    <?php echo render($displayLinks); ?>
    <?php foreach ($filters as $filter): ?>
      <?php echo render($filter); ?>
    <?php endforeach; ?>
  </div>
  <div id="ucms-contrib-results">
    <?php echo render($search); ?>
    <?php echo render($sort_field); ?>
    <?php echo render($sort_order); ?>
    <?php echo render($displayView); ?>
    <?php echo render($pager); ?>
  </div>
</div>