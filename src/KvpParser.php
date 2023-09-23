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
        if (!file_exists($filePath)) {
            return false;
        }

        $filePointer = fopen($filePath, 'r');

        if ($filePointer === false) {
            return false;
        }

        $data = []; 
        $item = [];
        
        while (($line = fgets($filePointer)) !== false) {
            $line = trim($line);

            // Ignore lines starting with # to allow comments
            if (substr($line, 0, 1) === "#") {
                continue;
            }
            

            if (empty($line) && !empty($item)) {
                $data[] = $item;
                $item = [];
            } else {
                list($key, $value) = explode(": ", $line, 2);
                $item[$key] = $value;
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
     * $columnMap = ['first' => 'First Name', 'last' => 'Last Name', 'age' => 'Age']
     * 
     * Will produce:
     *    First Name: john
     *    Lat Name: doe
     *    AgeL 35
     * 
     * @param string $fileIn   The path to the CSV file to read
     * @param string $fileOut  The path to the KVP file to write
     * @param array $columnMap The key specifies the CSV column and the value is the name it will become in the
     */
    public static function csvToKvp(string $fileIn, string $fileOut, array $columnMap = [])
    {
        $rows = array_map('str_getcsv', file($fileIn));
        $header = array_shift($rows);
        print_r($header);

        $kdpContent = '';
        foreach ($rows as $rowData) {
            // Skip empty lines including the last one
            if (empty($rowData[0])) {
                continue;
            }

            foreach($rowData as $index => $value) {
                if (empty($columnMap)) {
                    $key =  $header[$index];    
                } else {
                    // Skip values not included in the column map
                    if (!isset($columnMap[$header[$index]])) {
                        continue;
                    }
                    $key = $columnMap[$header[$index]];
                }

                $kdpContent .= "$key: $value\n"; 
            }
            $kdpContent .= "\n";
        }
        file_put_contents($fileOut, $kdpContent);
    }
}