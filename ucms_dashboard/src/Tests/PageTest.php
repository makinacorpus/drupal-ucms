<?php

namespace MakinaCorpus\Ucms\Dashboard\Tests;

use MakinaCorpus\Drupal\Sf\Tests\AbstractDrupalTest;
use MakinaCorpus\Ucms\Dashboard\Action\ActionRegistry;
use MakinaCorpus\Ucms\Dashboard\Page\Filter;
use MakinaCorpus\Ucms\Dashboard\Page\Page;

class PageTest extends AbstractDrupalTest
{
    public function testEmptyPage()
    {
        $formBuilder = $this->getMockBuilder('\Drupal\Core\Form\FormBuilderInterface')->getMock();
        $formBuilder->method('getForm')->willReturn(['#type' => 'form']);

        $actionRegistry = new ActionRegistry();
        $datasource = $this->getMockBuilder('\MakinaCorpus\Ucms\Dashboard\Page\DatasourceInterface')->getMock();
        $display = $this->getMockBuilder('\MakinaCorpus\Ucms\Dashboard\Page\DisplayInterface')->getMock();

        $page = new Page($formBuilder, $actionRegistry, $datasource, $display);
        $render = $page->render([], 'some/path');

        $this->assertArrayHasKey('#theme', $render);
        $this->assertArrayHasKey('#filters', $render);
        $this->assertArrayHasKey('#display', $render);
        $this->assertArrayHasKey('#items', $render);
        $this->assertArrayHasKey('#pager', $render);
        $this->assertArrayNotHasKey('#search', $render);
        $this->assertEmpty($render['#filters']);
        $this->assertEmpty($render['#items']);

        // Sort field and sort order should no be displayed if there is no
        // sort field given by the datasource
        $this->assertArrayNotHasKey('#sort_field', $render);
        $this->assertArrayNotHasKey('#sort_order', $render);

        // But it should when thers is!
        $datasource->method('getSortFields')->willReturn(['some' => 'field', 'other' => 'field too']);
        $render = $page->render([], 'some/path');
        $this->assertArrayHasKey('#sort_field', $render);
        $this->assertArrayHasKey('#sort_order', $render);

        // Search form should be here whenever the datasource tells us to
        $datasource->method('hasSearchForm')->willReturn(true);
        $render = $page->render([], 'some/path');
        $this->assertArrayHasKey('#search', $render);
    }

    public function testSortManagerStuff()
    {
        $formBuilder = $this->getMockBuilder('\Drupal\Core\Form\FormBuilderInterface')->getMock();
        $actionRegistry = new ActionRegistry();

        $datasource = $this->getMockBuilder('\MakinaCorpus\Ucms\Dashboard\Page\DatasourceInterface')->getMock();
        $datasource->method('getSortFields')->willReturn([
            'a'       => "A",
            'kitten'  => "Aww",
            'sam'     => 'and max',
        ]);
        $datasource->method('getDefaultSort')->willReturn(['kitten', 'desc']);

        $display = $this->getMockBuilder('\MakinaCorpus\Ucms\Dashboard\Page\DisplayInterface')->getMock();
        $page = new Page($formBuilder, $actionRegistry, $datasource, $display);
        $render = $page->render([
            'some'  => 'query',
            'st'    => 'sam',
        ]);

        // Tests that once clicked, new field is new field
        $this->assertNotTrue(in_array('active', $render['#sort_field']['#links']['a']['attributes']['class']));
        $this->assertNotTrue(in_array('active', $render['#sort_field']['#links']['kitten']['attributes']['class']));
        $this->assertTrue(in_array('active', $render['#sort_field']['#links']['sam']['attributes']['class']));

        // Default link must not have the field parameter
        $this->assertArrayNotHasKey('st', $render['#sort_field']['#links']['kitten']['query']);

        // Tests that arbitrary values are kept
        // In active field links
        $this->assertArrayHasKey('some', $render['#sort_field']['#links']['sam']['query']);
        $this->assertSame('query', $render['#sort_field']['#links']['sam']['query']['some']);
        // In inactive field links
        $this->assertArrayHasKey('some', $render['#sort_field']['#links']['kitten']['query']);
        $this->assertSame('query', $render['#sort_field']['#links']['kitten']['query']['some']);
        // In active sort links
        $this->assertArrayHasKey('some', $render['#sort_order']['#links']['desc']['query']);
        $this->assertSame('query', $render['#sort_order']['#links']['desc']['query']['some']);
        // In inactive sort links
        $this->assertArrayHasKey('some', $render['#sort_order']['#links']['asc']['query']);
        $this->assertSame('query', $render['#sort_order']['#links']['asc']['query']['some']);
    }

    public function testFiltersInBaseQueryAreDropped()
    {
        $formBuilder = $this->getMockBuilder('\Drupal\Core\Form\FormBuilderInterface')->getMock();
        $actionRegistry = new ActionRegistry();

        $datasource = $this->getMockBuilder('\MakinaCorpus\Ucms\Dashboard\Page\DatasourceInterface')->getMock();
        $datasource->method('getFilters')->willReturn([
            new Filter('foo'),
            new Filter('bar'),
            new Filter('baz'),
            new Filter('trout'),
        ]);

        $display = $this->getMockBuilder('\MakinaCorpus\Ucms\Dashboard\Page\DisplayInterface')->getMock();

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
        $someFilter = (new Filter('awesome_test_field', "Some test field"))
            ->setChoicesMap([
                'a'       => "The A choice",
                '666'     => "The 666 choice",
                'trout'   => "The special trout slap choice",
                'unsafe'   => "<h1>A non-safe choice",
            ])
        ;

        // @todo fixme using the new API
        return;
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

        // Test that markup is safe
        $this->assertSame('&lt;h1&gt;A non-safe choice', $render['#links']['unsafe']['title']);

        // 'a' and 'trout' being selected, click on 'a' removes 'a' and keeps 'trout'
        $this->assertSame('trout', $render['#links']['a']['query']['awesome_test_field']);
        // 'a' and 'trout' being selected, click on 'a' removes 'trout' and keeps 'a'
        $this->assertSame('a', $render['#links']['trout']['query']['awesome_test_field']);
        // 'a' and 'trout' being selected, click on '666' adds '666'
        $values = $render['#links']['666']['query']['awesome_test_field'];
        $values = explode(Filter::URL_VALUE_SEP, $values);
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

