<?php

namespace Tests\Unit;

use App\Services\Bibtex\GostValidator;
use App\Services\Bibtex\Parser;
use App\Services\ExternalApi\BibValidator;
use Tests\TestCase;
use App\Services\BibtexService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BibtexParserTest extends TestCase
{
    use RefreshDatabase;
    /** @test */
    public function test_it_identifies_missing_required_fields()
    {

         $parser = new Parser();
         $gost = new GostValidator();
         $api = new BibValidator();

        $service = new BibtexService($parser, $gost, $api);

        // Передаем статью БЕЗ автора (author - обязателен в твоем массиве)
        $badBib = <<<PHP
                @manual{GOST7052008,
                  title = {ГОСТ 7.0.5-2008: Библиографическая ссылка. Общие требования и правила составления},
                  year = {2008},
                  organization = {Издательство стандартов},
                  address     = {Москва},
                  pagetotal = {22},
                  %url         = {https://www.ifap.ru/library/gost/7052008.pdf}
                }

                @article{logunova,
                  author      = {Логунова, О. С. and Ильина, Е. А. and Попов, С. Н. and Кочежинская, Ю. В. and Сибилева, Н. С.},
                  title       = {Структура программного модуля для обработки библиографической информации},
                  journal     = {Омский научный вестник},
                  year        = {2016},
                  volume      = {150},
                  number      = {6},
                  pages       = {158--164},
                  hyphenation = {russian},
                  %url         = {https://cyberleninka.ru/article/n/struktura-programmnogo-modulya-dlya-obrabotki-bibliograficheskoy-informatsii}
                 }
        PHP;

        $analysisResults = $service->fullCheck($badBib);


        echo "-------------------------";
//        print_r($result);
    }

//    /** @test */
//    public function test_it_calculates_metrics_correctly()
//    {
//        $service = new BibtexParserService();
//
//        $bibText = "
//            @article{test1,
//                author = {Ivanov}, title = {T1}, journal = {J1}, year = {2022},
//                pages = {1}, volume = {1}, number = {1}, hyphenation = {english}
//            }
//        ";
//
//        $result = $service->analyze($bibText);
//
//        // Проверяем метрики
//        $this->assertEquals(1, $result['aggregated_metrics']['totalQuantity']);
//        $this->assertEquals(1, $result['aggregated_metrics']['amountOfLiteratureInForeignLanguages']);
//    }
}
