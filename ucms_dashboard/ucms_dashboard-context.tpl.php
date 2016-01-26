<div id="contextual-pane">
  <a id="contextual-pane-toggle" href="#">
    <span class="glyphicon glyphicon-chevron-right"></span>
  </a>
  <div class="inner">
    <?php foreach ($items as $item): ?>
      <?php echo render($item); ?>
      <!-- <hr/> -->
    <?php endforeach; ?>
  </div>
</div>