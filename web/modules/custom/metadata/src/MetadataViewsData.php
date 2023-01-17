<?php

namespace Drupal\metadata;

use Drupal\views\EntityViewsData;

/**
 * Provides views data for Metadata entities.
 *
 */
class MetadataViewsData extends EntityViewsData {

   /**
    * Returns the Views data for the entity.
    */
   public function getViewsData() {
      return parent::getViewsData();
   }
}