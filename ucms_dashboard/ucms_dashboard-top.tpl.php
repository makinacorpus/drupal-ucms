<nav id="toolbar" class="navbar navbar-inverse">
  <div class="container-fluid">
    <div class="navbar-header">
      <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#nav-collapse" aria-expanded="false">
        <span class="sr-only"><?php echo t("Toggle navigation"); ?></span>
      </button>
      <a class="navbar-brand" href="<?php echo url('admin'); ?>">
        <span aria-hidden="true" class="glyphicon glyphicon-home"></span>
        <span class="sr-only"><?php echo t("Administration"); ?></span>
      </a>
    </div>

    <div class="collapse navbar-collapse" id="nav-collapse">
      <ul class="nav navbar-nav navbar-right">
        <li>
          <a href="<?php echo url('admin/dashboard/content'); ?>">
            <span aria-hidden="true" class="glyphicon glyphicon-file"></span>
            <?php echo t("Content"); ?>
          </a>
        </li>
        <li>
          <a href="<?php echo url('admin/dashboard/media'); ?>">
            <span aria-hidden="true" class="glyphicon glyphicon-picture"></span>
            <?php echo t("Media"); ?>
          </a>
        </li>
        <li>
          <a href="<?php echo url('admin/dashboard/label'); ?>">
            <span aria-hidden="true" class="glyphicon glyphicon-tags"></span>
            <?php echo t("Labels"); ?>
          </a>
        </li>
        <li>
          <a href="<?php echo url('admin/dashboard/site'); ?>">
            <span aria-hidden="true" class="glyphicon glyphicon-cloud"></span>
            <?php echo t("Sites"); ?>
          </a>
        </li>
        <li>
          <a href="<?php echo url('admin/dashboard/user'); ?>">
            <span aria-hidden="true" class="glyphicon glyphicon-user"></span>
            <?php echo t("Users"); ?>
          </a>
        </li>
      </ul>
    </div>
  </div>
</nav>