<?php

namespace Tests\Unit;

class KvpParserProvider
{
    /**
     * 
     */
    public static function parseLine()
    {
        return [
            'colon without spaces around' => [
                'line' => "key:value",
                'expected' => [
                    'key'=>'value'
                ]
            ],
            'space after colon' => [
                'line' => "key: value",
                'expected' => [
                    'key'=>'value'
                ]
            ],
            'space before and after colon' => [
                'line' => "key : value",
                'expected' => [
                    'key'=>'value'
                ]
            ],
            'multiple spaces around colon' => [
                'line' => "key    :     value",
                'expected' => [
                    'key'=>'value'
                ]
            ],
            'indented line' => [
                'line' => "     key  : value",
                'expected' => [
                    'key'=>'value'
                ]
            ],
            'no colon' => [
                'line' => "key",
                'expected' => [
                    'key'=>''
                ]
            ],
            'no colon' => [
                'line' => "key",
                'expected' => [
                    'key'=>''
                ]
            ],
            # expect the key to be the next numeric index
            'nothing before colon' => [
                'line' => ":value",
                'expected' => [
                    0 => 'value'
                ]
            ],
            'single colon' => [
                'line' => ":",
                'expected' => [
                    0 => ''
                ]
            ],
            'empty line' => [
                'line' => "",
                'expected' => []
            ],
            'multiple spaces' => [
                'line' => "      ",
                'expected' => []
            ],
            'multiple white characters' => [
                'line' => "    \t  ",
                'expected' => []
            ],
            'multiple colons in the line' => [
                'line' => "key: value with colon : inside",
                'expected' => [
                    'key' => 'value with colon : inside'
                ]
            ],
            'hash tag at the beginning of the line' => [
                'line' => "# some comment",
                'expected' => []
            ],
            'hash tag at the beginning of the line' => [
                'line' => "# some comment",
                'expected' => []
            ],
        ];
    }
}
