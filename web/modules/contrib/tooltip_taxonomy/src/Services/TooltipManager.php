<?php

namespace Drupal\tooltip_taxonomy\Services;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Component\Plugin\Factory\FactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\node\Entity\Node;

/**
 * Tooltip filter condition manager class.
 *
 * @author Mingsong Hu
 */
class TooltipManager {

  /**
   * Drupal Condition plugin for path.
   *
   * @var \Drupal\Core\Condition\ConditionPluginBase
   */
  protected $pathCondition;

  /**
   * Drupal condition plugin for content type.
   *
   * @var \Drupal\Core\Condition\ConditionPluginBase
   */
  protected $contentTypeCondition;

  /**
   * The field type manager.
   *
   * @var \Drupal\tooltip_taxonomy\Services\FieldTypeManager
   */
  protected $fieldTypeManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * All conditions.
   *
   * @var array
   */
  protected $allConditions = [];

  /**
   * The render service.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * Constructs an TooltipConditionManager object.
   *
   * @param \Drupal\Component\Plugin\Factory\FactoryInterface $plugin_factory
   *   Plugin factory instance.
   * @param \Drupal\tooltip_taxonomy\Services\FieldTypeManager $field_type_manager
   *   Field type service instance.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entityTypeManager.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   Render service instance.
   */
  public function __construct(FactoryInterface $plugin_factory, FieldTypeManager $field_type_manager, EntityTypeManagerInterface $entity_type_manager, RendererInterface $renderer) {
    $this->pathCondition = $plugin_factory->createInstance('request_path');
    $this->contentTypeCondition = $plugin_factory->createInstance('node_type');
    $this->fieldTypeManager = $field_type_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->renderer = $renderer;
  }

  /**
   * Check the path and content type.
   *
   * Return all conditions that match the restrictions.
   *
   * @param \Drupal\Core\Entity\ContentEntityBase $node
   *   The node being viewed.
   *
   * @return array
   *   All filter conditions matched.
   */
  public function checkPathAndContentType(ContentEntityBase $entity) {
    $all_con = $this->getAllFilterCondition();
    $match_cons = [];

    if (!empty($all_con)) {
      $this->allConditions = $all_con;
      // Check the path & content types.
      foreach ($all_con as $con) {
        $path_con = $con->get('path');
        if (!empty($path_con)) {
          $this->pathCondition->setConfiguration($path_con);
          if ($this->pathCondition->evaluate() ^ $this->pathCondition->isNegated()) {
            $content_type_con = $con->get('contentTypes');

            if (!empty($content_type_con) && $entity instanceof Node) {
              $this->contentTypeCondition->setConfiguration($content_type_con)
                ->setContextValue('node', $entity);
              if ($this->contentTypeCondition->evaluate()) {
                $match_cons[] = $con;
              }
            }
            else {
              // Either this condition apply to all content types
              // or the entity is not a Node.
              $match_cons[] = $con;
            }
          }
        }
      }
    }

    return $match_cons;
  }

  /**
   * Get all tooltip filter conditions.
   *
   * @return array|\Drupal\Core\Entity\EntityInterface[]
   *   All filter conditions.
   */
  protected function getAllFilterCondition() {
    // Load the next release node.
    $cids = \Drupal::entityQuery('filter_condition')
    // ->exists('vids')
      ->sort('weight')
      ->execute();

    if (empty($cids)) {
      return [];
    }

    // Load all nodes matched the conditions.
    return $this->entityTypeManager->getStorage('filter_condition')->loadMultiple($cids);
  }

  /**
   * Attach tooltip into a field text.
   *
   * @param string $view_mode
   *   View mode of the field.
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity type.
   * @param string $field_name
   *   The field machine name.
   * @param array $field_value
   *   The field value.
   * @param array $tag
   *   Cache tags.
   *
   * @return string
   *   New text with tooltip markup.
   */
  public function addTooltip(string $view_mode, EntityInterface $entity, string $field_name, array $field_value, array &$tag) {
    $match_cons = $this->checkPathAndContentType($entity);
    // Taxonomy terms replacement array.
    $pattern_map = [];
    // Search keywords.
    $pattern_map['search'] = [];
    // Replace array.
    $pattern_map['replace'] = [];

    foreach ($match_cons as $con) {
      $allowed_views = $con->get('view');
      // Text formats this condition applied to.
      $formats = $con->get('formats');
      // Only generate tooltip for certain text formats.
      if (!in_array($field_value['#format'], $formats)) {
        continue;
      }

      if (!empty($allowed_views)) {
        $not_all = FALSE;
        foreach ($allowed_views as $mode) {
          if ($mode !== '0') {
            $not_all = TRUE;
            break;
          }
        }

        if ($not_all && !in_array($view_mode, $allowed_views)) {
          // The current view mode doesn't match this condition.
          continue;
        }
      }

      $allowed_fields = $con->get('field');
      // Is there any selected field within this condition?
      if (!empty($allowed_fields)) {
        $field_key = $entity->getEntityTypeId() . '-' . $field_name;
        // Check if the field is a selected field in this condition.
        if (in_array($field_key, $allowed_fields)) {
          // Add the taxonomy terms from this condition into the pattern array.
          $this->addVocabularyReplacement($con->get('vids'), $pattern_map);
          // Cache tag.
          $tag[] = 'tooltip_taxonomy:' . $con->id();
        }
      }
      // No selected fields, all field applied.
      else {
        // Add the taxonomy terms from this condition into the pattern array.
        $this->addVocabularyReplacement($con->get('vids'), $pattern_map);
        // Cache tag.
        $tag[] = 'tooltip_taxonomy:' . $con->id();
      }
    }
    // Replace the taxonomy terms with tooltip markup.
    $pattern_check = $pattern_map['search'];
    foreach ($pattern_check as $k => $check) {
      if (!preg_match($check, $field_value['#text'])) {
        unset($pattern_check[$k]);
      }
    }
    if (count($pattern_check) > 0) {
      // patch to remove tooltip if already exist in other MATCHING tooltips
      foreach ($pattern_check as $small_key => $small) {
        foreach ($pattern_check as $big_key => $big) {
          if ($small_key == $big_key) {
            continue;
          }
          $big = preg_replace('(^\/\\\b|\\\b\/$)', '', $big);
          // remove tooltip if duplicated
          if (preg_match($small, $big) === 1) {
            unset($pattern_map['search'][$small_key]);
            unset($pattern_map['replace'][$small_key]);
          }
        }
      }
      $result = $this->replaceContent($field_value['#text'], $pattern_map);
    }
    else {
      return '';
    }

    return $result;
  }

  /**
   * Check if an vocabulary is used for tooltip.
   *
   * @param string $vid
   *   Vocabulary ID.
   *
   * @return array
   *   All conditions' ID in which the Vocabulary
   *   is used for tooltip.
   */
  public function hasTooltip(string $vid) {
    $all_cons = $this->getAllFilterCondition();
    $result = [];

    foreach ($all_cons as $con) {
      if (in_array($vid, $con->get('vids'))) {
        $result[] = $con->id();
      }
    }

    return $result;
  }

  /**
   * Add new taxonomy term pattern into the replacement array,.
   *
   * That will be used to replace the field text with tooltip markup.
   *
   * @param array $vids
   *   Vocabulary array.
   * @param array $pattern_map
   *   Replacement pattern array.
   *
   * @return int
   *   The number of taxonomy terms added.
   */
  protected function addVocabularyReplacement(array $vids, array &$pattern_map) {
    $count = 0;

    if (empty($vids)) {
      return 0;
    }

    foreach ($vids as $vid) {
      $term_tree = $this->loadAllTaxonomyTerms($vid);
      if (!empty($term_tree)) {
        foreach ($term_tree as $term) {
          $des = strip_tags($term->description__value, "<b><i><strong><span><br><a><em>");
          if (empty($des)) {
            continue;
          }
          $tooltip_render = [
            '#theme' => 'tooltip_taxonomy',
            '#tooltip_id' => $term->vid . '-' . $term->tid,
            '#term_name' => $term->name,
            '#description' => $des,
          ];
          $name_pattern = '/\b' . preg_quote($term->name, '/') . '\b/';
          // Check if there is a same term name.
          $name_key = array_search($name_pattern, $pattern_map['search']);
          if ($name_key === FALSE) {
            // New term.
            $pattern_map['search'][] = $name_pattern;
            $pattern_map['replace'][] = $this->renderer->renderPlain($tooltip_render);
          }
          else {
            // Overwrite existing term.
            // Conditions that has bigger weight value
            // will overwrite the same term name.
            $pattern_map['replace'][$name_key] = $this->renderer->renderPlain($tooltip_render);
          }
          $count++;
        }
      }
    }

    return $count;
  }

  /**
   * Load all taxonomy terms of a vocabulary.
   *
   * @param string $vid
   *   Vocabulary ID.
   *
   * @return array
   *   All entities in the entity bundle.
   */
  protected function loadAllTaxonomyTerms(string $vid) {
    return $this->entityTypeManager->getStorage('taxonomy_term')->loadTree($vid);
  }

  /**
   * Replace taxonomy terms with tooltip for html content.
   * Avoid html markup tag and the attributes from being modified.
   *
   * @param string $html
   *   The original html markup
   * @param array $pattern_map
   *   Replace patterns.
   * @return string
   *   The new html markup with tooltip.
   */
  protected function replaceContent(string $html, array $pattern_map) {
    $content_start = 0;
    $tag_begin = strpos($html, '<');
    $new_html = '';
    $length = strlen($html);
    while ($tag_begin !== FALSE) {
      $tag_end = strpos($html, '>', $tag_begin);

      if ($tag_end === FALSE) {
        // Invalid html markup.
        // Return the original html.
        return $html;
      }
      else {
        // The end '>' should be included in.
        $tag_end++;
      }

      if ($content_start >= $length) {
        // Reach the end of the html markup.
        break;
      }

      if ($content_start < $tag_begin) {
        // There are content before the tag.
        $content = substr($html, $content_start, $tag_begin - $content_start);
        // Replace the taxonomy term with tooltip.
        $new_html .= preg_replace($pattern_map['search'], $pattern_map['replace'], $content);
      }
      // Append the html tag markup.
      $new_html .= substr($html, $tag_begin, $tag_end - $tag_begin);
      $content_start = $tag_end;
      $tag_begin = strpos($html, '<', $tag_end);
    }

    if ($content_start < $length) {
      // There are content before the tag.
      $content = substr($html, $content_start, $length - $content_start);
      // Replace the taxonomy term with tooltip.
      $new_html .= preg_replace($pattern_map['search'], $pattern_map['replace'], $content);
    }

    return $new_html;
  }

}
