<?php

namespace MakinaCorpus\Ucms\Contrib\DependencyInjection;

use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * We need a bundle for registering the extension
 */
class ContribBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function getContainerExtension()
    {
        return new ContribExtension();
    }
}
