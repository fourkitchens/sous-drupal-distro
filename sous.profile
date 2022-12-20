<?php

use Drupal\user\Entity\User;

/**
 * @file
 * Enables modules and site configuration for a standard site installation.
 */

/**
 * Implements hook_install_tasks().
 */
function sous_install_tasks(array $install_state) {
  $tasks = [
    'install_optional_paragraph_modules' => [
      'display_name' => t('Select optional paragraph modules'),
      'type' => 'form',
      'function' => '\Drupal\sous\SelectParagraphForm',
    ]
  ];

  return $tasks;
}

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

  // Assign user 1 the "superuser" role and block it.
  $user = User::load(1);
  $user->roles[] = 'superuser';
  $user->block();
  $user->save();

  // Use Drupals password generate service.
  $secure_pass = \Drupal::service('password_generator')->generate();

  // Create user 2 and assign "superuser" role.
  $user = User::create();
  $user->setPassword($secure_pass);
  $user->enforceIsNew();
  $user->setEmail("sous_chef@fourkitchens.com");
  $user->setUsername('sous_chef');
  $user->addRole('superuser');
  $user->activate();
  $user->save();

  \Drupal::messenger()->addStatus(t('The user account "sous_chef" was created. Use ') . $secure_pass . t(' to log in.'));
}
