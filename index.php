<?php
  /*
   * I don't actually know how to program in PHP. This is just a simple example
   * that shows one way of doing simple GraphQL operations in PHP for those who'd
   * like a head start. This file likely has severe issues that a seasoned PHP
   * developer could spot right away.
   * ¯\_(ツ)_/¯
   *
   * In production, you'd ideally split this file into multiple parts, including:
   *   - resolvers/queries.php
   *   - resolvers/mutations.php
   *   - server/context/date.php
   *   - server/schema.graphql
   */

   // Don't forget to run `composer install`!
  require_once('vendor/autoload.php');
  require_once('./parse-csv.php');

  use function Digia\GraphQL\buildSchema;
  use function Digia\GraphQL\graphql;

  header('Content-Type: application/json');

  // GraphQL requires POST so we can filter out anything that's not POST
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Create a response object that looks like GraphQL's error so that the
    // same tools can interpret errors correctly
    $result = (object) new stdClass();
    $result->data = array();
    $result->errors = array();

    $error = (object) new stdClass();
    $error->message = 'Request method is not POST, but ' . $_SERVER['REQUEST_METHOD'];
    $result->errors[] = $error;

    print_r(json_encode($result));
    exit;
  }

  // Read the schema definition from file
  $source = file_get_contents(__DIR__ . '/schema.graphql');

  // Use the imported package to combine our schema with resolvers
  $schema = buildSchema($source, [
    'Query' => [
      // This resolver returns "Hello, world!", plus the date from the execution
      // context
      'hi' => function ($rootValue, $arguments, $context) {
        $result = 'Hello, world! It\'s ' . $context->date;
        
        return $result;
      },
      // This reverses the "input" variable, which is "String!" by the schema
      // definition
      'reverse' => function ($rootValue, $arguments) {
        $result = strrev($arguments['input']);

        return $result;
      },
      // Reads a hardcoded CSV file and parses it into a native object variable
      'csv' => function ($rootValue, $arguments) {
        // Mocked data from https://mockaroo.com
        $result = parse_csv('example-data.csv', ',', $arguments['limit']);

        return $result;
      }
    ],
  ]);

  // The executor accepts a root and a context object that sets up variables
  // for our resolvers.
  $root = (object) new stdClass();
  $context = (object) new stdClass();

  // Contents of the context object can depend on anything, on local state for
  // example. We'll just put the current time in ISO8601 string format.
  $now = new DateTime();
  $dateiso = $now->format(DateTime::ATOM);

  $context->date = $dateiso;

  // Get the POST request body as JSON and parse them into a PHP native variable
  $body = json_decode(file_get_contents('php://input'), true);
  
  // The body contains an object with "query" and "variables" keys
  $query = $body['query'];
  $variables = $body['variables'];

  // The executor requires either non-existent or an array-like variable, so
  // we pass variables to it if we have any.
  if ($variables) {
    $result = graphql($schema, $query, $root, $context, $variables);
  } else {
    $result = graphql($schema, $query, $root, $context);
  }

  // The executor creates a PHP native object, so we encode it to JSON and send
  // it off.
  print_r(json_encode($result));
?>
