<?php

/**
 * AJAX callback for minidialog upload form.
 */
function ucms_media_image_upload_form_page($mode = 'nojs') {
  $form = drupal_get_form('ucms_media_image_upload_form', $mode);
  if ('ajax' !== $mode) {
    return $form;
  }
  ajax_deliver([
    '#type' => 'ajax',
    '#commands' => [
      minidialog_ajax_command_content($form, ['wide' => 1, 'title' => "Ajouter des images"]),
    ],
  ]);
  // @todo I have no idea why, without this I have 2 distinct
  //   AJAX return JSON, and this cause both Firefox and jQuery
  //   to crash miserably.
  ajax_footer();
  exit;
}

/**
 * Delete entity form submit js callback.
 */
function ucms_media_image_upload_form_submit_js($form, &$form_state) {
  if (!empty($form_state['close_dialog'])) {
    return [
      '#type' => 'ajax',
      '#commands' => [minidialog_ajax_command_close(true)],
    ];
  } else {
    return $form;
  }
}

/**
 * Form callback to upload images.
 */
function ucms_media_image_upload_form($form, &$form_state, $mode = 'nojs') {
  $form['#id'] = 'alouette-image-upload';

  $form['actions'] = array(
    '#type' => 'actions',
    '#weight' => 10000,
    'submit' => array(
      '#type' => 'submit',
      '#value' => "Enregistrer",
    ),
  );

  // Upload form (step 1).
  if (empty($form_state['storage']['files'])) {

    $form['files'] = array(
      '#type' => 'file_chunked',
      '#title' => "Sélection des images",
      '#multiple' => true,
      '#upload_location' => 'public://images', // @todo
      '#upload_validators' => array(
        'file_validate_extensions' => array('jpeg jpg png gif'),
      ),
    );

    /*
    $form['complete_info'] = array(
      '#type' => 'checkbox',
      '#title' => "Compléter les informations lors d'une seconde étape.",
      '#default_value' => false,
    );
     */
  }
  // Additional information form (step 2).
  else {
    // @todo image and node fields

    $form['actions']['submit']['#submit'] = array(
      'ucms_media_image_upload_form_finish',
    );

    $form['actions']['back'] = array(
      '#type' => 'submit',
      '#value' => "Retour",
      '#submit' => array('ucms_media_image_upload_form_cancel'),
      '#limit_validation_errors' => array(),
    );
  }

  if ('ajax' === $mode) { // @see ucms_media_image_upload_form_page()
    $form['actions']['submit']['#ajax'] = [
      'callback' => 'ucms_media_image_upload_form_submit_js',
      'wrapper'  => 'alouette-image-upload',
    ];
    if (isset($form['actions']['back'])) {
      $form['actions']['back']['#ajax'] = [
        'callback' => 'ucms_media_image_upload_form_submit_js',
        'wrapper'  => 'alouette-image-upload',
      ];
    }
  }

  return $form;
}

/**
 * Submit callback for the image upload form.
 */
function ucms_media_image_upload_form_submit($form, &$form_state) {
  $files = $form_state['values']['files'];
  unset($files['fid']);
  unset($files['upload']);

  /** @var \Drupal\Core\Entity\EntityManager $entityManager */
  $entityManager = \Drupal::service('entity.manager');
  $nodeStorage = $entityManager->getStorage('node');

  if (!empty($form_state['values']['complete_info'])) {
    $form_state['storage']['input'] = $form_state['input'];
    $form_state['storage']['files'] = $files;
    $form_state['rebuild'] = TRUE;
  } else {
    foreach ($files as $file) {
      $file->status = FILE_STATUS_PERMANENT;
      file_save($file);

      $node = $nodeStorage->create();
      $node->uid = $GLOBALS['user']->uid;
      $node->type = 'image';
      $node->status = 1;
      $node->title = $file->filename;
      $node->image = [LANGUAGE_NONE => [['fid' => $file->fid]]];
      $node->is_global = 0;
      $nodeStorage->save($node);
    }

    drupal_set_message(format_plural(
      count($files),
      "You uploaded 1 new image.",
      "You uploaded @count new images."
    ));

    $form_state['close_dialog'] = true; // @see ucms_media_image_upload_form_submit_js()
    $form_state['redirect'] = 'admin/dashboard/media';
  }
}

/**
 * Cancelation callback for the image upload form (step 2).
 */
function ucms_media_image_upload_form_cancel($form, &$form_state) {
  $form_state['input'] = $form_state['storage']['input'];
  unset($form_state['storage']['input']);
  unset($form_state['storage']['files']);
  $form_state['rebuild'] = TRUE;
}

/**
 * Finish callback for the image upload form (step 2).
 */
function ucms_media_image_upload_form_finish($form, &$form_state) {
  $form_state['redirect'] = 'admin/dashboard/media';
  $form_state['close_dialog'] = true; // @see ucms_media_image_upload_form_submit_js()
}
