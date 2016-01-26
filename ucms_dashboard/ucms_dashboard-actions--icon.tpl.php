<div class="btn-group" role="group" aria-label="actions">
  <?php if (!empty($primary)): ?>
    <?php foreach ($primary as $link): ?>
      <?php $link['options']['attributes']['class'] .= ' btn btn-default'; ?>
      <a href="<?php echo url($link['href'], $link['options']); ?>"<?php echo drupal_attributes($link['options']['attributes']); ?>>
        <?php if ($link['icon']): ?>
          <span class="glyphicon glyphicon-<?php echo $link['icon']; ?>" aria-hidden="true"></span>
        <?php endif; ?>
        <span class="sr-only"><?php echo check_plain($link['title']); ?></span>
      </a>
    <?php endforeach; ?>
  <?php endif; ?>
  <?php if (!empty($secondary)): ?>
    <div class="btn-group" role="group">
      <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
        <span class="sr-only"><?php echo t("More actions"); ?></span>
        <span class="caret"></span>
      </button>
      <ul class="dropdown-menu">
        <?php foreach ($secondary as $link): ?>
          <li>
            <a href="<?php echo url($link['href'], $link['options']); ?>"<?php echo drupal_attributes($link['options']['attributes']); ?>>
              <?php if ($link['icon']): ?>
                <span class="glyphicon glyphicon-<?php echo $link['icon']; ?>" aria-hidden="true"></span>
              <?php endif; ?>
              <?php echo check_plain($link['title']); ?>
            </a>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>
</div>