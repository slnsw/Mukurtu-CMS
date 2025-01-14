<?php

namespace Drupal\mukurtu_roundtrip\Form\MultiStepImport;

use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

class MukurtuImportUploadSummaryForm extends MukurtuImportFormBase {

  /**
   * {@inheritdoc}.
   */
  public function getFormId() {
    return 'mukurtu_import_upload_summary_form';
  }

  /**
   * {@inheritdoc}.
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    // These are the files to import.
    $files = $this->importer->getImportFiles();
    $table = $this->buildTable($files);
    $form[] = $table;

    // Submit for validation button.
    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Next'),
      '#button_type' => 'primary',
    ];

    if ($table['import_files']['count'] <= 0) {
      $form['actions']['submit']['#attributes']['disabled'] = 'disabled';
    }

    $form['actions']['back'] = [
      '#type' => 'submit',
      '#value' => $this->t('Back'),
      '#button_type' => 'primary',
      '#submit' => ['::submitFormBack'],
    ];
    return $form;
  }

  /**
   * Build the processor select control.
   *
   * @param array $options
   * @return array
   */
  private function processorSelect(array $options) {
    if (count($options) > 0) {
      return [
        '#type' => 'select',
        '#options' => $options,
      ];
    }

    return [];
  }

  private function buildTable($files) {
    $table = [];

    if (empty($files)) {
      return $table;
    }

    $fileEntities = $this->fileStorage->loadMultiple($files);
    $fileReport = $this->importer->getFileReport();

    // Build table.
    $table['import_files'] = [
      '#type' => 'table',
      '#caption' => $this->t('Files will be imported in order, top to bottom (lowest weight first).'),
      '#header' => [
        $this->t('Import File'),
        $this->t('Import Format'),
        $this->t('Weight'),
      ],
      '#empty' => $this->t('No import files provided.'),
      '#tableselect' => FALSE,
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'group-order-weight',
        ],
      ],
      'count' => 0,
    ];

    // Build rows.
    $weight = 0;
    foreach ($fileEntities as $id => $entity) {
      $table['import_files']['count'] += 1;
      $table['import_files'][$id]['#attributes']['class'][] = 'draggable';
      $table['import_files'][$id]['#weight'] = $weight;

      $errors = isset($fileReport[$id]) ? implode("\n", $fileReport[$id]) : NULL;

      // File names.
      if (!$errors) {
        $table['import_files'][$id]['label'] = [
          '#plain_text' => $entity->getFileName(),
        ];
      } else {
        $table['import_files'][$id]['label'] = [
          '#plain_text' => $entity->getFileName() . ": " . $errors,
        ];
        $table['import_files'][$id]['#attributes'] = ['class' => ['has-error']];
      }

      // Processor options.
      $options = $this->importer->getAvailableProcessors($entity);
      $table['import_files'][$id]['processor'] = $this->processorSelect($options);

      // Weights.
      $table['import_files'][$id]['weight'] = [
        '#type' => 'weight',
        '#title' => $this->t('Weight for @title', ['@title' => $entity->getFileName()]),
        '#title_display' => 'invisible',
        '#default_value' => $weight++,
        '#attributes' => ['class' => ['group-order-weight']],
      ];
    }

    return $table;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $files = $form_state->getValue('import_files');
    $import_list = [];
    foreach ($files as $fid => $values) {
      if (isset($values['processor'])) {
        $import_list[] = ['id' => $fid, 'processor' => $values['processor']];
      }
    }

    //$this->importer->importMultiple($import_list);
    $operations = $this->importer->getValidationBatchOperations($import_list, 10);
    $this->importer->resetValidationReport();
    $this->importer->resetFileReport();
    $batch = [
      'title' => $this->t('Validating import files'),
      'operations' => $operations,
      'init_message' => $this->t('Reading the import files...'),
      'progress_message' => $this->t('Processed @current out of @total files.'),
      'error_message' => $this->t('An error occurred during while validating the files.'),
      'finished' => ['Drupal\mukurtu_roundtrip\Form\MultiStepImport\MukurtuImportUploadSummaryForm', 'afterBatchRedirect'],
    ];
    batch_set($batch);
    //$form_state->setRedirect('mukurtu_roundtrip.import_validation_complete');
  }

  /**
   * Batch 'finished' callback for file validation. No dependency injection available, very ugly.
   */
  public static function afterBatchRedirect($success, $results, $operations) {
    $tempstore = \Drupal::service('tempstore.private');
    $store = $tempstore->get('mukurtu_roundtrip_importer');
    $file_report = $store->get('import_file_report');
    if (empty($file_report)) {
      // Redirect to entity validation form if no exceptions from the files.
      //$form_state->setRedirect('mukurtu_roundtrip.import_validation_complete');
      return new RedirectResponse(\Drupal\Core\Url::fromRoute('mukurtu_roundtrip.import_validation_complete')->toString());
    } else {

    }
  }

  public function submitFormBack(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('mukurtu_roundtrip.import_start');
  }

}
