<?php

namespace MakinaCorpus\Ucms\Site\Controller;

use Drupal\Core\Url;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Routing\LinkGeneratorTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use MakinaCorpus\Calista\Datasource\DatasourceInterface;
use MakinaCorpus\Calista\Query\InputDefinition;
use MakinaCorpus\Calista\Query\QueryFactory;
use MakinaCorpus\Calista\Twig\View\TwigView;
use MakinaCorpus\Calista\View\ViewDefinition;
use MakinaCorpus\Ucms\Dashboard\Controller\PageControllerTrait;
use MakinaCorpus\Ucms\Site\Site;
use MakinaCorpus\Ucms\Site\SiteManager;
use MakinaCorpus\Ucms\Site\SiteState;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use MakinaCorpus\Calista\Datasource\DatasourceInputDefinition;

class AdminController extends ControllerBase
{
    use LinkGeneratorTrait;
    use PageControllerTrait;
    use StringTranslationTrait;

    private $eventDispatcher;
    private $siteDatasource;
    private $siteManager;
    private $twig;
    private $webmasterDatasource;

    /**
     * {@inheritdoc}
     */
    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('event_dispatcher'),
            $container->get('twig'),
            $container->get('ucms_site.manager'),
            $container->get('ucms_site.datasource'),
            $container->get('ucms_site.webmaster_datasource')
        );
    }

    /**
     * Default constructor
     */
    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        \Twig_Environment $twig,
        SiteManager $siteManager,
        DatasourceInterface $siteDatasource,
        DatasourceInterface $webmasterDatasource
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->siteDatasource = $siteDatasource;
        $this->siteManager = $siteManager;
        $this->twig = $twig;
        $this->webmasterDatasource = $webmasterDatasource;
    }

    /**
     * Site list
     */
    public function siteList(Request $request)
    {
        $inputDefinition = new InputDefinition();
        $viewDefinition = new ViewDefinition([
            'templates' => [
                'default' => '@ucms_site/admin/site-list.html.twig'
            ]
        ]);

        $query = (new QueryFactory())->fromRequest($inputDefinition, $request);
        $items = $this->siteDatasource->getItems($query);

        $view = new TwigView($this->twig, $this->eventDispatcher);

        return [
            '#markup' => $view->render($viewDefinition, $items, $query),
        ];
    }

    /**
     * Webmaster list action
     */
    public function webmasterList(Request $request, Site $site)
    {
        $inputDefinition = new DatasourceInputDefinition($this->webmasterDatasource, [
            'base_query' => [
                'site_id' => $site->getId(),
            ],
        ]);
        $viewDefinition = new ViewDefinition([
            'templates' => [
                'default' => '@ucms_site/admin/webmaster-list.html.twig'
            ]
        ]);

        $query = (new QueryFactory())->fromRequest($inputDefinition, $request);
        $items = $this->webmasterDatasource->getItems($query);

        $view = new TwigView($this->twig, $this->eventDispatcher);

        return [
            '#markup' => $view->render($viewDefinition, $items, $query),
        ];
    }

    /**
     * Routing title callback
     */
    public function siteWebmasterListTitle(Site $site): string
    {
        return $this->t("@site webmasters", ['@site' => $site->getAdminTitle()]);
    }

    /**
     * Routing title callback
     */
    public function siteWebmasterAddTitle(Site $site): string
    {
        return $this->t("Add webmaster in @site", ['@site' => $site->getAdminTitle()]);
    }

    /**
     * Routing title callback
     */
    public function siteEditTitle(Site $site): string
    {
        return $site->getAdminTitle();
    }

    /**
     * Routing title callback
     */
    public function siteViewTitle(Site $site): string
    {
        return $site->getAdminTitle();
    }

    /**
     * View site details action
     */
    public function siteView(Site $site)
    {
        /** @var \Drupal\user\Entity\User $requester */
        $requester = $this->entityTypeManager()->getStorage('user')->load($site->getOwnerUserId());
        if (!$requester) {
            // $requester = drupal_anonymous_user();
        }

        $template = null;
        if ($site->template_id) {
            try {
                $template = $this->siteManager->getStorage()->findOne($site->getTemplateId());
                $template = (string)$this->l($template->getTitle(), 'admin/dashboard/site/' . $template->getId());
            } catch (\Exception $e) {
                $template = '<span class="text-muted>'.t("Template does not exist anymore").'</span>';
            }
        } else {
            $template = '';
        }

        $states = SiteState::getList();

        $uri = 'http://'.$site->getHostname();

        $table = $this
            ->createAdminTable('ucms_site_details', ['site' => $site])
            ->addHeader($this->t("Identification"))
            ->addRow($this->t("HTTP hostname"), (string)$this->l($uri, Url::fromUri($uri)))
            ->addRow($this->t("State"), $this->t($states[$site->getState()]))
            ->addRow($this->t("Title"), $site->getTitle())
            ->addRow($this->t("Created at"), \format_date($site->createdAt()->getTimestamp()))
            ->addRow($this->t("Lastest update"), \format_date($site->changedAt()->getTimestamp()))
            ->addRow($this->t("Requester"), $requester ? $requester->getDisplayName() : '')
            ->addHeader($this->t("Description"))
            ->addRow($this->t("Description"), \check_markup($site->getAdminTitle()))
            ->addRow($this->t("Replaces"), \check_markup($site->getReplacementOf()))
            ->addRow($this->t("HTTP redirections"), \check_markup($site->getHttpRedirects()))
            ->addHeader($this->t("Display information"))
            ->addRow($this->t("Theme"), $site->getTheme())
            ->addRow($this->t("Is a template"), $site->isTemplate() ? '<strong>' . $this->t("yes") . '</strong>' : $this->t("No"))
        ;

        if ($template) {
            $table->addRow($this->t("Site template"), $template);
        }

        $this->addArbitraryAttributesToTable($table, $site->getAttributes());

        return $table->render();
    }
}
