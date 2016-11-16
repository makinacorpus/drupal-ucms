<?php

namespace MakinaCorpus\Ucms\Seo\Portlet;

use Drupal\Core\Entity\EntityManager;
use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\ACL\Permission;
use MakinaCorpus\Ucms\Contrib\NodeReference;
use MakinaCorpus\Ucms\Dashboard\Action\Action;
use MakinaCorpus\Ucms\Dashboard\Page\AbstractDisplay;

class DeadLinkPortletDisplay extends AbstractDisplay
{
    use StringTranslationTrait;

    private $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    protected function displayAs($mode, $items)
    {
        $rows   = [];

        $nodeStorage = $this->entityManager->getStorage('node');

        foreach ($items as $item) {
            /** @var $item NodeReference */

            switch ($item->getType()) {
                case 'link':
                    $typeLabel = $this->t("Link");
                    break;
                case 'media':
                    $typeLabel = $this->t("Media");
                    break;
                case 'unknown':
                    $typeLabel = $this->t("Undefined");
                    break;
                default:
                    $typeLabel = $item->getType();
                    break;
            }

            $fieldName = $item->getFieldName();
            if ($info = field_info_field($fieldName)) {
                $fieldName = $info['label'];
            }

            $source = $nodeStorage->load($item->getSourceId());
            $target = $item->targetExists() ? $nodeStorage->load($item->getTargetId()) : null;

            if ($source->access(Permission::UPDATE)) {
                $actions = [
                    '#theme' => 'ucms_dashboard_actions',
                    '#actions' => [
                        new Action(
                            "",
                            'node/' . $source->id() . '/edit',
                            ['attributes' => ['class' => ['btn-sm']]],
                            'share-alt'
                        )
                    ]
                ];
            } else {
                $actions = '';
            }

            $rows[] = [
                check_plain($source ? $source->title : 'error'),
                check_plain($fieldName),
                $target ? check_plain($target->title) : '<em>' . $item->getTargetId() . '</em>',
                $item->targetExists() ? $this->t('Unpublished') : $this->t('Deleted'),
                $typeLabel,
                $actions,
            ];
        }

        return [
            '#theme'  => 'table',
            '#header' => [
                $this->t('Title'),
                $this->t('Field'),
                $this->t('Destination'),
                $this->t('Reason'),
                $this->t("Type"),
                $this->t('Edit'),
            ],
            '#rows'   => $rows,
            '#empty'  => $this->t('No dead link'),
        ];
    }
}
