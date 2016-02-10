<div id="contextual-pane">
  <div id="contextual-pane-toggle">
    <?php foreach ($tabs as $key => $tab): ?>
      <a href="#tab-<?php echo $key; ?>">
        <span class="glyphicon glyphicon-<?php echo $tab['icon']; ?>"></span>
        <span class="sr-only"><?php echo $tab['label']; ?></span>
      </a>
    <?php endforeach; ?>
  </div>
  <div class="inner">
    <div class="actions">
      <?php echo render($actions); ?>
    </div>
    <div class="tabs">
      <?php foreach ($tabs as $key => $tab): ?>
        <div id="tab-<?php echo $key; ?>">
          <?php foreach ($items[$key] as $item): ?>
            <?php echo render($item); ?>
          <?php endforeach; ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
