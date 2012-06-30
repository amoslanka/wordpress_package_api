<?php

// This file contains config settings necessary to run this api package. The 
// controllers will load a file called config.php. The best approach when setting
// up this api package is to duplicate this sample file, assign the proper 
// environment-specific variables to the following constants, and save the file
// as config.php.

// The root url where the api's files live. 
define('ROOT_URL', 'http://api.example.com/wordpress');

// The root url from which released packages will be downloaded.
// Expects to build the structure of the child directories as such:
// [DOWNLOADS_ROOT_URL]/
//	  [type (themes or plugins)]
//       [package-name]/
//          [version]
// 				contents.json
//				[various files available, including .zip and .gz for download]
// 
define('DOWNLOADS_ROOT_URL', 'http://api.example.com/wordpress/packages');
define('DOWNLOADS_ROOT_PATH', 'packages');

?>