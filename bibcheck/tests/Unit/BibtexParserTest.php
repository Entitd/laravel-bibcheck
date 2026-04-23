<?php

namespace Tests\Unit;

use App\Services\Bibtex\Parser;
use Tests\TestCase;

class BibtexParserTest extends TestCase
{
    /** @test */
    public function test_it_parses_last_bare_value_and_reports_duplicate_fields(): void
    {
        $parser = new Parser();
        $bib = <<<'BIB'
@manual{Oren_Patashnik,
  title = {The Biblatex Package},
  pagetotal = 262,
  pagetotal = 300
}
BIB;

        $result = $parser->parse($bib);

        $this->assertCount(1, $result['entries']);
        $this->assertSame('262', $result['entries'][0]->getField('pagetotal'));
        $this->assertCount(1, $result['error']);
        $this->assertStringContainsString("Поле 'pagetotal' объявлено повторно.", $result['error'][0]['message']);
    }
}
