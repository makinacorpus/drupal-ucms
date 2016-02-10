<?php

namespace MakinaCorpus\Ucms\Dashboard\Tests;

use MakinaCorpus\Drupal\Sf\Container\Tests\AbstractDrupalTest;
use MakinaCorpus\Ucms\Dashboard\Action\ActionRegistry;
use MakinaCorpus\Ucms\Dashboard\Page\LinksFilterDisplay;
use MakinaCorpus\Ucms\Dashboard\Page\Page;

class PageTest extends AbstractDrupalTest
{
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

    public function testSortManagerStuff()
    {
        $formBuilder = $this->getMock('\Drupal\Core\Form\FormBuilderInterface');
        $actionRegistry = new ActionRegistry();

        $datasource = $this->getMock('\MakinaCorpus\Ucms\Dashboard\Page\DatasourceInterface');
        $datasource->method('getSortFields')->willReturn([
            'a'       => "A",
            'kitten'  => "Aww",
            'sam'     => 'and max',
        ]);
        $datasource->method('getDefaultSort')->willReturn(['kitten', 'desc']);

        $display = $this->getMock('\MakinaCorpus\Ucms\Dashboard\Page\DisplayInterface');
        $page = new Page($formBuilder, $actionRegistry, $datasource, $display);
        $render = $page->render([
            'some'  => 'query',
            'st'    => 'sam',
        ]);

        // Tests that once clicked, new field is new field
        $this->assertNotTrue(in_array('active', $render['#sort_field']['#links']['a']['attributes']['class']));
        $this->assertNotTrue(in_array('active', $render['#sort_field']['#links']['kitten']['attributes']['class']));
        $this->assertTrue(in_array('active', $render['#sort_field']['#links']['sam']['attributes']['class']));

        // Test a bit deeper how links are built
        // @todo
    }

    public function testFiltersInBaseQueryAreDropped()
    {
        $formBuilder = $this->getMock('\Drupal\Core\Form\FormBuilderInterface');
        $actionRegistry = new ActionRegistry();

        $datasource = $this->getMock('\MakinaCorpus\Ucms\Dashboard\Page\DatasourceInterface');
        $datasource->method('getFilters')->willReturn([
            new LinksFilterDisplay('foo'),
            new LinksFilterDisplay('bar'),
            new LinksFilterDisplay('baz'),
            new LinksFilterDisplay('trout'),
        ]);

        $display = $this->getMock('\MakinaCorpus\Ucms\Dashboard\Page\DisplayInterface');

        // Ensure all filters are there
        $page = new Page($formBuilder, $actionRegistry, $datasource, $display);
        $render = $page->render();
        $this->assertArrayHasKey('foo', $render['#filters']);
        $this->assertArrayHasKey('bar', $render['#filters']);
        $this->assertArrayHasKey('baz', $render['#filters']);
        $this->assertArrayHasKey('trout', $render['#filters']);

        // Ensure that when using base query, matching filters will be removed
        $page->setBaseQuery(['bar' => 'somevalue', 'trout' => 'othervalue']);
        $render = $page->render();
        $this->assertArrayHasKey('foo', $render['#filters']);
        $this->assertArrayNotHasKey('bar', $render['#filters']);
        $this->assertArrayHasKey('baz', $render['#filters']);
        $this->assertArrayNotHasKey('trout', $render['#filters']);
    }

    public function testLinksAreCorrectlyBuildUsingQuery()
    {
        $someFilter = (new LinksFilterDisplay('awesome_test_field', "Some test field"))
            ->setChoicesMap([
                'a'       => "The A choice",
                '666'     => "The 666 choice",
                'trout'   => "The special trout slap choice",
            ])
        ;

        $render = $someFilter->build([
            'awesome_test_field' => 'a|trout',
            'unknown_field' => 27
        ], 'my/page');

        // Oh, and ensures that all links point to the current path
        foreach ($render['#links'] as $link) {
            $this->assertSame('my/page', $link['href']);
        }

        // Ensure title is OK, and filters links are here
        $this->assertSame("Some test field", $render['#heading']);
        $this->assertArrayHasKey('a', $render['#links']);
        $this->assertArrayHasKey('666', $render['#links']);
        $this->assertArrayHasKey('trout', $render['#links']);

        // 'a' and 'trout' being selected, click on 'a' removes 'a' and keeps 'trout'
        $this->assertSame('trout', $render['#links']['a']['query']['awesome_test_field']);
        // 'a' and 'trout' being selected, click on 'a' removes 'trout' and keeps 'a'
        $this->assertSame('a', $render['#links']['trout']['query']['awesome_test_field']);
        // 'a' and 'trout' being selected, click on '666' adds '666'
        $values = $render['#links']['666']['query']['awesome_test_field'];
        $values = explode(LinksFilterDisplay::URL_VALUE_SEP, $values);
        $this->assertCount(3, $values);
        $this->assertTrue(in_array('a', $values));
        $this->assertTrue(in_array('trout', $values));
        $this->assertTrue(in_array('666', $values));

        // In all cases, the external parameter must be untouched
        $this->assertSame(27, $render['#links']['a']['query']['unknown_field']);
        $this->assertSame(27, $render['#links']['666']['query']['unknown_field']);
        $this->assertSame(27, $render['#links']['trout']['query']['unknown_field']);
    }
}

