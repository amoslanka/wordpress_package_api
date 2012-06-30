<?php 

include 'config.php';
include 'api_controller.php';

class ArgumentException extends Exception {}
class RecordNotFoundException extends Exception {}

try {
  // Process API requests
  $api_controller = new APIController();
  if (!isset($_REQUEST['action'])) throw ArgumentException("No action provided");
  $action = strtolower($_REQUEST['action']);
  if (!$api_controller->has_action($action)) throw ArgumentException("Invalid action: $action");
  $api_controller->$action();
} catch (ArgumentException $e) {
  header( 'HTTP/1.1 400: BAD REQUEST' );
  print $e->getMessage();
} catch (RecordNotFoundException $e) {
	header( 'HTTP/1.1 404: NOT FOUND' );
  print $e->getMessage();
} catch (Exception $e) {	
  header( 'HTTP/1.1 500: INTERNAL SERVER ERROR' );
  print $e->getMessage();
}
die();

?>
