<?php

namespace MakinaCorpus\Ucms\Site\Tests;

use MakinaCorpus\Drupal\Sf\Tests\AbstractDrupalTest;
use MakinaCorpus\Ucms\Site\SiteManager;
use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\Site\Access;

class WebmasterTest extends AbstractDrupalTest
{
    public function testNodeIndexerChain()
    {
        /* @var $manager SiteManager */
        $manager  = $this->getDrupalContainer()->get('ucms_site.manager');
        $storage  = $manager->getStorage();
        $access   = $manager->getAccess();

        $u1 = $this->createDrupalUser()->uid;
        $u2 = $this->createDrupalUser()->uid;
        $u3 = $this->createDrupalUser()->uid;

        // Create a new foo site with a non existing user
        $site = new Site();
        $site->uid = $u1;
        $site->title = 'Satan !';
        $site->title_admin = "Satan's site";
        $site->http_host = uniqid() . mt_rand() . '.example.com';
        $storage->save($site);

        // Ensures that upon site creation, event is raised and site now
        // has a new webmaster, which is our user
        $list = $access->listWebmasters($site);
        $this->assertCount(1, $list);
        $this->assertEquals($u1, $list[0]->getUserId());
        $this->assertEquals($site->id, $list[0]->getSiteId());
        $this->assertEquals(Access::ROLE_WEBMASTER, $list[0]->getRole());

        // Add 2 users
        $access->addContributors($site, [$u2, $u3]);
        $this->assertCount(1, $access->listWebmasters($site));
        $this->assertCount(2, $access->listContributors($site));
        $this->assertCount(3, $access->listAllUsers($site));

        $access->removeUsers($site, [$u1, $u3]);
        $this->assertCount(0, $access->listWebmasters($site));
        $this->assertCount(1, $access->listContributors($site));
        $this->assertCount(1, $access->listAllUsers($site));

        $record = $access->listAllUsers($site)[0];
        $this->assertEquals($u2, $record->getUserId());
        $this->assertEquals($site->id, $record->getSiteId());
        $this->assertEquals(Access::ROLE_CONTRIB, $record->getRole());

        $access->addWebmasters($site, $u2);
        $record = $access->listAllUsers($site)[0];
        $this->assertEquals($u2, $record->getUserId());
        $this->assertEquals($site->id, $record->getSiteId());
        $this->assertEquals(Access::ROLE_WEBMASTER, $record->getRole());
    }
}
