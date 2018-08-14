<?php
  // Source: https://stackoverflow.com/a/45458776
  // Modified so it can accept a limit and it outputs native variables
  // License: https://creativecommons.org/licenses/by-sa/3.0/

  function parse_csv($file, $delimiter, $limit) {
    if (($handle = fopen($file, "r")) === false) {
      die("can't open the file.");
    }

    $csv_headers = fgetcsv($handle, 4000, $delimiter);
    $csv = array();
    
    $i = 0;
    
    if (!$limit) {
      $limit = 1000;
    }

    while (($row = fgetcsv($handle, 4000, $delimiter)) && ($i < $limit)) {
      $csv[] = array_combine($csv_headers, $row);
      $i++;
    }

    fclose($handle);
    return $csv;
  }
?>
