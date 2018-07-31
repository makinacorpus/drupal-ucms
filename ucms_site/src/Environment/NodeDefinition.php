<?php

namespace MakinaCorpus\Ucms\Site\Environment;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\StringTranslation\TranslatableMarkup;

final class NodeDefinition
{
    /**
     * Common base for properties
     */
    private static function create(string $type): BaseFieldDefinition
    {
        return BaseFieldDefinition::create($type)
            ->setRevisionable(false)
            ->setTranslatable(false)
            ->setReadOnly(true)
            ->setRequired(true)
        ;
    }

    /**
     * Get node additional base properties
     */
    public static function getAdditionalBaseFields(): array
    {
        return [
            // References to other entities.
            // Not setting 'entity_reference' there to keep history when nodes are deleted.
            'origin_nid' => self::create('integer')
                ->setSetting('size', 'big')
                ->setLabel(new TranslatableMarkup('Original node'))
                ->setDescription(new TranslatableMarkup('Original node is the node from which the whole parenting chain was cloned'))
                ->setDefaultValue(null)
                ->setRequired(false),
            'parent_nid' => self::create('integer')
                ->setSetting('size', 'big')
                ->setLabel(new TranslatableMarkup('Parent node'))
                ->setDescription(new TranslatableMarkup('Parent node is the node from which this content was cloned'))
                ->setDefaultValue(null)
                ->setRequired(false),
            'site_id' => self::create('integer')
                ->setSetting('size', 'big')
                ->setLabel(new TranslatableMarkup('Site identifier'))
                ->setDescription(new TranslatableMarkup('Site this node belongs to'))
                ->setDefaultValue(null)
                ->setRequired(false),
            'group_id' => self::create('integer')
                ->setSetting('size', 'big')
                ->setLabel(new TranslatableMarkup('Group identifier'))
                ->setDescription(new TranslatableMarkup('Group this node belongs to'))
                ->setDefaultValue(null)
                ->setRequired(false),

            // Node flags.
            'is_shared' => self::create('boolean')
                ->setLabel(new TranslatableMarkup('Is shared'))
                ->setDescription(new TranslatableMarkup('Content is a shared content, does not belong to a site'))
                ->setDefaultValue(false),
            'is_corporate' => self::create('boolean')
                ->setLabel(new TranslatableMarkup('Is corporate content'))
                ->setDescription(new TranslatableMarkup('Corporate content has special access rights'))
                ->setDefaultValue(false),
            'is_clonable' => self::create('boolean')
                ->setLabel(new TranslatableMarkup('Is clonable'))
                ->setDescription(new TranslatableMarkup('This content can be cloned'))
                ->setDefaultValue(true),
            'is_global' => self::create('boolean')
                ->setLabel(new TranslatableMarkup('Is global'))
                ->setDescription(new TranslatableMarkup('Content is a shared content, does not belong to a site'))
                ->setDefaultValue(true),
            'is_ghost' => self::create('boolean')
                ->setLabel(new TranslatableMarkup('Is ghost'))
                ->setDescription(new TranslatableMarkup('Content cannot be seen by anyone outside of its group'))
                ->setDefaultValue(true),

            // Computed properties.
            'ucms_sites' => self::create('integer')
                ->setSetting('size', 'big')
                ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
                ->setComputed(true)
                ->setLabel(new TranslatableMarkup('Sites'))
                ->setDescription(new TranslatableMarkup('Sites the node is really attached to'))
                ->setDefaultValue([])
                ->setRequired(false),
            'ucms_allowed_sites' => self::create('integer')
                ->setSetting('size', 'big')
                ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
                ->setComputed(true)
                ->setLabel(new TranslatableMarkup('Allowed sites'))
                ->setDescription(new TranslatableMarkup('Sites the node is attached to and the current user can see'))
                ->setDefaultValue([]),
            'ucms_enabled_sites' => self::create('integer')
                ->setSetting('size', 'big')
                ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
                ->setComputed(true)
                ->setLabel(new TranslatableMarkup('Enabled sites'))
                ->setDescription(new TranslatableMarkup('Sites the node is attached to and are enabled'))
                ->setDefaultValue([])
                ->setRequired(false),
        ];
    }
}
