<?php

namespace Drupal\metadata\Form;

// Drupal core classes
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for the metadata entity edit forms.
 * @ingroup content_entity_example
 */
class MetadataForm extends ContentEntityForm {

   /**
    * {@inheritdoc}
    */
   public function buildForm(array $form, FormStateInterface $form_state) {
      /* @var $entity \Drupal\metadata\Entity\metadata */
      return parent::buildForm($form, $form_state);
   }

   /**
    * {@inheritdoc}
    */
   public function save(array $form, FormStateInterface $form_state) {
      // Redirect to metadata list after save.
      $form_state->setRedirect('entity.metadata.collection');
      $entity = $this->getEntity();
      $entity->save();
   }
}