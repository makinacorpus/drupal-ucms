<?php

namespace MakinaCorpus\Ucms\Contrib;

class MediaAdminDisplay extends NodeAdminDisplay
{
    /**
     * {@inheritdoc}
     */
    protected function getSupportedModes()
    {
        // Switch grid to default
        return [
            'grid'  => $this->t("thumbnail grid"),
            'table' => $this->t("table"),
            'list'  => $this->t("teaser list"),
        ];
    }
}
