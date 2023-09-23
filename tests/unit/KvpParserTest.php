<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use ArtKoder\KvpParser\KvpParser;

/**
 * Description of KvpParserTest
 *
 * @author francisco
 */
class KvpParserTest extends TestCase 
{
    /**
     * 
     * @param type $line
     * @param type $expected
     * @dataProvider Tests\Unit\KvpParserProvider::parseLine
     */
    public function testParseLine($line, $expected)
    {
        $record = []; 
        KvpParser::parseLine($line, $record);
        $this->assertEqualsCanonicalizing($expected, $record);
    }
}
