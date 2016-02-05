<?php

namespace MakinaCorpus\Ucms\Dashboard\Tests;

use MakinaCorpus\Drupal\Sf\Container\Tests\AbstractDrupalTest;
use MakinaCorpus\Ucms\Dashboard\Action\ActionRegistry;
use MakinaCorpus\Ucms\Dashboard\Page\Page;

class PageTest extends AbstractDrupalTest
{
    /**
     * Tests a lot of stuff
     */
    public function testEmptyPage()
    {
        $formBuilder = $this
            ->getMockBuilder('\Drupal\Core\Form\FormBuilderInterface')
            ->getMock()
        ;

        $actionRegistry = new ActionRegistry();

        $datasource = $this
            ->getMockBuilder('\MakinaCorpus\Ucms\Dashboard\Page\DatasourceInterface')
            ->getMock()
        ;

        $display = $this
            ->getMockBuilder('\MakinaCorpus\Ucms\Dashboard\Page\DisplayInterface')
            ->getMock()
        ;

        $page = new Page($formBuilder, $actionRegistry, $datasource, $display);
        $render = $page->render([], 'some/path');

        $this->assertArrayHasKey('#theme', $render);
        $this->assertArrayHasKey('#filters', $render);
        $this->assertArrayHasKey('#display', $render);
        $this->assertArrayHasKey('#items', $render);
        $this->assertArrayHasKey('#pager', $render);
        $this->assertArrayHasKey('#sort_field', $render);
        $this->assertArrayHasKey('#sort_order', $render);
        $this->assertArrayNotHasKey('#search', $render);

        $this->assertEmpty($render['#filters']);
        $this->assertEmpty($render['#items']);
    }

    public function testFilterHidesWhenInBaseQuery()
    {
        // @todo
    }
}
