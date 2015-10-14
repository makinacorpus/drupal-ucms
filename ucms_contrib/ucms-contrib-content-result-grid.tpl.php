<?php foreach ($nodes as $nid => $node): ?>
  <div class="ucms-contrib-result" data-nid="<?php echo $nid; ?>">
    <?php
      $view = node_view($node, $view_mode);
      echo render($view);
    ?>
  </div>
<?php endforeach; ?>