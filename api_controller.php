<?php

/**
 * @package worpress_package_api
 * @author Amos Lanka
 **/
class APIController
{

  var $packages;
  
  /**
   * Returns true if the controller exposes the provided action name as a public action.
   *
   * @param string $a 
   * @return void
   * @author Amos Lanka
   */
  public function has_action($a) 
  {
    return in_array($a, array('index', 'show', 'latest', 'check'));
  }
  
  private function find_package($slug)
  {
    if (!isset($slug)) throw new ArgumentException("No slug provided", 1);
    $package = array('versions' => array());

    // Find any release.json files
    foreach (glob(make_path(DOWNLOADS_ROOT_PATH, $slug, "**/release.json")) as $filepath) {
      $release_data = json_decode(file_get_contents($filepath), true);

      // echo '<pre>';
      // print_r($release_data);
      // echo '</pre>';

      $version = $release_data['version'];
      $date = $release_data['date'];

      if (isset($release_data['package'])) {
        $pkg = $release_data['package'];
      } elseif (isset($release_data['packages'])) {
        $pkg = $release_data['packages'][0];
      }

      if (isset($pkg)) {
        // $name = $pkg['name'];
        $zip = $pkg['zip'];

        if (!isset($package['versions'][$version])) {
          $package['versions'][$version] = array(
            'version' => $version,
            'date' => $date,
            'package' => absolute_download_url($zip)
          );
        }
      }
    }

    if (!isset($package)) throw new RecordNotFoundException("Package not found: $slug", 1);
    // echo '<pre>';
    // print_r($package);
    // echo '</pre>';
    return $package;
  }

  // 
  // Actions
  // 
  
  /**
   * Display all available packages
   *
   * @return void
   * @author Amos Lanka
   */
  public function index() 
  {
    header('Content-type: application/json');
    $list = glob(make_path(DOWNLOADS_ROOT_PATH, "*/*/*/release.json"));
    print json_encode($list);
  }
  
  /**
   * Display information about a specific package / version combo.
   * If a version is not provided, all versions are returned.
   *
   * @return void
   * @author Amos Lanka
   */
  public function show() 
  {
    extract($_REQUEST);

    // echo "<PRE>";
    // // print_r(array($def, $current_version, $this->plugin_slug, $action, $args));
    // print_r($_REQUEST);
    // echo "</PRE>";

    $package = $this->find_package($slug);
    
    if (isset($version)) {
      if (strtolower($version) == 'latest') {
        $this->latest();
        return;
      }

      if (!isset($package['versions'][$version])) throw new RecordNotFoundException("Package version not found: $version");
      $response = array_to_object($package['versions'][$version]);
      $response->last_updated = $response->date;
      unset($response->date);
      $response->download_link = $response->package;
      unset($response->package);
      
      // not sure why this has to happen? is it for wp ?
      // $data->sections = array('description' => '<h2>$_REQUEST</h2><small><pre>'.var_export($_REQUEST, true).'</pre></small>'
      //        . '<h2>Response</h2><small><pre>'.var_export($data, true).'</pre></small>'
      //        . '<h2>Packages</h2><small><pre>'.var_export($packages, true).'</pre></small>');
    	
    } else {
      $response = array_to_object($package);
    }
    
    header('Content-type: application/json');
    $response->slug = $slug;
    print json_encode($response);
  }
  
  /**
   * Check whether the provided slug/version combo is out of date.
   *
   * @return void
   * @author Amos Lanka
   */
  public function check() 
  {
    extract($_REQUEST);
    if (!isset($version)) throw new ArgumentException("No current version provided");
    
    $package = $this->find_package($slug);
    $response = array_to_object(array_pop($package['versions']));
    
    // Compare the versions
    if (version_compare($response->version, $version) > 0) {
  		$response->new_version = $response->version;
  		$response->version = $version;
    }

    header('Content-type: application/json');
    $response->slug = $slug;
    print json_encode($response);
  }
  
  /**
   * Returns the latest release information for a specific package.
   *
   * @return void
   * @author Amos Lanka
   */
  public function latest() 
  {
    extract($_REQUEST);
        
    $package = $this->find_package($slug);
    $response = array_to_object(array_pop($package['versions']));
    
    header('Content-type: application/json');
    print json_encode($response);
  }

  
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

function make_path($a=null,$b=null,$c=null,$d=null,$e=null,$f=null,$g=null,$h=null,$i=null,$j=null)
{
  $args = func_get_args();

  $args2 = array();

  foreach ($args as $arg) {
    if (!isset($arg)) next;
    $arg = preg_replace('/(^[\s\/\s]*)/', '', $arg);
    $arg = preg_replace('/([\s\/\s]*$)/', '', $arg);
    if ($arg != '') array_push($args2, $arg);
  }

  return implode('/', $args2);
}

function absolute_download_url($url)
{
  if (preg_match('/^http(s)?:\/\//', $url)) return $url;
  return make_path(DOWNLOADS_ROOT_URL, $url);
}




?>