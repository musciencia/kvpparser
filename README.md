# KVP Parser
A library to read and parse files with key value pairs.

## What is a KVP file
A KVP file consists of records of key value pairs separated by empty lines.

Here is an example:

```kdp
date: 2023-01-01
description: Electric company example Inc.
category: Electricity
amount: 120

date: 2023-02-01
description: Real estate example Inc.
category: Rent
amount: 1500
```

Here is a differnt example with a comment. Lines starting with `#` are comments ignored by the parser.

```kdp
# Budget report routes
name: budget-report
path: /budget/{start}/{end}
method: get
controller: ArtKoder\Peasy\Controllers\Budget::report

name: budget-index
path: /budget
method: get
controller: ArtKoder\Peasy\Controllers\Budget::index
```

I started using this type of file in different projects because it's similar to yaml but without
the nesting and it's parsing complexity. So I decided to build a library to make the code
reusable.

## Installation

### Using composer

```shell
composer require artkoder/kvpparser
```

## How to use this library

Here are some examples:

### Parse a single file

Supose we have the following file named `budget.kvp`:

```
date: 2023-01-01
description: some description
category: some category
amount: 1000

date: 2023-01-02
description: another description
category: another category
amount: 2000

date: 2023-01-03
description: one more description
category: one more category
amount: 3000
```

We can parse the file and receive an associative array by running the following code:

```php
$data = KvpParser::parseFile(__DIR__ . '/../data/budget.kvp');
print_r($data);
```

Here is the output:

```shell
Array
(
    [0] => Array
        (
            [date] => 2023-01-01
            [description] => some description
            [category] => some category
            [amount] => 1000
        )

    [1] => Array
        (
            [date] => 2023-01-02
            [description] => another description
            [category] => another category
            [amount] => 2000
        )

    [2] => Array
        (
            [date] => 2023-01-03
            [description] => one more description
            [category] => one more category
            [amount] => 3000
        )

)
```

### Parse multiple files inside a directory recursively

You can specify a directory. KvpParser will parse all the files found in that 
directory and subdirectories.

```php
$directory = '/path/to/your/data';
$data = KvpParser::parseRecursive($directory);
```

By default KvpParser will look for files with extension `.kvp`. But you can specify
any file extensions. For example:

```php
$directory = '/path/to/your/data';
$includExtension = ['kvp', 'dat'];
$data = KvpParser::parseRecursive($directory, $includeExtensions);
```

### Convert a CSV file to a KVP file

This is very convenient. Suppose that you have
a csv file with the following data from your bank statement:

```csv
"Account Type","Account Number","Transaction Date","Cheque Number","Description 1","Description 2","CAD$","USD$"
Chequing,123-4567,6/12/2023,,"C - IDP REFUND-0819","COSTCO WHOLESAL ",183.95,
Chequing,123-4567,6/12/2023,,"COSTCO WHOLESAL","IDP PURCHASE - 0746 ",-276.94,
Chequing,123-4567,6/13/2023,,"AUTOMOBILE RENT","TOYOTA FINANCE ",-129.12,
```

Let's say that we are only interested in the following data: Date, Description 1 and CAD$.
But we also want to change the column names in our resultin `.kdp` file.

Here is what we can do:

```php
$csvFile = __DIR__ . '/../data/transactions.csv';
$kdpFile = __DIR__ . '/../data/transactions.kdp';
$columnMap = [
    "Date" => "Transaction Date", 
    "Description" => "Description 1",
    "Amount" => "CAD$"
];
KvpParser::csvToKvp($csvFile, $kdpFile, $columnMap);
```

Here are the contents of `transactions.kdp`:

```
Date: 6/12/2023
Description: C - IDP REFUND-0819
Amount: 183.95

Date: 6/12/2023
Description: COSTCO WHOLESAL
Amount: -276.94

Date: 6/13/2023
Description: AUTOMOBILE RENT
Amount: -129.12
```

If no `$columnMap` is specified, the column names in the `csv` file will 
become the name of the properties in the `kvp` file.

Some times you need to do some processing of data during mapping, we can
do that adding closures to the map array.

In our previous example, suppose you want ot combine both description columns
and format date to be `Y-m-d`.

Here is how you would do it:

```php
function formatDate($data) {
    $inputDate = $data['Transaction Date'];
    $inputFormat = 'm/d/Y';
    $outputFormat = 'Y-m-d';
    // Create a DateTime object from the input date with the specified format
    $dateTime = DateTime::createFromFormat($inputFormat, $inputDate);

    // Check for errors in parsing the date
    if ($dateTime === false) {
        return false; // Return false if the parsing fails
    }

    // Format the DateTime object in the desired output format
    $formattedDate = $dateTime->format($outputFormat);

    return $formattedDate;
}

$columnMap = [
    "Date" => 'formatDate',
    "Account" => "Account Number",
    "Description" => function($data) { return $data['Description 1'] . " - " . $data['Description 2']; },
    "Amount" => "CAD$",
    "Category" => "uncategorized"];

KvpParser::csvToKvp($csvFile, $kdpFile, $columnMap);
```

Notice also that the column `uncategorized` does not exist. In this case
the word `uncategorized` would be use as the value.