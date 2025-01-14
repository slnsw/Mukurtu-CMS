<?php

namespace Drupal\mukurtu_person\Entity;

use Drupal\node\Entity\Node;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\mukurtu_person\PersonInterface;
use Drupal\mukurtu_protocol\CulturalProtocolControlledTrait;
use Drupal\mukurtu_protocol\CulturalProtocolControlledInterface;

class Person extends Node implements PersonInterface, CulturalProtocolControlledInterface {
  use CulturalProtocolControlledTrait;

  public static function bundleFieldDefinitions(EntityTypeInterface $entity_type, $bundle, array $base_field_definitions) {
    $definitions = self::getProtocolFieldDefinitions();

    $definitions['field_keywords'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Keywords'))
      ->setDescription(t('Keywords provide added ways to group your content. They make it easier for users to search and retrieve content.'))
      ->setSettings([
        'target_type' => 'taxonomy_term',
        'handler' => 'default:taxonomy_term',
        'handler_settings' => [
          'target_bundles' => [
            'keywords' => 'keywords'
          ],
          'sort' => [
            'field' => 'name',
            'direction' => 'asc'
          ],
          'auto_create' => TRUE,
          'auto_create_bundle' => '',
        ]
      ])
      ->setDefaultValue('')
      ->setCardinality(-1)
      ->setRequired(FALSE)
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $definitions['field_media_assets'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Media Assets'))
      ->setDescription(t(''))
      ->setSettings([
        'target_type' => 'media',
        'handler' => 'default:media',
        'handler_settings' => [
          'target_bundles' => [
            'audio' => 'audio',
            'document' => 'document',
            'image' => 'image',
            'remote_video' => 'remote_video',
            'video' => 'video'
          ],
          'sort' => [
            'field' => '_none'
          ],
          'auto_create' => FALSE,
          'auto_create_bundle' => 'audio',
        ]
      ])
      ->setDefaultValue('')
      ->setCardinality(-1)
      ->setRequired(FALSE)
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $definitions['field_date_born'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Date Born'))
      ->setDescription('')
      ->setRevisionable(TRUE)
      ->setSettings([
        'datetime_type' => 'date',
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'datetime_default',
        'settings' => [
          'format_type' => 'medium',
        ],
      ])
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => -9,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $definitions['field_date_died'] = BaseFieldDefinition::create('datetime')
      ->setLabel(t('Date Died'))
      ->setDescription('')
      ->setRevisionable(TRUE)
      ->setSettings([
        'datetime_type' => 'date',
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'datetime_default',
        'settings' => [
          'format_type' => 'medium',
        ],
      ])
      ->setDisplayOptions('form', [
        'type' => 'datetime_default',
        'weight' => -9,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $definitions['field_deceased'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Deceased'))
      ->setDescription('')
      ->setDefaultValue(FALSE)
      ->setRevisionable(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $definitions['field_sections'] = BaseFieldDefinition::create('entity_reference_revisions')
      ->setLabel(t('Biographical Information Sections'))
      ->setDescription(t(''))
      ->setSettings([
        'target_type' => 'paragraph',
        'handler' => 'default:paragraph',
        'handler_settings' => [
          'negate' => FALSE,
          'target_bundles' => [
            'formatted_text_with_title' => 'formatted_text_with_title'
          ],
          'target_bundles_drag_drop' => [
            'formatted_text_with_title' => [
              'enabled' => TRUE,
              'weight' => 2,
            ],
          ],
        ]
      ])
      ->setCardinality(-1)
      ->setRequired(FALSE)
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $definitions['field_related_people'] = BaseFieldDefinition::create('entity_reference_revisions')
      ->setLabel(t('Related People'))
      ->setDescription(t(''))
      ->setSettings([
        'target_type' => 'paragraph',
        'handler' => 'default:paragraph',
        'handler_settings' => [
          'negate' => FALSE,
          'target_bundles' => [
            'related_person' => 'related_person'
          ],
          'target_bundles_drag_drop' => [
            'related_person' => [
              'enabled' => TRUE,
              'weight' => 2,
            ],
          ],
        ]
      ])
      ->setCardinality(-1)
      ->setRequired(FALSE)
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $definitions['field_related_content'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Related Content'))
      ->setDescription(t(''))
      ->setSettings([
        'target_type' => 'node',
        'handler' => 'default:node',
        'handler_settings' => [
          'target_bundles' => NULL,
          'auto_create' => FALSE,
        ]
      ])
      ->setCardinality(-1)
      ->setRequired(FALSE)
      ->setRevisionable(TRUE)
      ->setTranslatable(TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $definitions['field_representative_terms'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Representative Terms'))
      ->setDescription(t(''))
      ->setSettings([
        'target_type' => 'taxonomy_term',
        'handler' => 'default:taxonomy_term',
        'handler_settings' => [
          'target_bundles' => [
            'authors' => 'authors',
            'contributor' => 'contributor',
            'creator' => 'creator',
            'people' => 'people',
            'publisher' => 'publisher',
          ],
          'sort' => [
            'field' => 'name',
            'direction' => 'asc'
          ],
          'auto_create' => FALSE,
          'auto_create_bundle' => 'people',
        ]
      ])
      ->setDefaultValue('')
      ->setCardinality(-1)
      ->setRequired(FALSE)
      ->setRevisionable(TRUE)
      ->setTranslatable(FALSE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    return $definitions;
  }

}
