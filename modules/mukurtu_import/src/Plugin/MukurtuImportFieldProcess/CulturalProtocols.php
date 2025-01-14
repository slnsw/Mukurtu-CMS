<?php

namespace Drupal\mukurtu_import\Plugin\MukurtuImportFieldProcess;

use Drupal\mukurtu_import\MukurtuImportFieldProcessPluginBase;
use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Plugin implementation of the mukurtu_import_field_process.
 *
 * @MukurtuImportFieldProcess(
 *   id = "cultural_protocol",
 *   label = @Translation("Cultural Protocols"),
 *   field_types = {
 *     "cultural_protocol",
 *   },
 *   weight = 0,
 *   description = @Translation("Cultural Protocols.")
 * )
 */
class CulturalProtocols extends MukurtuImportFieldProcessPluginBase {
  /**
   * {@inheritdoc}
   */
  public function getProcess(FieldDefinitionInterface $field_config, $source, $context = []) {
    $multivalue_delimiter = $context['multivalue_delimiter'] ?? ';';
    $subfield = $context['subfield'] ?? NULL;
    $process = [];

    if ($subfield == 'protocols') {
      $process[] = [
        'plugin' => 'explode',
        'source' => $source,
        'delimiter' => $multivalue_delimiter,
      ];
      $process[] = [
        'plugin' => 'mukurtu_entity_lookup',
        'value_key' => \Drupal::entityTypeManager()->getDefinition('protocol')->getKey('label'),
        'ignore_case' => TRUE,
        'entity_type' => 'protocol',
      ];
      $process[] = [
        'plugin' => 'protocols',
      ];
      return $process;
    }

    if ($subfield == 'sharing_setting') {
      return $source;
    }

    return $source;
  }

}
