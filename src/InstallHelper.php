<?php

namespace Drupal\sous;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ExtensionList;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\State\StateInterface;
use Drupal\embed\Entity\EmbedButton;
use Drupal\file\Entity\File;
use Drupal\media\Entity\Media;
use Symfony\Component\DependencyInjection\ContainerInterface;

class InstallHelper implements ContainerInjectionInterface {

  /**
   * Content types to build from CSV with the same name.
   */
  const DEFAULT_CONTENT_TYPES = ['frontpage', 'page'];

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * State.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * The file system.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Extensio list.
   *
   * @var \Drupal\Core\Extension\ExtensionList
   */
  protected $extensionList;

  /**
   * Constructs a new InstallHelper object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   Entity type manager.
   * @param \Drupal\Core\State\StateInterface $state
   *   State service.
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   The file system.
   * @param \Drupal\Core\Extension\ExtensionList $extensionList
   *    Extension list.
   */
  public function __construct(
    EntityTypeManagerInterface $entityTypeManager,
    StateInterface $state,
    FileSystemInterface $fileSystem,
    ExtensionList $extensionList
  ) {
    $this->entityTypeManager = $entityTypeManager;
    $this->state = $state;
    $this->fileSystem = $fileSystem;
    $this->extensionList = $extensionList;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('state'),
      $container->get('file_system'),
      $container->get('extension.list.module')
    );
  }

  /**
   * Import some useful default content.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function importContent() {

    $profile_path = drupal_get_path('profile', 'sous');

    // Get information from the CSVs
    foreach (self::DEFAULT_CONTENT_TYPES as $content_type) {
      $keyed_content = [];
      if (file_exists($profile_path . "/default_content/$content_type.csv") &&
        ($handle = fopen($profile_path . "/default_content/$content_type.csv", 'r')) !== FALSE) {
        $header = fgetcsv($handle);
        $line_counter = 0;
        while (($content = fgetcsv($handle)) !== FALSE) {
          $keyed_content[$line_counter] = array_combine($header, $content);
          $line_counter++;
        }
        fclose($handle);

        // We can loop through here for now because the fields are identical.
        // We won't be able to do that if we choose to have other defaults.
        foreach($keyed_content as $content) {

          // Setting up the default structure.
          $values = [
            'type' => $content_type,
            'title' => $content['title'],
            'field_seo_title' => $content['seo_title'],
            'field_teaser_text' => $content['teaser_text'],
          ];

          // Setting up the media entity.
          if (!empty($content['teaser_media'])) {
            $media_entity = Media::create([
              'bundle' => 'image',
              'name' => $content['title'] . '-image',
              'uid' => 1,
              'status' => 1,
            ]);
            $media_entity->field_media_image->generateSampleItems();
            $media_entity->save();
            $values['field_teaser_media'] = [
              'target_id' => $media_entity->id(),
            ];
          }

          // Save Entity.
          $entity = $this->entityTypeManager->getStorage('node')->create($values);
          $entity->save();
        }
      }
    }

    // Change Entity Browser icon.
    $filePath = "{$this->extensionList->getPath('sous')}/assets/scaffold/files/entity-browser-icon.png";
    $fileName = basename($filePath);
    // Icons uploaded into 'embed_buttons'
    // entities get saved into the
    // following path:
    $directory = 'public://embed_buttons';
    $iconDestination = "{$directory}/{$fileName}";

    $this->fileSystem->prepareDirectory($directory, FileSystemInterface:: CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);
    $this->fileSystem->copy($filePath, $iconDestination, FileSystemInterface::EXISTS_REPLACE);

    // Create the file in the
    // Drupal system.
    $file = File::create([
      'filename' => $fileName,
      'uri' => $iconDestination,
      'status' => 1,
      'uid' => 1,
    ]);
    $file->save();

    // Load the current 'embed_button' entities.
    /** @var \Drupal\embed\EmbedButtonInterface[] */
    $entities = $this->entityTypeManager->getStorage('embed_button')->loadMultiple();

    // On installation, there should be only
    // two Entity Browser entities, which are
    // our targets.
    foreach ($entities as $entity) {
      $entity->set('icon', EmbedButton::convertImageToEncodedData($file->getFileUri()));
      $entity->save();
    }
  }
}
