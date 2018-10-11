<?php

namespace MakinaCorpus\Ucms\Seo\StoreLocator;

interface StoreLocatorInterface
{
    public function getTitle();

    public function getMapItems();

    public function getNodes();

    public function getLinks();

    public function getTypeLabel($type = null);

    public function getSubAreaLabel();

    public function getLocalityLabel();
}
