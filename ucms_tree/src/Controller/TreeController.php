<?php

namespace MakinaCorpus\Ucms\Tree\Controller;

use MakinaCorpus\Drupal\Sf\Controller;
use MakinaCorpus\Ucms\Contrib\TypeHandler;

use Symfony\Component\HttpFoundation\Request;

class TreeController extends Controller
{
    /**
     * @return TypeHandler
     */
    private function getTypeHandler()
    {
        return $this->get('ucms_contrib.type_handler');
    }

    /**
     * Provides minidialog for creating content at a specific position
     *
     * @param Request $request
     * @return array
     */
    public function addContentHere(Request $request)
    {
        $links = [];
        $handler = $this->getTypeHandler();

        foreach (
            $this
                ->getTypeHandler()
                ->getTypesAsHumanReadableList($handler->getContentTypes())
            as $type => $name
        ) {
            if (node_access('create', $type)) {
                $options = [
                    'query' => [
                        'destination' => $request->get('destination'),
                        'menu'        => $request->get('menu'),
                        'parent'      => $request->get('parent'),
                        'position'    => $request->get('position'),
                    ],
                ];
                $links[] = l($name, 'node/add/'.strtr($type, '_', '-'), $options);
            }
        }

        return [
            '#theme' => 'item_list',
            '#items' => $links,
        ];
    }
}
