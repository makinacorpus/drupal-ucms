<?php

namespace MakinaCorpus\Ucms\Dashboard\Twig\Extension;

use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\node\NodeInterface;
use MakinaCorpus\Ucms\Contrib\TypeHandler;

class HelperExtension extends \Twig_Extension
{
    use StringTranslationTrait;

    private $debug;
    private $entityManager;
    private $typeHandler;

    /**
     * Default constructor
     */
    public function __construct(EntityManager $entityManager, TypeHandler $typeHandler = null, bool $debug = false)
    {
        $this->debug = $debug;
        $this->entityManager = $entityManager;
        $this->typeHandler = $typeHandler;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('ucms_content_type', [$this, 'renderContentType']),
            new \Twig_SimpleFunction('ucms_user_name', [$this, 'renderUserName']),
        ];
    }

    /**
     * Render content type
     */
    public function renderContentType($typeOrNodeOrId) : string
    {
        $type = null;

        if ($typeOrNodeOrId instanceof NodeInterface) {
            $type = $typeOrNodeOrId->bundle();
        } else if (\is_string($typeOrNodeOrId)) {
            $type = $typeOrNodeOrId;
        } else if (\is_numeric($typeOrNodeOrId)) {
            if ($node = $this->entityManager->getStorage('node')->load($typeOrNodeOrId)) {
                $type = $node->bundle();
            } else if ($this->debug) {
                throw new \InvalidArgumentException(sprintf("ucms_content_type() was given a non existing node identifier: '%s'", $typeOrNodeOrId)); 
            }
        } else if ($this->debug) {
            throw new \InvalidArgumentException(sprintf("First parameter of ucms_content_type() must be an %s instance or an content type string or a node identifier", NodeInterface::class)); 
        }

        if (!$type) {
            return $this->t("Unknown");
        }

        if (!$typeName = $this->typeHandler->getTypeLabel($type)) {
            if ($this->debug) {
                throw new \InvalidArgumentException(sprintf("ucms_content_type() was given a non existing content type: '%s'", $type));
            }
            return $this->t("Unknown");
        }

        return $typeName;
    }

    /**
     * Render user name
     */
    public function renderUserName($accountOrId, bool $withLink = false) : string
    {
        $account = null;

        if (\is_numeric($accountOrId)) {
            if (0 === (int)$accountOrId) {
                return $this->t("Anonymous");
            }
            $account = $this->entityManager->getStorage('user')->load($accountOrId);
        } else if ($accountOrId instanceof AccountInterface) {
            $account = $accountOrId;
        } else if ($this->debug) {
            throw new \InvalidArgumentException(sprintf("First parameter of ucms_user_name() must be an %s instance or an acccount identifier", AccountInterface::class));
        }

        if ($account instanceof AccountInterface) {
            return $account->getDisplayName();
        }

        return $this->t("Unknown");
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'ucms_dashboard_helper';
    }
}
