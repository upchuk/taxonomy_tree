<?php

namespace Drupal\taxonomy_tree;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityTypeManager;

/**
 * Loads taxonomy terms in a tree
 */
class TaxonomyTermTree
{

    /**
     * @var EntityTypeManager
     */
    protected $entityTypeManager;

    /**
     * TaxonomyTermTree constructor.
     *
     * @param EntityTypeManager $entityTypeManager
     */
    public function __construct(EntityTypeManager $entityTypeManager)
    {
        $this->entityTypeManager = $entityTypeManager;
    }

    /**
     * Loads the tree of a vocabulary.
     *
     * @param $vocabulary
     * @return array
     * @throws InvalidPluginDefinitionException
     * @throws PluginNotFoundException
     */
    public function load($vocabulary): array
    {
        $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree($vocabulary);
        $tree = [];
        foreach ($terms as $tree_object) {
            $this->buildTree($tree, $tree_object, $vocabulary);
        }

        return $tree;
    }

    /**
     * Populates a tree array given a taxonomy term tree object.
     *
     * @param $tree
     * @param $object
     * @param $vocabulary
     * @throws InvalidPluginDefinitionException
     * @throws PluginNotFoundException
     */
    protected function buildTree(&$tree, $object, $vocabulary): void
    {
        if ($object->depth != 0) {
            return;
        }
        $key = 'tid_' . $object->tid;

        $tree[$key] = $object;
        $tree[$key]->children = [];
        $object_children = &$tree[$key]->children;

        $children = $this->entityTypeManager->getStorage('taxonomy_term')->loadChildren($object->tid);
        if (!$children) {
            return;
        }

        $child_tree_objects = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree($vocabulary, $object->tid);

        foreach ($children as $child) {
            foreach ($child_tree_objects as $child_tree_object) {
                if ($child_tree_object->tid == $child->id()) {
                    $this->buildTree($object_children, $child_tree_object, $vocabulary);
                }
            }
        }

        uasort($tree, function ($a, $b) {
            return $a->weight <=> $b->weight;
        });
        uasort($object_children, function ($a, $b) {
            return $a->weight <=> $b->weight;
        });
    }
}
