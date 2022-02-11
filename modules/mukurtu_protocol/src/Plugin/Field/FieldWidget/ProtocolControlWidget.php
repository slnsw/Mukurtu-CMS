<?php

namespace Drupal\mukurtu_protocol\Plugin\Field\FieldWidget;

use Drupal\Core\Field\WidgetBase;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Field\FieldDefinitionInterface;
use Exception;

/**
 * Mukurtu Protocol Control widget.
 *
 * @FieldWidget(
 *   id = "mukurtu_protocol_control_widget",
 *   label = @Translation("Mukurtu Protocol Control widget"),
 *   field_types = {
 *     "entity_reference",
 *     "entity_reference_revisions",
 *   }
 * )
 */
class ProtocolControlWidget extends WidgetBase {

  public static function customSubmit(array $form, FormStateInterface $form_state) {
   // $dump = print_r($form_state->getValue('field_protocol_control'), TRUE);
   //dpm("here first");
    $needSave = FALSE;
    $protocolControlValues = $form_state->getValue('field_protocol_control')[0] ?? NULL;

    /**
     * @var \Drupal\mukurtu_protocol\Entity\ProtocolControlInterface $entity
     */
    $entity = $form_state->get('protocol_control_entity');

    // If we have both a protocol control entity and some new user given values,
    // set the new values as needed and update the protocol control entity.
    if ($entity && $protocolControlValues) {
      // Privacy Setting.
      dpm($protocolControlValues);
      $field_privacy_setting = $protocolControlValues['field_sharing_setting'] ?? NULL;
      if ($field_privacy_setting && $entity->getPrivacySetting() != $field_privacy_setting) {
        $entity->setPrivacySetting($field_privacy_setting);
        $needSave = TRUE;
      }

      // Protocols.
      $protocols = $protocolControlValues['field_protocols'] ?? NULL;
      $protocols = array_column($protocols, 'target_id');
      if (!empty(array_diff($protocols, $entity->getProtocols()))) {
        $entity->setProtocols($protocols);
        $needSave = TRUE;
      }

      // Save the protocol control entity if data was altered.
      if ($needSave) {
        try {
          $entity->save();
        }
        catch (Exception $e) {
          // Can't use DI because we're in a static method.
          \Drupal::messenger()->addError(t("Failed to update protocols."));
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function formElement(FieldItemListInterface $items, $delta, array $element, array &$form, FormStateInterface $form_state) {
    $item = $items->get($delta);

    $element = [
      '#type' => 'details',
      '#field_title' => $this->fieldDefinition->getLabel(),
      '#open' => TRUE,
    ] + $element;

    /**
     * @var \Drupal\mukurtu_protocol\Entity\ProtocolControlInterface $entity
     */
    $entity = $item->entity;
    if (is_null($entity) || !$entity->access('update')) {
      return [];
    }
    //dpm("widget: " . ($entity->id() ?? $entity->uuid()));

    // Keep the protocol control entity for this item in the form.
    // if the user changes any of its fields in this widget we can
    // update it in ::customSubmit.
    $form_state->set('protocol_control_entity', $entity);

    $element['target_id'] = [
      '#type' => 'hidden',
      '#value' => $entity->id() ?? $entity->uuid(),
    ];

    // this worked.
 /*    $element['field_privacy_setting'] = [
      '#type' => 'radios',
      '#options' => $entity->getSharingSettingOptions(),
      '#default_value' => $entity->getSharingSetting(),
    ]; */


    /** @var \Drupal\Core\Entity\Display\EntityFormDisplayInterface $form_display */
    $form_display = \Drupal::entityTypeManager()->getStorage('entity_form_display')->load('protocol_control.protocol_control.default');
    $fieldnames = ['field_sharing_setting', 'field_protocols'];
    //$name = 'field_privacy_setting';
    foreach ($fieldnames as $name) {
      /**
       * @var \Drupal\Core\Field\PluginSettingsInterface $widget
       */
      $widget = $form_display->getRenderer($name);
      $items = $entity->get($name);
      $items->filterEmptyItems();
      $mockedWidget = $widget->form($items, $form, $form_state);
      $element[$name]['#access'] = $items->access('edit');
      $element[$name] = $mockedWidget['widget'];
      unset($element[$name]['#parents']);
    }
/*     $element[$name]['#type'] = $mockedWidget['widget']['#type'];
    $element[$name]['#options'] = $mockedWidget['widget']['#options'];
    $element[$name]['#default_value'] = $mockedWidget['widget']['#default_value']; */
    //unset($element[$name]['#key_column']);
    //dpm($element[$name]);

    //$fields = $entity->getFields();
    //dpm(array_keys($fields));

    //$element['test'] = $fields['field_privacy_setting']->
    //$element['field_protocols'] = $fields['field_protocols']->view();
/* $field_name = $this->fieldDefinition->getFieldStorageDefinition()->getName();
    $wrapper = 'edit-' . str_replace('_', '-', $field_name) . '-ajax-wrapper';

    $referenced_entities = $items->referencedEntities();

    $element += [
      '#type' => 'select',
      '#default_value' => isset($referenced_entities[$delta]) ? $referenced_entities[$delta]->id() : -1,
      '#options' => $this->getElementOptions($items, $delta, $element, $form, $form_state),
      '#element_validate' => [
        [static::class, 'validate'],
      ],
      '#ajax' => [
        'event' => 'change',
        'callback' => [$this, 'protocolChangeCallback'],
        'wrapper' => $wrapper,
      ],
    ]; */


    // dpm($element);
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function massageFormValues(array $values, array $form, FormStateInterface $form_state) {
    foreach ($values as &$item) {
/*       $entity = $form_state->get('protocol_control_entity');
      if ($entity->isNew()) {
        try {
          $entity->save();
          $item['target_id'] = $entity->id();
          dpm("wow now we have {$entity->id()}");
        } catch (Exception $e) {
          dpm($e);
        }
      } */
      //dpm($item);
      //$item['target_id'] = $item['value']['target_id'] ?? NULL;
      //$item['protocol_control_entity_values']['field_privacy_setting'] = $item['value']['field_privacy_setting'] ?? NULL;
      //$form_state->set('protocol_control_test_entity', ['wow']);
      //return $values[0]['value']['target_id'] ?? NULL;
    }
    return $values;
  }

  /**
   * {@inheritDoc}
   */
  public static function isApplicable(FieldDefinitionInterface $field_definition) {
    if ($field_definition->getSetting('target_type') == 'protocol_control') {
      return TRUE;
    }
    return FALSE;
  }

}
