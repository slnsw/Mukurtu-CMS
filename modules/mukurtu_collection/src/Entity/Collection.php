<?php

namespace Drupal\mukurtu_collection\Entity;

use Drupal\node\Entity\Node;
use Drupal\Core\Entity\EntityInterface;
use Drupal\mukurtu_collection\Entity\CollectionInterface;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Entity\EntityStorageInterface;


class Collection extends Node implements CollectionInterface {
  /**
   * Add an entity to a collection.
   *
   * @param EntityInterface $entity
   *   The entity to add to the collection.
   *
   * @return void
   */
  public function add(EntityInterface $entity): void {
    $items = $this->get(MUKURTU_COLLECTION_FIELD_NAME_ITEMS)->getValue();
    $items[] = ['target_id' => $entity->id()];
    $this->set(MUKURTU_COLLECTION_FIELD_NAME_ITEMS, $items);
  }

  /**
   * Remove an entity from a collection.
   *
   * @param EntityInterface $entity
   *   The entity to remove from the collection.
   *
   * @return void
   */
  public function remove(EntityInterface $entity): void {
    $needle = $entity->id();
    $items = $this->get(MUKURTU_COLLECTION_FIELD_NAME_ITEMS)->getValue();
    foreach ($items as $delta => $item) {
      if ($item['target_id'] == $needle) {
        unset($items[$delta]);
      }
    }
    $this->set(MUKURTU_COLLECTION_FIELD_NAME_ITEMS, $items);
  }

  /**
   * {@inheritdoc}
   */
  public function postSave(EntityStorageInterface $storage, $update = TRUE) {
    // Invalid the cache of referenced entities
    // to trigger recalculation of the computed fields.
    $refs = $this->get(MUKURTU_COLLECTION_FIELD_NAME_ITEMS)->referencedEntities() ?? NULL;
    if (!empty($refs)) {
      foreach ($refs as $ref) {
        Cache::invalidateTags($ref->getCacheTagsToInvalidate());
      }
    }

    // Invalid the collection's cache as well.
    Cache::invalidateTags($this->getCacheTagsToInvalidate());
  }

}
