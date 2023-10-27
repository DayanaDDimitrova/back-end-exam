<?php

namespace Drupal\media_upload\Form;

use Drupal\Component\Render\PlainTextOutput;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Url;
use Drupal\file\FileInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Component\Utility\Bytes;
use Drupal\file\FileRepositoryInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Contribute form.
 */
class UploadMediaForm extends FormBase {
  const COMPLETE_FILE_NAME     = 0;
  const FILE_NAME              = 1;
  const EXT_NAME               = 2;
  const FILENAME_REGEX         = '/(^[\w\-\. ]+)\.([a-zA-Z0-9]+)/';
  protected $allowedImgExt     = [];
  protected $allowedDocExt     = [];
  protected $allowedVidExt     = [];
  protected $allowedAudExt     = [];
  protected $imgMaxSize        = [];
  protected $docMaxSize        = [];
  protected $vidMaxSize        = [];
  protected $audMaxSize        = [];
  protected $totalMaxSize      = 0;
  protected $singleFileMaxSize = '';
  protected $mediaBundle       = [];
  protected $logger;

  /**
   * Entity Field Manager Service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected EntityFieldManagerInterface $entityFieldManager;

  /**
   * Entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * File system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface;
   */
  protected FileSystemInterface $fileSystem;

  /**
   * File repository service.
   *
   * @var \Drupal\file\FileRepositoryInterface
   */
  protected FileRepositoryInterface $fileRepository;

  /**
   * Creates an instance of this form, with injected dependencies.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   *   Container interface.
   *
   * @return static
   *   Instance of this form.
   */
  public static function create(ContainerInterface $container) {
    return new static(
    // Load the services required to construct this class.
      $container->get('logger.factory')->get('mediaupload'),
      $container->get('entity_field.manager'),
      $container->get('entity_type.manager'),
      $container->get('file_system'),
      $container->get('file.repository')
    );
  }

  /**
   * UploadMediaForm constructor.
   *
   * @param \Psr\Log\LoggerInterface $logger
   *   Logger interface.
   * @param \Drupal\Core\Entity\EntityFieldManager $fieldManager
   *   Field manager.
   */
  public function __construct(
    LoggerInterface $logger,
    EntityFieldManagerInterface $fieldManager,
    EntityTypeManagerInterface $entityTypeManager,
    FileSystemInterface $fileSystem,
    FileRepositoryInterface $fileRepository
  ) {
    $this->logger = $logger;
    $this->entityFieldManager = $fieldManager;
    $config = $this->config('media_upload.settings');
    $this->entityTypeManager = $entityTypeManager;
    $this->fileSystem = $fileSystem;
    $this->fileRepository = $fileRepository;

    $this->allowedImgExt = explode(' ', $config->get('image_ext'));
    $this->allowedDocExt = explode(' ', $config->get('document_ext'));
    $this->allowedVidExt = explode(' ', $config->get('video_ext'));
    $this->allowedAudExt = explode(' ', $config->get('audio_ext'));

    $this->imgMaxSize = Bytes::toNumber($config->get('image_size'));
    $this->docMaxSize = Bytes::toNumber($config->get('document_size'));
    $this->vidMaxSize = Bytes::toNumber($config->get('video_size'));
    $this->audMaxSize = Bytes::toNumber($config->get('audio_size'));

    // Get the size as bytes int from the configured string.
    $this->totalMaxSize = Bytes::toNumber($config->get('total_size'));
    // Note: DropZone take #max_filesize as size string, and not bytes int,
    // (hence no conversion needed).
    $this->singleFileMaxSize = $config->get('single_size');

    $this->mediaBundle = [
      'image'    => [
        'format' => (isset($config->get('bundles')['image']) ? $this->allowedImgExt : ''),
        'bundle' => $config->get('image_bundle'),
        'field'  => $config->get('image_field'),
      ],
      'video'    => [
        'format' => (isset($config->get('bundles')['video']) ? $this->allowedVidExt : ''),
        'bundle' => $config->get('video_bundle'),
        'field'  => $config->get('video_field'),
      ],
      'document' => [
        'format' => (isset($config->get('bundles')['document']) ? $this->allowedDocExt : ''),
        'bundle' => $config->get('document_bundle'),
        'field'  => $config->get('document_field'),
      ],
      'audio'    => [
        'format' => (isset($config->get('bundles')['audio']) ? $this->allowedAudExt : ''),
        'bundle' => $config->get('audio_bundle'),
        'field'  => $config->get('audio_field'),
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'mediaupload_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    if (!$this->config('media_upload.settings')->get('bundles') || empty(array_filter($this->config('media_upload.settings')
      ->get('bundles')))
    ) {
      $this->messenger()->addWarning(
        $this->t(sprintf('No media bundle is enabled in <a href="%s">module configuration</a>',
          Url::fromRoute('media_upload.mediaupload_settings')->toString())));
      return [];
    }

    $form['dropzonejs'] = [
      '#type'                 => 'dropzonejs',
      '#title'                => $this->t('Dropzone'),
      '#required'             => TRUE,
      '#dropzone_description' => $this->t('Drop your files here'),
      '#max_filesize'         => $this->singleFileMaxSize,
      '#extensions'           => trim(implode(' ', $this->allowedDocExt) . ' ' . implode(' ', $this->allowedImgExt) . ' ' . implode(' ', $this->allowedVidExt) . ' ' . implode(' ', $this->allowedAudExt)),
    ];
    $form['submit'] = [
      '#type'  => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    try {
      $errorFlag = FALSE;
      $fileCount = 0;
      $createdMedia = [];
      $values = $form_state->getValues();
      if (!empty($values['dropzonejs']) && !empty($values['dropzonejs']['uploaded_files'])) {
        $files = $values['dropzonejs']['uploaded_files'];

        if (!$this->checkTotalSize($files)) {
          $this->logger->error('The total size of uploaded files is exceeded (@limit)', [
            '@limit' => format_size($this->totalMaxSize),
          ]);
          return $this->messenger()->addError($this->t('The total size of uploaded files is exceeded (@limit)', [
            '@limit' => format_size($this->totalMaxSize),
          ]));
        }

        foreach ($files as $file) {
          $fileInfo = [];
          if (preg_match(self::FILENAME_REGEX, $file['filename'], $fileInfo) === 1) {
            if (($bundle = $this->getBundleForFile($fileInfo[self::EXT_NAME])) !== FALSE) {
              if ($this->checkFileSize($file['path'], $bundle)) {
                /** @var \Drupal\Core\Field\FieldDefinitionInterface[] $fieldDefinitions */
                $fieldDefinitions = $this->entityFieldManager->getFieldDefinitions('media', $this->mediaBundle[$bundle]['bundle']);
                $settings = $fieldDefinitions[$this->mediaBundle[$bundle]['field']]->getSettings();
                // Prepare destination. Patterned on protected method
                // FileItem::doGetUploadLocation and public method
                // FileItem::generateSampleValue.
                $file_directory = trim($settings['file_directory'], '/');
                // Replace tokens. As the tokens might contain HTML we convert
                // it to plain text.
                $file_directory = PlainTextOutput::renderFromHtml(\Drupal::token()
                  ->replace($file_directory, []));
                $dirname = $settings['uri_scheme'] . '://' . $file_directory;
                $this->fileSystem->prepareDirectory($dirname);
                $destination = $dirname . '/' . $file['filename'];
                $data = file_get_contents($file['path']);
                if (file_exists($destination)) {
                  $this->logger->notice('File @filename already exists - It has been replaced', ['@filename' => $file['filename']]);
                  $this->messenger()->addStatus($this->t('File @filename already exists - It has been replaced', ['@filename' => $file['filename']]));
                }
                $file_entity = $this->fileRepository->writeData($data, $destination, FileSystemInterface::EXISTS_REPLACE);
                $media = $this->entityTypeManager->getStorage('media')->create($this->getFieldsForMedia($fileInfo, $bundle, $file_entity));
                $media->save();
                $createdMedia[] = $media;
                $fileCount++;
              }
              else {
                $errorFlag = TRUE;
                $this->logger->warning('The maximum size of the @filename file is exceeded', ['@filename' => $file['filename']]);
                $this->messenger()->addWarning($this->t('The maximum size of the @filename file is exceeded', ['@filename' => $file['filename']]));
              }
            }
            else {
              $errorFlag = TRUE;
              $this->logger->error('@filename - File extension is not allowed', ['@filename' => $file['filename']]);
              $this->messenger()->addError($this->t('@filename - File extension is not allowed', ['@filename' => $file['filename']]));
            }
          }
          else {
            $errorFlag = TRUE;
            $this->logger->warning('@filename - Incorrect file name', ['@filename' => $file['filename']]);
            $this->messenger()->addWarning($this->t('@filename - Incorrect file name', ['@filename' => $file['filename']]));
          }
        }
        $form_state->set('createdMedia', $createdMedia);
        if ($errorFlag && !$fileCount) {
          $this->logger->warning('No documents were uploaded');

          return $this->messenger()->addWarning($this->t('No documents were uploaded'));
        }
        else {
          if ($errorFlag) {
            $this->logger->info('Some documents have not been uploaded');
            $this->messenger()->addWarning($this->t('Some documents have not been uploaded'));
            $this->logger->info('@fileCount documents have been uploaded', ['@fileCount' => $fileCount]);

            return $this->messenger()->addStatus($this->t('@fileCount documents have been uploaded', ['@fileCount' => $fileCount]));
          }
          else {
            $this->logger->info('@fileCount documents have been uploaded', ['@fileCount' => $fileCount]);

            return $this->messenger()->addStatus($this->t('@fileCount documents have been uploaded', ['@fileCount' => $fileCount]));
          }
        }
      }
      $this->logger->warning('No documents were uploaded');

      return $this->messenger()->addWarning($this->t('No documents were uploaded'));
    }
    catch (\Exception $e) {
      $this->logger->critical($e->getMessage());

      return $this->messenger()->addError($e->getMessage());
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {

    return ['media_upload.settings'];
  }

  /**
   * Make a sum of each file size, and check if it exceeds max allowed value.
   *
   * @param array $files
   *   Files list to import.
   *
   * @return bool
   *   True if total size do not exceeds the max allowed value, false otherwise.
   */
  protected function checkTotalSize(array $files) {
    if ($this->totalMaxSize === 0) {
      return TRUE;
    }
    $totalSize = 0;
    foreach ($files as $file) {
      $totalSize += stat($file['path'])[7];
    }
    if ($totalSize > $this->totalMaxSize) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Check the size of a file.
   *
   * @param string $filePath
   *   File path.
   * @param string $bundleType
   *   Bundle type (index of $this->>mediaBundle)
   *
   * @return bool
   *   True if max size for a given file do not exceeds max size for its type.
   */
  protected function checkFileSize($filePath, $bundleType) {
    $size = stat($filePath)[7];
    switch ($bundleType) {
      case 'image':
        return ($this->imgMaxSize == 0 || $size <= $this->imgMaxSize) ? TRUE : FALSE;

      case 'video':
        return ($this->vidMaxSize == 0 || $size <= $this->vidMaxSize) ? TRUE : FALSE;

      case 'document':
        return ($this->docMaxSize == 0 || $size <= $this->docMaxSize) ? TRUE : FALSE;

      case 'audio':
        return ($this->audMaxSize == 0 || $size <= $this->audMaxSize) ? TRUE : FALSE;

      default:
        return FALSE;
    }
  }

  /**
   * Get the media bundle configured for the given format.
   *
   * @param string $fileExt
   *   File extension.
   *
   * @return string|false
   *   Return the media bundle that accepts the given format.
   */
  protected function getBundleForFile($fileExt) {
    $bundle = FALSE;
    foreach ($this->mediaBundle as $bundleType => $acceptedFormats) {
      if (in_array($fileExt, $acceptedFormats['format'])) {
        $bundle = $bundleType;
        break;
      }
    }

    return $bundle;
  }

  /**
   * Builds the array of all necessary info for the new media entity.
   *
   * @param array $fileInfo
   *   File info.
   * @param string $bundleType
   *   Bundle type (index of $this->mediaBundle).
   * @param \Drupal\file\FileInterface $file_entity
   *   File entity.
   *
   * @return array
   *   Return an array describing the new media entity.
   */
  protected function getFieldsForMedia(
    array $fileInfo,
    $bundleType,
    FileInterface $file_entity
  ) {
    $fieldFile = $this->mediaBundle[$bundleType]['field'];
    $fields = [
      'bundle'   => $this->mediaBundle[$bundleType]['bundle'],
      'name'     => $fileInfo[self::FILE_NAME],
      $fieldFile => [
        'target_id' => $file_entity->id(),
        'title'     => $fileInfo[self::FILE_NAME],
      ],
    ];

    return $fields;
  }

}
