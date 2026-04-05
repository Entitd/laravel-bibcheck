<?php

namespace App\Services\ExternalApi;

use Illuminate\Support\Facades\Http;

class OpenAlexProvider extends Provider{

    protected $client;

    public function __construct()
    {
        $this->client = Http::withoutVerifying()
            ->timeout(2)
            ->baseUrl(config('services.openalex.url'))
            ->withOptions([
                'query' => [
                    'api_key' => config('services.openalex.key'),
                ]
            ]);
    }

//    public function findByDoi($doi)
//    {
//        return $this->client->get("works/https://doi.org/{$doi}")->json();
//    }

    public function findByTitle($title)
    {
        $response = $this->client->get("works", [
            'filter' => "title.search:\"{$title}\""
        ])->json();

//        // Если результатов 0, сразу выходим
        if (($response['meta']['count'] ?? 0) === 0) {
            return null;
        }

        // Берем первый результат из массива
        $work = $response['results'][0];

        var_dump("123123123123123123");
        var_dump($title);
//        var_dump($work);
        // Формируем чистый массив только с нужными нам данными
        return [
//            'id'               => $work['id'],
//            'doi'              => $work['doi'] ?? null,
          'title'            => $work['title'],
//            'publication_year' => $work['publication_year'],
            // Собираем авторов в одну строку через запятую
            'authors'          => collect($work['authorships'])->map(function($auth) {
                return $auth['author']['display_name'];
            })->implode(', '),
            // Ссылка на саму статью (CyberLeninka в твоем случае)
//            'landing_page'     => $work['primary_location']['landing_page_url'] ?? null,
//            'is_xpac'          => $work['is_xpac'],
        ];
    }

}





