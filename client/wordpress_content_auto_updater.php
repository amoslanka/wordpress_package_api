<?php

/**
 * Handles updating of a plugin or theme via api calls to a private wordpress content api and 
 * the right hooks at the right time into the wp admin system.
 *
 * @package WordpressContentAPI
 * @author Amos Lanka
 **/
class WordpressContentAutoUpdater
{
  
  /**
   * @var string The name of the content slug this instance handles.
   **/
  private $slug;

  /**
   * @var string The content type. Either 'themes' or 'plugins'.
   */
  private $type;
  
  /**
   * @var string The url of the api that will handle update requests.
   */
  private $api_url;

  /**
   * @param string $slug The name of the plugin this instance handles.
   * @author Amos Lanka
   */
  function __construct($type, $slug, $api_url) 
  {
    $this->slug = $slug;
    $this->type = $type;
    $this->api_url = $api_url; 

    if ($type != 'plugins' && $type != 'themes') {
      throw new Exception("Content type must be either 'plugins' or 'themes'", 1);      
    }

    // Add the hooks.
    if ($type == 'plugins') {
      // PLUGINS
      // Compares version numbers
      add_filter('pre_set_site_transient_update_plugins', array($this, 'check_and_filter'), 10, 3);
      // Requests plugin information
      add_filter('plugins_api', array($this, 'inject_plugin_information_filter'), 10, 3);
    } else {
      // THEMES
      add_filter('pre_set_site_transient_update_themes', array($this, 'check_and_filter'), 10, 3);
    }

  }

  // 
  // Actions
  // 

  /**
   * Generic request. Builds args, sends request, deserializes returned result.
   *
   * @param string $action 
   * @param array $body_args Any arguments that should be sent along in the request body
   * @param array $args Any arguments that should be sent along in the main request
   * @return void The parsed body result
   * @author Amos Lanka
   */
  private function request($action, $body_args=null, $args=null) 
  {
    if (!isset($body_args)) $body_args = array();
    if (!isset($args)) $args = array();
    $args = $this->build_request_args($action, array_merge_recursive($args, array('body' => $body_args)));

    $raw_response = wp_remote_post($this->api_url, $args);

    if (is_wp_error($raw_response)) {
      $response = new WP_Error('content_api_failed', __('An Unexpected HTTP Error occurred during the API request.</p> <p><a href="?" onclick="document.location.reload(); return false;">Try again</a>'), $request->get_error_message());
    } else {
      $response = json_decode($raw_response['body']);
      if ($response === false) {
        $response = new WP_Error('content_api_failed', __('An unknown error occurred'), $raw_response['body']);
      }
    }

    // error_dump($raw_response);

    return $response;
  }
  
  /**
   * Calls the "check" method on the api. 
   * The response format will look like this:
   *
   * response:
   *   version: 1.0.1
   *   date: [   timestamp   ]
   *   package: "[   zip url   ]"
   *   url: [   package homepage url   ]
   *
   * @param string $current_version The version to check against
   * @return void
   * @author Amos Lanka
   */
  public function check($current_version)
  {
    return $this->request('check', array('version' => $current_version));
  }
  
  /**
   * Calls the "latest" method on the api. Returns the detailed information about the latest
   * version of the plugin. The response format will look like this:
   *
   * response:
   *   version: 1.0.1
   *   date: [   timestamp   ]
   *   package: "[   zip url   ]"
   *   url: [   package homepage url   ]
   *
   * @return void
   * @author Amos Lanka
   */
  public function latest()
  {
    return $this->request('latest');
  }
  
  // 
  // Callbacks
  // 
  
  /**
   * Handles a filter hook from wordpress. Checks for updates to the plugin and inserts
   * the appropriate information into the $update_info array, passing it along back
   * to wordpress. 
   *
   * @param stdClass $update_info 
   * @return stdClass The modified $update_info object.
   * @author Amos Lanka
   */
  public function check_and_filter($update_info) 
  {
  	if (empty($update_info->checked)) return $update_info;
    
    $update_info_key = "{$this->slug}/{$this->slug}.php";
    
    // Run the check
    if (isset($update_info->checked[$update_info_key])) {
      $current_version = $update_info->checked[$update_info_key];  
    } else {
      $current_version = 0;
    }
    // $current_version = $update_info->checked[$update_info_key];  
    
    $response = $this->check($current_version); 
  	if (is_object($response) && !empty($response)) {
      $update_info->response[$update_info_key] = $response;
    }
  		
    // echo "<PRE>";
    // print_r($response);
    // print_r($update_info);
    // echo "</PRE>";

  	return $update_info;
  }
  
  /**
   * Filters plugins api calls by inserting plugin information requested from the private
   * api. 
   *
   * @param $def
   * @param $action
   * @param $args
   * @return 
   * @author Amos Lanka
   */
  public function inject_plugin_information_filter($def, $action, $args) 
  {
    if ($args->slug != "{$this->type}/{$this->slug}") return $def;
  
    // Get the current version
    $plugin_info = get_site_transient('update_plugins');
    $current_version = $plugin_info->checked[$this->slug .'/'. $this->slug .'.php'];
    $args->version = $current_version;
    $response = $this->request('show', array(
      'slug' => $args->slug,
      'version' => $args->version,
      'per_page' => $args->per_page
    ));

    // error_dump($args);
    // error_dump($response);
    
    return $response;
  }

  // 
  // Utilities / private methods
  // 
  
  /**
   * Builds request arguments for an api request. Performs a recursive merge
   * with any additional args passed in. 
   *
   * @param string $action
   * @param array $args Must be an associative array. An array of arguments that will be sent in the request. 
   * @return array The merged array.
   * @author Amos Lanka
   */
  private function build_request_args($action, $args) 
  {
    global $wp_version;

    $body = array(
      'action' => $action,
      'slug' => "{$this->type}/{$this->slug}",
      'api_key' => md5(get_bloginfo('url'))
    );

    // error_dump($args['body']);
    // error_dump($body);

    if (isset($args['body'])) {
      $body = array_merge($body, $args['body']);
      unset($args['body']);
    }

    // error_dump($body);
    // error_dump($response);
    
    $merged = array_merge(array(
      'body' => $body,
      'user-agent' => 'WordPress/' . $wp_version . '; ' . get_bloginfo('url')
    ), $args);

    // error_dump($merged);

    return $merged;
  }
  

} 


function error_dump($thing) 
{
  ob_start();
  var_dump($thing);
  $contents = ob_get_contents();
  ob_end_clean();
  error_log($contents);
}
function array_to_object($array = array()) 
{
  if (empty($array) || !is_array($array)) return false;
    
  $data = new stdClass;
  foreach ($array as $akey => $aval) {
    $data->{$akey} = $aval;
  }
  return $data;
}

?>