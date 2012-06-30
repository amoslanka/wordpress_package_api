<?php

// The root url where the api's files live. 
define('API_URL', 'http://api.example.com/wordpress');

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