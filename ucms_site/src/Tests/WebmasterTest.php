<?php

namespace MakinaCorpus\Ucms\Site\Tests;

use MakinaCorpus\Drupal\Sf\Container\Tests\AbstractDrupalTest;
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

        // Create a new foo site with a non existing user
        $site = new Site();
        $site->uid = 1;
        $site->title = 'Satan !';
        $site->title_admin = "Satan's site";
        $site->http_host = uniqid() . mt_rand() . '.example.com';
        $storage->save($site);

        if (!user_load(2)) {
            $account = new \stdClass();
            $account->name = uniqid() . mt_rand();
            $account->mail = $account->name . '@example.com';
            user_save($account);
        }
        if (!user_load(3)) {
            $account = new \stdClass();
            $account->name = uniqid() . mt_rand();
            $account->mail = $account->name . '@example.com';
            user_save($account);
        }

        // Ensures that upon site creation, event is raised and site now
        // has a new webmaster, which is our user
        $list = $access->listWebmasters($site);
        $this->assertCount(1, $list);
        $this->assertEquals(1, $list[0]->getUserId());
        $this->assertEquals($site->id, $list[0]->getSiteId());
        $this->assertEquals(Access::ROLE_WEBMASTER, $list[0]->getRole());

        // Add 2 users
        $access->addContributors($site, [2, 3]);
        $this->assertCount(1, $access->listWebmasters($site));
        $this->assertCount(2, $access->listContributors($site));
        $this->assertCount(3, $access->listAllUsers($site));

        $access->removeUsers($site, [1, 3]);
        $this->assertCount(0, $access->listWebmasters($site));
        $this->assertCount(1, $access->listContributors($site));
        $this->assertCount(1, $access->listAllUsers($site));

        $record = $access->listAllUsers($site)[0];
        $this->assertEquals(2, $record->getUserId());
        $this->assertEquals($site->id, $record->getSiteId());
        $this->assertEquals(Access::ROLE_CONTRIB, $record->getRole());

        $access->addWebmasters($site, 2);
        $record = $access->listAllUsers($site)[0];
        $this->assertEquals(2, $record->getUserId());
        $this->assertEquals($site->id, $record->getSiteId());
        $this->assertEquals(Access::ROLE_WEBMASTER, $record->getRole());
    }
}
