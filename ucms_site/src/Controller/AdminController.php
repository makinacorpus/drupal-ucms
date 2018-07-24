<?php

namespace MakinaCorpus\Ucms\Site\Controller;

use Drupal\Core\StringTranslation\StringTranslationTrait;

use MakinaCorpus\Drupal\Sf\Controller;
use MakinaCorpus\Ucms\Dashboard\Controller\PageControllerTrait;
use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\Site\SiteState;
use MakinaCorpus\Ucms\Site\SiteManager;

class DashboardController extends Controller
{
    use PageControllerTrait;
    use StringTranslationTrait;

    /**
     * Get site manager
     *
     * @return SiteManager
     */
    private function getSiteManager()
    {
        return $this->get('ucms_site.manager');
    }

    /**
     * View site details action
     */
    public function viewAction(Site $site)
    {
        $requester = user_load($site->uid);
        if (!$requester) {
            $requester = drupal_anonymous_user();
        }

        $template = null;
        if ($site->template_id) {
            try {
                $template = $this->getSiteManager()->getStorage()->findOne($site->template_id);
                $template = l($template->title, 'admin/dashboard/site/' . $template->id);
            } catch (\Exception $e) {
                $template = '<span class="text-muted>' . t("Template does not exist anymore") . '</span>';
            }
        } else {
            $template = '';
        }

        $states = SiteState::getList();

        $uri = 'http://' . $site->http_host;

        $table = $this->createAdminTable('ucms_site_details', ['site' => $site])
            ->addHeader(t("Identification"))
            ->addRow(t("HTTP hostname"), l($uri, $uri))
            ->addRow(t("State"), t($states[$site->state]))
            ->addRow(t("Title"), check_plain($site->title))
            ->addRow(t("Created at"), format_date($site->ts_created->getTimestamp()))
            ->addRow(t("Lastest update"), format_date($site->ts_changed->getTimestamp()))
            ->addRow(t("Requester"), check_plain(format_username($requester)))
            ->addHeader(t("Description"))
            ->addRow(t("Description"), check_markup($site->title_admin))
            ->addRow(t("Replaces"), check_markup($site->replacement_of))
            ->addRow(t("HTTP redirections"), check_markup($site->http_redirects))
            ->addHeader(t("Display information"))
            ->addRow(t("Theme"), check_plain($site->theme))
            ->addRow(t("Is a template"), $site->is_template ? '<strong>' . t("yes") . '</strong>' : t("No"))
        ;

        if ($template) {
            $table->addRow(t("Site template"), $template);
        }

        $this->addArbitraryAttributesToTable($table, $site->getAttributes());

        return $table->render();
    }
}
