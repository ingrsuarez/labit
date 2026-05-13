<?php

namespace Tests\Unit;

use App\Services\SantaCruz\SantaCruzXmlParser;
use Tests\TestCase;

class SantaCruzXmlParserTest extends TestCase
{
    public function test_parse_sample_fixture(): void
    {
        $xml = file_get_contents(base_path('tests/Fixtures/santacruz/sample.xml'));
        $parser = new SantaCruzXmlParser;
        $data = $parser->parse($xml);

        $this->assertSame('24347908', $data['document_number']);
        $this->assertSame('222335', $data['accession_number']);
        $this->assertCount(1, $data['practicas']);
        $this->assertSame('0682', $data['practicas'][0]['prestacion_code']);
        $this->assertSame('Hidroxipireno ART', $data['practicas'][0]['prestacion_name']);
        $this->assertSame('m', $parser->mapSex($data['sex_raw']));
        $this->assertSame('1974-11-13', $parser->birthDate($data)?->toDateString());
    }
}
