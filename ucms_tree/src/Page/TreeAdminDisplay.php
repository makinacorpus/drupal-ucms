<?php
/**
 * TO BE REMOVED, KEPT BECAUSE I NEED TO MOVE SOME LOGIC OUT.
 */

namespace MakinaCorpus\Ucms\Tree\Page;

class TreeAdminDisplay
{
    /**
     * {@inheritdoc}
     */
    protected function displayAs($mode, $menus)
    {
        $rows = [];

        $allowedRoles = ucms_tree_role_list();

        /** @var \MakinaCorpus\Umenu\Menu[] $menus */
        foreach ($menus as $menu) {

            // I could use some preload there...
            try {
                $siteName = check_plain($this->siteManager->getStorage()->findOne($menu->getSiteId())->getAdminTitle());
                if ($menu->isSiteMain()) {
                    $siteMain = '<strong>' . $this->t("Yes") . '</strong>';
                } else {
                    $siteMain = $this->t("No");
                }
            } catch (\Exception $e) {
                // In theory, this can't happen...
                $siteName = '<em>' . $this->t("unknown") . '</em>';
                $siteMain = '<em>' . $this->t("N/A") . '</em>';
            }

            $role = $menu->getRole();
            if ($role) {
                if (isset($allowedRoles[$role])) {
                    $role = $allowedRoles[$role];
                }
            }

            $rows[] = [
                check_plain($menu->getId()),
                check_plain($menu->getName()),
                check_plain($menu->getTitle()),
                check_plain($menu->getDescription()),
                check_plain($siteName),
                check_plain($role),
                $siteMain,
                theme('calista_actions', ['actions' => $this->getActions($menu), 'mode' => 'icon']),
            ];
        }
    }
}
