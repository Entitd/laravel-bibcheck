<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\Bibtex\Parser;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ParserTest extends TestCase
{
    use RefreshDatabase;
    /** @test */
    public function test_it_identifies_missing_required_fields()
    {
        $service = new Parser();

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

                @article{Vlasova,
                  title={Российская наука в цифрах: 2023},
                  author={Власова, В.В. and Гохберг, Л.М. and Дитковский, К.А. and Коцемир, М.Н. and Мартынова, С.В. and Нестеренко, А.В. and Полякова, В.В. and Ратай, Т.В. and Сагиева, Г.С. and Стрельцова, Е.А. and Тарасенко, И.И. and Юдин, И.Б.},
                  journal={Нац. исслед. ун-т «Высшая школа экономики»},
                  number={200},
                  pages={48--200},
                  year={2023},
                %https://issek.hse.ru/mirror/pubs/share/822633163.pdf
                }

                @article{Chisnikov,
                  title={Unified modeling language (uml)},
                  author={Чисников, Павел Иванович},
                  journal={Статистика и экономика},
                  number={3},
                  pages={153--156},
                  year={2010},
                hyphenation = {russian},
                % https://cyberleninka.ru/article/n/unified-modeling-language-uml
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
