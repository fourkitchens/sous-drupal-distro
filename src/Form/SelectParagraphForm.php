<?php

namespace Drupal\sous\Form;

use Drupal\Core\Extension\ModuleInstallerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides the selectable options to install paragraph modules.
 */
class SelectParagraphForm extends FormBase{

  /**
   * The module installer.
   *
   * @var \Drupal\Core\Extension\ModuleInstallerInterface
   */
  protected $moduleInstaller;


  /**
   * SelectParagraphForm constructor.
   *
   * @param \Drupal\Core\Extension\ModuleInstallerInterface $module_installer
   *   The module installer.
   */
  public function __construct(ModuleInstallerInterface $module_installer) {
    $this->moduleInstaller = $module_installer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $container->get('module_installer');
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'select_paragraph_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#title'] = 'Enable paragraph modules';
    $form['settings']['enable'] = [
      '#title' => t('Install paragraph based contrib modules?'),
      '#type' => 'checkbox',
      '#default_value' => FALSE,
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => 'Save',
      '#button_type' => 'primary',
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($form_state->getValue('enable')) {
      global $install_state;
      $optional_modules = $install_state['profile_info']['optional'];
      foreach ($optional_modules as $optional_module) {
        $this->moduleInstaller->install($optional_module);
      }
    }
  }

}
