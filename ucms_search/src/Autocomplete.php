<?php

namespace MakinaCorpus\Ucms\Search;

use Elasticsearch\Client;
use Symfony\Component\HttpFoundation\JsonResponse;


class Autocomplete
{
    /**
     * @var \Elasticsearch\Client
     */
    protected $client;

    /**
     * @var IndexStorage
     */
    protected $storage;

    /**
     * @var string
     */
    protected $index;

    /**
     * @param \Elasticsearch\Client $client
     */
    public function __construct(Client $client, IndexStorage $storage)
    {
        $this->client = $client;
        $this->storage = $storage;
        $this->index = $this->storage->getIndexRealname('private');
    }

    /**
     * Execute the completion suggestion and return the response in JSON.
     *
     * @param string $text
     *   The text to apply the suggestion (completion)
     * @param string $field
     *   The completion field to use (define in ES mapping)
     * @param int $fuzziness
     *   Which fuzziness to apply. For example, with fuzziness of 1, the text
     *   'paage' will match with term 'page'.
     *   Default to none (0).
     * 
     * @return JsonResponse
     */
    public function execute($text, $fuzziness = 0, $field = 'autocomplete')
    {
        $params = [
          'index' => $this->index,
          'body' => [
            'autocomplete-suggest' => [
              'text' => $text,
              'completion' => [
                'field' => $field,
                'fuzzy' => [
                  'fuzziness' => $fuzziness,
                ],
              ],
            ],
          ],
        ];
        $result = $this->client->suggest($params);

        $suggestions = [];
        foreach ($result['autocomplete-suggest'][0]['options'] as $suggestion) {
            $suggestions[] = filter_xss($suggestion['text']);
        }

        return new JsonResponse($suggestions);
    }
}
