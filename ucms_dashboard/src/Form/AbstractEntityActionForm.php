<?php

namespace MakinaCorpus\Ucms\Dashboard\Form;

use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

abstract class AbstractEntityActionForm extends FormBase
{
    protected function setEntity(FormStateInterface $form_state, $entity)
    {
        $form_state->setTemporaryValue('entity', $entity);
    }

    protected function getEntity(FormStateInterface $form_state)
    {
        return $form_state->getTemporaryValue('entity');
    }

    /**
     * Get entity manager
     *
     * @return EntityManager
     */
    protected function getEntityManager()
    {
        // Not proud of this one...
        return \Drupal::service('entity.manager');
    }

    /**
     * Get entity storage
     *
     * @param string $entityTypeId
     *
     * @return EntityStorageInterface
     */
    protected function getEntityStorage($entityTypeId)
    {
        return $this->getEntityManager()->getStorage($entityTypeId);
    }
}
