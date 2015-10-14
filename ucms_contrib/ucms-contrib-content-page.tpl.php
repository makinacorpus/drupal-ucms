<div class="container">
  <div id="ucms-contrib-facets">
    <?php echo render($display); ?>
    <?php echo render($facets); ?>
  </div>
  <div id="ucms-contrib-results">
    <?php echo render($search); ?>
    <?php echo render($nodes); ?>
    <?php echo render($pager); ?>
  </div>
  <?php echo render($favorites); ?>
</div>
