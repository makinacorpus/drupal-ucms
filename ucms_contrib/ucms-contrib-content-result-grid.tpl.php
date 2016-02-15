<?php foreach ($nodes as $nid => $node): ?>
  <div class="ucms-contrib-result" data-nid="<?php echo $nid; ?>">
    <?php echo render($view); ?>
    <?php if (isset($actions)): ?>
      <?php echo render($actions); ?>
    <?php endif; ?>
  </div>
<?php endforeach; ?>
