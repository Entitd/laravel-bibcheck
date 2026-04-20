<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\Bibtex\Parser;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SimpleParserTest extends TestCase
{
    use RefreshDatabase;
    /** @test */
    public function test_it_identifies_missing_required_fields()
    {

        $service = new Parser();

        // Передаем статью БЕЗ автора (author - обязателен в твоем массиве)
        $badBib = <<<PHP


        @manual{Oren_Patashnik,
          abstract = {This document is a systematic reference manual for the Biblatex package},
          organization = {Lehman, Philipp and Kime, Philipp and Boruvka, Audrey and Wright, Joseph},
          pagetotal       = 262
        }



        PHP;

        var_dump($service->analyze($badBib));

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
