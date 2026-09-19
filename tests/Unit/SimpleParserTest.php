<?php

namespace Tests\Unit;

use App\Services\Bibtex\Parser;
use Tests\TestCase;

class SimpleParserTest extends TestCase
{
    /** @test */
    public function test_it_parses_inline_fields_and_nested_braces(): void
    {
        $parser = new Parser();
        $bib = <<<'BIB'
@article{beebe1993bibliography, title = {A {Very} Complex Title}, year = 1993}
BIB;

        $result = $parser->parse($bib);

        $this->assertCount(1, $result['entries']);
        $this->assertSame([], $result['error']);
        $this->assertSame('beebe1993bibliography', $result['entries'][0]->key);
        $this->assertSame('A {Very} Complex Title', $result['entries'][0]->getField('title'));
        $this->assertSame('1993', $result['entries'][0]->getField('year'));
    }
}
