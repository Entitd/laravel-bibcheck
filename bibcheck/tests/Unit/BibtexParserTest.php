<?php

namespace Tests\Unit;

use App\Services\Bibtex\GostValidator;
use App\Services\Bibtex\Parser;
use App\Services\ExternalApi\BibValidator;
use Tests\TestCase;
use App\Services\BibtexService;
use Illuminate\Foundation\Testing\RefreshDatabase;

use App\Services\ExternalApi\OpenAlexProvider;
class BibtexParserTest extends TestCase
{
    use RefreshDatabase;
    /** @test */
    public function test_it_identifies_missing_required_fields()
    {


      $aapi = new OpenAlexProvider();
    
      $targetTitle = 'Bibliography prettyprinting and syntax checking';
      $response = $aapi->findByTitle($targetTitle);
$bestMatch = $response['results'][0] ?? null;

$this->assertNotNull($bestMatch, "Источник не найден даже с учетом XPAC");
      
      // Проверяем, что заголовок совпадает достаточно сильно (например, > 80%)
      similar_text(mb_strtolower($targetTitle), mb_strtolower($bestMatch['title']), $percent);
      $this->assertGreaterThan(80, $percent, "Найденный заголовок '{$bestMatch['title']}' слишком отличается");



        // // Передаем статью БЕЗ автора (author - обязателен в твоем массиве)
        // $badBib = <<<PHP
        // %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%
        // @article{beebe1993bibliography,
        //   title={Bibliography prettyprinting and syntax checking},
        //   author={Beebe, Nelson HF},
        //   journal={TUGBoat},
        //   volume={14},
        //   number={4},
        //   pages={395--419},
        //   hyphenation = {english},
        //   year={1993}
        
        // }
        
        // @article{Frederick,
        //   author    = {Hensley, Merinda Kaye},
        //   title     = {Citation Management Software: Features and Futures},
        //   journal   = {Reference \& User Services Quarterly},
        //   volume    = {50},
        //   number    = {3},
        //   pages     = {204--208},
        //   year      = {2011},
        //   % url       = {https://journals.ala.org/index.php/rusq/article/download/3962/4448},
        // }
        
        // @manual{Oren_Patashnik,
        //   abstract = {This document is a systematic reference manual for the Biblatex package},
        //   organization = {Lehman, Philipp and Kime, Philipp and Boruvka, Audrey and Wright, Joseph},
        //   pagetotal       = {262},
        //   title = {The Biblatex Package},
        //   address     = {Berlin},
        // hyphenation = {english},
        //  %url = {http://ctan.mirrorcatalogs.com/macros/latex/contrib/biblatex/doc/biblatex.pdf},
        //   year = 2025
        // }
        
        // @article{Volnov,
        //   author      = {Вольнов, Е. В},
        //   title       = {Использование издательской системы Latex для оформления диссертаций},
        //   journal     = {Медицинский альманах},
        //   year        = {2022},
        //   volume      = {15},
        //   number      = {4},
        //   pages       = {1--15},
        //   hyphenation = {russian},
        //   %url        = {https://cyberleninka.ru/article/n/ispolzovanie-izdatelskoy-sistemy-latex-dlya-oformleniya-dissertatsiy/viewer}
        //   %про исподьзование latex
        // }
        // PHP;



        // $analysisResults = $service->fullCheck($badBib);

        // var_dump($analysisResults);
        // // Проверяем что парсер нашёл 2 записи
        // $this->assertCount(2, $analysisResults['entries']);
        // $this->assertArrayHasKey('GOST7052008', $analysisResults['entries']);
        // $this->assertArrayHasKey('logunova', $analysisResults['entries']);

        // // Проверяем метрики
        // $this->assertEquals(2, $analysisResults['aggregated_metrics']['totalQuantity']);

        // // Проверяем что у manual нет обязательного поля organization — ошибок не будет,
        // // а у article должны быть все обязательные поля
        // $logunovaErrors = $analysisResults['entries']['logunova']['gost_errors'];
        // $missingRequiredFields = array_filter($logunovaErrors, fn($e) => str_contains($e['message'], 'отсутствует обязательное поле'));
        // $this->assertEmpty($missingRequiredFields);

        // // Проверяем что manual без language получает рекомендацию
        // $manualErrors = $analysisResults['entries']['GOST7052008']['gost_errors'];
        // $hasLangRecommendation = array_filter($manualErrors, fn($e) => str_contains($e['message'], 'language'));
        // $this->assertNotEmpty($hasLangRecommendation);
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
