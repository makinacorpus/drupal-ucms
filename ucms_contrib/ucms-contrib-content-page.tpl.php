<div class="container">
  <div id="ucms-contrib-facets">
    <?php echo render($facets); ?>
  </div>
  <div id="ucms-contrib-results">
    <?php echo render($search); ?>
    <?php foreach ($items as $nid => $item): ?>
      <div class="ucms-contrib-result" data-nid="<?php echo $nid; ?>">
        <?php echo render($item); ?>
      </div>
    <?php endforeach; ?>
    <?php echo render($pager); ?>
  </div>
  <?php echo render($favorites); ?>
</div>
