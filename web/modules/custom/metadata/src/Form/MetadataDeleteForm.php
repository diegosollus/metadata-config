<?php

namespace Drupal\metadata\Form;

// Core drupal classes
use Drupal\Core\{
   Entity\ContentEntityConfirmFormBase,
   Form\FormStateInterface,
   StringTranslation\TranslatableMarkup,
   Url
};

/**
 * Provides a form for deleting a content_entity_example entity.
 * @ingroup metadata
 */
class MetadataDeleteForm extends ContentEntityConfirmFormBase {

   /**
    * {@inheritdoc}
    */
   public function getQuestion(): TranslatableMarkup {
      return $this->t('Are you sure you want to delete %name?', [
         '%name' => $this->entity->label()
      ]);
   }

   /**
    * {@inheritdoc}
    *
    * If the delete command is canceled, return to the metadata.
    */
   public function getCancelUrl(): Url {
      return Url::fromRoute('entity.metadata.edit_form', [
         'metadata' => $this->entity->id()
      ]);
   }


   /**
    * {@inheritdoc}
    */
   public function getConfirmText(): TranslatableMarkup {
      return $this->t('Delete');
   }

   /**
    * {@inheritdoc}
    * Delete the entity.
    */
   public function submitForm(array &$form, FormStateInterface $form_state) {
      $entity = $this->getEntity();
      $entity->delete();

      $this->logger('metadata')->notice('deleted %title.',
         ['%title' => $this->entity->label()]
      );

      // Redirect to metadata list after delete.
      $form_state->setRedirect('entity.metadata.collection');
   }
}