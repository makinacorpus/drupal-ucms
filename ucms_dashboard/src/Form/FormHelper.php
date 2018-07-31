<?php

namespace MakinaCorpus\Ucms\Dashboard\Form;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Url;
use Drupal\Core\StringTranslation\TranslatableMarkup;

class FormHelper
{
    /**
     * Create a cancel url using destination parameter if set
     */
    public static function createCancelUrl(Url $default): Url
    {
        $request = \Drupal::request();
        $url = null;

        if ($request && $request->query->has('destination')) {
            $options = UrlHelper::parse($request->query->get('destination'));
            try {
                $url = Url::fromUserInput('/' . ltrim($options['path'], '/'), $options);
            } catch (\InvalidArgumentException $e) {
                $url = null;
            }
        }

        return $url ?? $default;
    }

    /**
     * Create a cancel link render array using destination parameter if set
     */
    public static function createCancelLink(Url $default, $text = null): array
    {
        return [
            '#type' => 'link',
            '#title' => $text ? $text : new TranslatableMarkup("Cancel"),
            '#attributes' => ['class' => ['button']],
            '#url' => self::createCancelUrl($default),
            '#cache' => [
                'contexts' => [
                    'url.query_args:destination',
                ],
            ],
        ];
    }
}
