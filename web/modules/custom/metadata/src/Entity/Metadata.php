<?php

namespace Drupal\metadata\Entity;

// Drupal core classes
use Drupal;
use Drupal\Core\Entity\EditorialContentEntityBase;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Defines the metadata entity.
 *
 * @ingroup metadata
 *
 * @ContentEntityType(
 *   id = "metadata",
 *   label = @Translation("Metadata"),
 *   base_table = "metadata",
 *   data_table = "metadata_field_data",
 *   revision_table = "metadata_revision",
 *   revision_data_table = "metadata_field_revision",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "url_path" = "url_path",
 *     "title_page" = "title_page",
 *     "meta_title" = "title_page",
 *     "meta_title_description" = "meta_title_description",
 *     "revision" = "vid",
 *     "status" = "status",
 *     "published" = "status",
 *     "uid" = "uid",
 *     "owner" = "uid"
 *   },
 *   revision_metadata_keys = {
 *     "revision_user" = "revision_uid",
 *     "revision_created" = "revision_timestamp",
 *     "revision_log_message" = "revision_log"
 *   },
 *   handlers = {
 *     "access" = "Drupal\metadata\MetadataAccessControlHandler",
 *     "views_data" = "Drupal\metadata\MetadataViewsData",
 *     "form" = {
 *      "add" = "Drupal\metadata\Form\MetadataForm",
 *      "edit" = "Drupal\metadata\Form\MetadataForm",
 *      "delete" = "Drupal\metadata\Form\MetadataDeleteForm"
 *     }
 *   },
 *   links = {
 *     "canonical" = "/metadatas/{metadata}",
 *     "delete-form" = "/metadata/{metadata}/delete",
 *     "edit-form" = "/metadata/{metadata}/edit",
 *     "create" = "/metadata/create"
 *   }
 * )
 */
class Metadata extends EditorialContentEntityBase {

   /**
    * {@inheritDoc}
    */
	public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {
		$fields = parent::baseFieldDefinitions($entity_type); // Provides id and uuid fields

		$fields['user_id'] = BaseFieldDefinition::create('entity_reference')
			->setLabel(t('User'))
			->setDescription(t('The user that created the metadata.'))
			->setSetting('target_type', 'user')
			->setSetting('handler', 'default')
			->setDisplayOptions('view', [
				'label' => 'hidden',
				'type' => 'author',
				'weight' => 0
			])
			->setDisplayOptions('form', [
				'type' => 'entity_reference_autocomplete',
				'weight' => 5,
				'settings' => [
					'match_operator' => 'CONTAINS',
					'size' => '60',
					'autocomplete_type' => 'tags',
					'placeholder' => ''
				]
			])
			->setDisplayConfigurable('form', TRUE)
			->setDisplayConfigurable('view', TRUE);

		$fields['url_path'] = BaseFieldDefinition::create('string')
			->setLabel(t('URL Path'))
			->setDescription(t('The url path of metadata'))
			->setSettings([
				'max_length' => 100,
				'text_processing' => 0
			])
			->setDefaultValue('')
			->setDisplayOptions('view', [
				'label' => 'above',
				'type' => 'string',
				'weight' => -4
			])
			->setDisplayOptions('form', [
				'type' => 'string_textfield',
				'weight' => -4
			])
			->setDisplayConfigurable('form', TRUE)
			->setDisplayConfigurable('view', TRUE);

      $fields['title_page'] = BaseFieldDefinition::create('string')
         ->setLabel(t('Title Page'))
         ->setDescription(t('The title page of metadata'))
         ->setSettings([
            'max_length' => 63,
            'text_processing' => 0
         ])
         ->setDefaultValue('')
         ->setDisplayOptions('view', [
            'label' => 'above',
            'type' => 'string',
            'weight' => -4
         ])
         ->setDisplayOptions('form', [
            'type' => 'string_textfield',
            'weight' => -4
         ])
         ->setDisplayConfigurable('form', TRUE)
         ->setDisplayConfigurable('view', TRUE);

      $fields['meta_title'] = BaseFieldDefinition::create('string')
         ->setLabel(t('Meta Title'))
         ->setDescription(t('The meta title of metadata'))
         ->setSettings([
            'max_length' => 100,
            'text_processing' => 0
         ])
         ->setDefaultValue('')
         ->setDisplayOptions('view', [
            'label' => 'above',
            'type' => 'string',
            'weight' => -4
         ])
         ->setDisplayOptions('form', [
            'type' => 'string_textfield',
            'weight' => -4
         ])
         ->setDisplayConfigurable('form', TRUE)
         ->setDisplayConfigurable('view', TRUE);

      $fields['meta_title_description'] = BaseFieldDefinition::create('string')
         ->setLabel(t('Meta Title Description'))
         ->setDescription(t('The meta title of metadata description'))
         ->setSettings([
            'max_length' => 100,
            'text_processing' => 0
         ])
         ->setDefaultValue('')
         ->setDisplayOptions('view', [
            'label' => 'above',
            'type' => 'string',
            'weight' => -4
         ])
         ->setDisplayOptions('form', [
            'type' => 'string_textfield',
            'weight' => -4
         ])
         ->setDisplayConfigurable('form', TRUE)
         ->setDisplayConfigurable('view', TRUE);

		$fields['status'] = BaseFieldDefinition::create('boolean')
			->setLabel(t('Publishing status'))
			->setDescription(t('A boolean indicating whether the Metadata entity is published.'))
			->setDefaultValue(TRUE);

		$fields['created'] = BaseFieldDefinition::create('created')
			->setLabel(t('Created'))
			->setDescription(t('The time that the entity was created.'));

		$fields['changed'] = BaseFieldDefinition::create('changed')
			->setLabel(t('Changed'))
			->setDescription(t('The time that the entity was last edited.'));

		return $fields;
	}

   /**
    * {@inheritdoc}
    *
    * Makes the current user the owner of the entity
    */
   public static function preCreate(EntityStorageInterface $storage, array &$values) {
      parent::preCreate($storage, $values);
      $values += [
         'user_id' => Drupal::currentUser()->id(),
      ];
   }

	/**
	 * Get the Owner of the entity.
	 */
	public function getOwner() {
		return $this->get('user_id')->entity;
	}

	/**
	 * Get the Owner Id of the entity.
	 */
	public function getOwnerId() {
		return $this->get('user_id')->target_id;
	}
}
