<?php

use Drupal\user\Entity\User;

/**
 * @file
 * Enables modules and site configuration for a standard site installation.
 */

/**
 * Implements hook_install_tasks_alter().
 */
function sous_install_tasks_alter(array &$tasks, array $install_state) {
  $tasks['install_finished']['function'] = 'sous_after_install_finished';
}

/**
 * Sous after install finished.
 *
 * Disable User 1 and generate User 2.
 *
 * @param array $install_state
 *   The current install state.
 *
 */
function sous_after_install_finished(array &$install_state) {

  // Assign user 1 the "superuser" role.
  $user = User::load(1);
  $user->roles[] = 'superuser';
  $user->block();
  $user->save();

  // see: https://stackoverflow.com/questions/6101956/generating-a-random-password-in-php
  $secure_pass = bin2hex(openssl_random_pseudo_bytes(8));

  // Create user 2 and assign "superuser" role.
  $user = User::create();
  //Mandatory settings
  $user->setPassword($secure_pass);
  $user->enforceIsNew();
  $user->setEmail("superuser@example.com");
  $user->setUsername('superuser');
  $user->addRole('superuser');
  $user->activate();
  $user->save();

  \Drupal::messenger()->addStatus(t('The user accout "superuser" was created. Use ') . $secure_pass . t(' to log in.'));
}
