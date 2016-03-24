<?php

namespace MakinaCorpus\Ucms\Site\Tests;

use MakinaCorpus\Drupal\Sf\Tests\AbstractDrupalTest;
use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\Site\SiteState;
use MakinaCorpus\Ucms\Site\SiteStorage;

class SiteAttributesTest extends AbstractDrupalTest
{
    private $site;

    protected function setUp()
    {
        parent::setUp();

        $site = new Site();
        $stupidHash = uniqid().mt_rand();
        $site->state = (int)SiteState::OFF;
        $site->title = $stupidHash;
        $site->title_admin = $stupidHash;
        $site->http_host = $stupidHash.'.example.com';

        $this->getStorage()->save($site);
        $this->site = $site;
    }

    protected function tearDown()
    {
        if ($this->site) {
            $this->getStorage()->delete($this->site);
        }

        parent::tearDown();
    }

    /**
     * @return SiteStorage
     */
    private function getStorage()
    {
        return $this->getDrupalContainer()->get('ucms_site.manager')->getStorage();
    }

    public function testSiteAttributes()
    {
        $site = $this->site;

        $this->assertFalse($site->hasAttribute('foo'));
        $this->assertFalse($site->hasAttribute('bar'));
        $this->assertFalse($site->hasAttribute('baz'));

        $site->setAttribute('foo', 'test');
        $site->setAttribute('bar', 12);
        $site->setAttribute('baz', ['roger' => 33]);

        $this->assertTrue($site->hasAttribute('foo'));
        $this->assertTrue($site->hasAttribute('bar'));
        $this->assertTrue($site->hasAttribute('baz'));
        $this->assertSame('test', $site->getAttribute('foo'));
        $this->assertSame(12, $site->getAttribute('bar'));
        $this->assertSame(['roger' => 33], $site->getAttribute('baz'));
        // and delete
        $site->deleteAttribute('bar');
        $this->assertFalse($site->hasAttribute('bar'));
        $all = $site->getAttributes();
        $this->assertArrayNotHasKey('bar', $all);
        // and ensure default behavior
        $this->assertNull($site->getAttribute('cassoulet'));
        $this->assertSame(666, $site->getAttribute('bernadette', 666));

        $this->getStorage()->save($site);

        // Force relod
        $site = $this->getStorage()->findOne($this->site->getId());

        $this->assertTrue($site->hasAttribute('foo'));
        $this->assertTrue($site->hasAttribute('baz'));
        $this->assertSame('test', $site->getAttribute('foo'));
        $this->assertSame(['roger' => 33], $site->getAttribute('baz'));
        // and delete
        $this->assertFalse($site->hasAttribute('bar'));
        $all = $site->getAttributes();
        $this->assertArrayNotHasKey('bar', $all);
        // and ensure default behavior
        $this->assertNull($site->getAttribute('cassoulet'));
        $this->assertSame(999, $site->getAttribute('bernadette', 999));
    }
}
