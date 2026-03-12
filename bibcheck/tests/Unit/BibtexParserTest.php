<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\BibtexParserService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BibtexParserTest extends TestCase
{
    use RefreshDatabase;
    /** @test */
    public function test_it_identifies_missing_required_fields()
    {
        $service = new BibtexParserService();

        // Передаем статью БЕЗ автора (author - обязателен в твоем массиве)
        $badBib = "

            @article{bephy,
              title={фывфывф},
              author={Beebe, Nelson HF},
              journal={TUGBoat},
              volume={14},
              number={4},
              pages={395--419},
              hyphenation = { },
              year={1993}
            }
        ";

        $result = $service->analyze($badBib);

        echo "-------------------------";
        print_r($result);
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
