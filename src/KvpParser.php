<?php

namespace ArtKoder\KvpParser;

/**
 * A collection of static methods for parsing KVP files
 */
class KvpParser
{

    /**
     * Looks for files inside a directory and it's subdirectories and returns a list of paths
     * to the files. 
     * 
     * @param string $directory        The directory to to scan recursively
     * @param array $includeExtensions The extensions to include in the search
     * @return type
     */
    public static function parseRecursive(string $directory, array $includeExtensions = ['kvp'])
    {
        $paths = self::recursiveScandir($directory);
        $filteredPaths = self::filterPathsByExtension($paths, $includeExtensions);

        $result = [];
        foreach ($filteredPaths as $filePath) {
            $data = self::parseFile($filePath);
            $result = array_merge($result,$data);
        }

        return $result;
    }

    /**
     * Compiles a list of files inside the directory and all subdirectories
     * 
     * @param string $directory The directory to scan
     */
    public static function recursiveScandir(string $directory): array
    {
        $result = [];

        $files = scandir($directory);

        foreach ($files as $file)
        {
            if (in_array($file,array(".",".."))) {
                continue;
            }

            $filePath = realpath($directory . DIRECTORY_SEPARATOR . $file); 

            if (is_dir($filePath))
            {
                $dirsToAppend = self::recursiveScandir($filePath);
                $result = array_merge($result, $dirsToAppend);
            } else {
                $result[] = $filePath;
            } 

        }

        return $result;
    }


    /**
     * From a list of file paths, return only the ones with specific extensions 
     * 
     * @param array $filePaths          A list of file paths (strings)
     * @param array $includeExtensions  The filter will keep the files with an extension included in this array
     * @return type
     */
    public static function filterPathsByExtension(array $filePaths, array $includeExtensions = ['kvp'])
    {
        return array_filter($filePaths, function($filePath) use ($includeExtensions) {
            $pathParts = pathinfo($filePath);
            $extension = $pathParts['extension'];
            return in_array($extension, $includeExtensions, true);
        });
    }

    /**
     * Parses a single KVP file and returns an associative array where properties become the keys of the array
     * 
     * @param string $filePath  The path to the KVP file
     * @return array|false      An array with the parsed data or false if something goes wrong
     */
    public static function parseFile(string $filePath): array|false
    {
        if (!file_exists($filePath)) { return false; }

        $filePointer = fopen($filePath, 'r');

        if ($filePointer === false) { return false ; }

        $data = []; 
        $item = [];
        
        while (($line = fgets($filePointer)) !== false) {
            if (empty(trim($line)) && !empty($item)) {
                $data[] = $item;
                $item = [];
            } else {
                self::parseLine($line, $item);
            }
        }
            
        if (!empty($item)) {
            $data[] = $item;
        }

        fclose($filePointer);

        return $data;        
    }

    /**
     * Converts a CSV file to a KVP file. If the column map is provided
     * the key specifies the CSV column and the value is the name it will become in the KVP file
     * 
     * E.g. 
     *   first,last,age
     *   john,doe,35
     * 
     * $columnMap = ['First Name' => 'first', 'Last Name' => 'last', 'Age'  => 'age']
     * 
     * Will produce:
     *    First Name: john
     *    Lat Name: doe
     *    AgeL 35
     * 
     * @param string $fileIn   The path to the CSV file to read
     * @param string $fileOut  The path to the KVP file to write
     * @param array $columnMap The key specifies the property in the KVP file and the value is the CSV column
     */
    public static function csvToKvp(string $fileIn, string $fileOut, array $columnMap = [])
    {
        $lines = file($fileIn,  FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        $header = str_getcsv(array_shift($lines));
        
        $rows = array_map(function ($line) use ($header) {
            $values = str_getcsv($line);
            return array_combine($header, $values);
        }, $lines);
        
        $kdpContent = '';
                
        foreach ($rows as $rowData) {
            $kdpContent .= self::mapColumnsToProperties($rowData, $columnMap) . "\n";
        }
        file_put_contents($fileOut, $kdpContent);
    }
    
    public static function mapColumnsToProperties($rowData, $columnMap = [])
    {   
        $kvpContent = '';
        if (empty($columnMap)) {
            foreach ($rowData as $property => $value) {
                $kvpContent .= "$property: $value\n";
            }
        } else {
            foreach ($columnMap as $kvpProperty => $csvColumn) {
                if (is_callable($csvColumn)) {
                    $value = call_user_func($csvColumn, $rowData);
                } else {
                    $value = $rowData[$csvColumn] ?? $csvColumn;                
                }
                
                $kvpContent .= "$kvpProperty: $value\n";
            }
        }
        return $kvpContent;
    }
    
    public static function concatenateValues($rowData, $flippedHeader, $csvColumns)
    {
        $values = [];
        foreach($csvColumns as $csvColumn) {
            $index = $flippedHeader[$csvColumn] ?? -1;
            $values [] = $rowData[$index] ?? $csvColumn; 
        }
        return implode(' ', $values);
    }
    
    public static function parseLine($line, &$addToArray): void
    {
        $trimmedLine = trim($line);
        // If line is empty, do nothing
        if (empty($trimmedLine)) {
            return;
        }
        
        // Ignore comments starting with hash tag
        if (str_starts_with($trimmedLine, '#')) {
            return;
        }
        
        $substrings = explode(":", $trimmedLine, 2);
        $key = isset($substrings[0]) ? trim($substrings[0]) : '';
        $value = isset($substrings[1]) ? trim($substrings[1]) : '';
        $addToArray[$key] = $value;
    }
}