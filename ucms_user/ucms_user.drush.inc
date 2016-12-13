<?php
/**
 * @file
 * Drush integration.
 */

/**
 * Implements hook_drush_command().
 */
function ucms_user_drush_command() {
  return [
    'ucms-user-login-link' => [
      'description' => "Generate a one time login URL for user",
      'arguments'   => [
        'user'      => "User name, mail or identifier",
      ],
      'aliases'     => ['us-uli'],
    ],
  ];
}

/**
 * List all indices.
 */
function drush_ucms_user_login_link() {

  $args = drush_get_arguments();
  if (count($args) < 2) {
    drush_die("You must provide at least one argument, the user name, mail or identifier");
  }

  $user = $args[1];

  /** @var \Drupal\Core\Session\AccountInterface $account */
  $account = user_load($user);
  if (!$account) {
    $account = user_load_by_mail($user);
    if (!$account) {
      $account = user_load_by_name($user);
      if (!$account) {
        drush_die(sprintf("Unknown user %s", $user));
      }
    }
  }

  /** @var \MakinaCorpus\Ucms\User\TokenManager $tokenManager */
  $tokenManager = \Drupal::getContainer()->get('ucms_user.token_manager');

  $token = $tokenManager->createToken($account);
  $url = url('set-password/' . $token->token, ['absolute' => true]);

  drush_print(sprintf("One time login link for %s: %s", $account->getDisplayName(), $url));
}
