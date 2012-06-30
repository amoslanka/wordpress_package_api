<?php 

include 'config.php';
include 'api_controller.php';

class ArgumentException extends Exception {}
class RecordNotFoundException extends Exception {}

// Process API requests
$api_controller = new APIController();
if (!isset($_REQUEST['action'])) $api_controller->invalid_request("No action provided");
$action = strtolower($_REQUEST['action']);
if (!$api_controller->has_action($action)) $api_controller->invalid_request("Invalid action: $action");
try {
  $api_controller->$action();
} catch (ArgumentException $e) {
  header( 'HTTP/1.1 400: BAD REQUEST' );
  print $e->getMessage();
  die();
} catch (RecordNotFoundException $e) {
	header( 'HTTP/1.1 404: NOT FOUND' );
  print $e->getMessage();
  die();
} catch (Exception $e) {	
  header( 'HTTP/1.1 500: INTERNAL SERVER ERROR' );
  print $e->getMessage();
  die();
}
die();

?>
