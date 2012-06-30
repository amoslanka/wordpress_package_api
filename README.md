Wordpress Package API
=====================

An API for providing private plugin and theme data to a Wordpress installation, because there are those of us who wish to keep our plugins off the public servers.

This module will act as a stand-alone server of data and files pertaining to Wordpress content that is intended to be kept private. In a way, it mimics [Wordpress's own plugins\_api](http://codex.wordpress.org/WordPress.org_API) and provides information about downloadable content. The most likely interaction with this type of data would be an installation of Wordpress plugins that need a private host for autoupdate functionality.

Combined with an updater class that communicates with this api, custom plugins and themes can autoupdate themselves using native Wordpress features. 

Best of all, this module is not intended to act as a data provider for just a single theme or plugin, but rather, for any number of themes and plugins added to it. It expects a particular downloadable directory structure in order to keep content types and packages seperate, but this structure is straightforward and should be easy to understand and maintain. Every released package must include a `release.json` file which contains information about that particular package and version. These json files are used to look up what files are available when a request is made to the server.

Content Files
-------------
The directory structure of content files, or at least of the `release.json` files is integral to the usability of the api. One `release.json` file represents each version of a particular theme or plugin, and files are grouped within a hierarchy of content type, content slug, and version directories. So for an example custom plugin named _herp-derp_, for which 3 versions have been released, the directory structure would look like this:

    - plugins
        - herp-derp
            - 1.0
                » release.json
                » herp-derp-1.0.zip
            - 1.1
                » release.json
                » herp-derp-1.1.zip
            - 1.2
                » release.json
                » herp-derp-1.2.zip

The themes directory would use the same exact structure, only replacing the top directory `plugins/` with `themes/`.

### release.json

The precise json data structure of `release.json` is also quite important to the representation of the data and files by the api. It provides a few extra details about a particular release. `release.json` files should be structured like so: 

    {
        "name":"herp-derp",
        "version":"1.0",
        "date":"2012-06-29 21:47:12 -0700",
        "package":
        {
            "zip":"http://api.example.com/wordpress/content/plugins/herp-derp/1.0/herp-derp-1.0.zip",
            "gz":"http://api.example.com/wordpress/content/plugins/herp-derp/1.0/herp-derp-1.0.zip.gz"
        }
    }

The api uses globbing to find the `release.json` files, and it limits the files globbed to only those applicable to a particular plugin or theme by globbing within the directory of, in the case of this example, `content/plugins/herp-derp`. As long as a `release.json` for each version that is to be made availalbe lives somewhere within this directory, it will be picked up and added to the list of available versions.

It is also important to note that the package zip urls provided in `release.json` can be relative urls, if that relativity is anchored to the root content directory. The API Setup section below will go into more detail about this. It is also important to note that the zip content files themselves can live anywhere on the web, so long as the urls reflect their fully qualified location. 

API setup
---------

Copy and paste, git clone, or ftp the files in this repo to a location accessible on the web. This type of endpoint url is recommendable:
```
http://api.example.com/wordpress
```
or
```
http://www.example.com/api/wordpress-content
```

Rename `config.sample.php` to `config.php` and edit its contents to provide paths to all the appropriate locations. The actual content files can live anywhere on the web, as long as their corresponding `release.json` files contain fully qualified urls and are organized on the same file system as the API. The API controller globs for `release.json` files within a particular path, as described by the _release.json_ section above. This path is defined by a constant, DOWNLOADS\_ROOT\_PATH, which is set in `config.php`. 

A related constant, DOWNLOADS\_ROOT\_URL, is the web address to the root directory from which all files are served by default. It is important to note, however, that in most cases this root url constant is irrelevant if all `release.json` files contain fully qualified urls. It is in place as a fallback and in case a user wishes to serve all content from the same relative location.

These two settings will typically coincide, particularly if the web hosting used is a simple shared host. In this case, if the root of the API code were to live on a file system at `http://api.example.com/wordpress` and have a corresponding content root directory at `http://api.example.com/wordpress/downloads`, the downloads directory would normally be a simple child directory of the Wordpress directory on that host file system. If the downloads path followed convention as well, its constant value would be set to `/downloads`, since a relative filesystem path will suffice on a module as simple as this.

API Methods
-----------

The API uses simple request params to determine its course of action. The most important param is the `action` param, which tells the controller which action to run. The `action` param is a required parameter on every request made to the API.

The following api methods are enabled:

**Index:**
Displays all available content names, based on globbable `release.json` files. Example: 

    http://api.example.com/wordpress/?action=index
    => [
        "packages/plugins/herp-derp/1.0/release.json",
        "packages/plugins/herp-derp/1.2/release.json",
        "packages/themes/foo/1.0/release.json",
        "packages/themes/foo/2.0/release.json"
      ]
  
**Show:** Displays all information about a specific package, but works in two modes. If a `version` param is provided, only information about that version is printed. If no version is provided, all version information will be printed. `slug`, the name of the package being requested is a required param. Examples: 

    http://api.example.com/wordpress/?action=show&slug=plugins/herp-derp
      => {
           slug: "plugins/herp-derp"
           versions: {
             1.0: {
               version: "1.0",
               date: "2012-05-11 21:47:44 -0700",
               package: "http://api.example.com/wordpress/packages/plugins/herp-derp/1.0/herp-derp-1.0.zip"
             },
             1.2: {
               version: "1.2",
               date: "2012-06-29 21:47:12 -0700",
               package: "http://api.example.com/wordpress/packages/plugins/herp-derp/1.0/herp-derp-1.0.zip"
            }
          },
      }
      
    http://api.example.com/wordpress/?action=show&slug=plugins/herp-derp&version=1.0
      => {
         version: "1.0",
         date: "2012-05-11 21:47:44 -0700",
         package: "http://api.example.com/wordpress/packages/plugins/herp-derp/1.0/herp-derp-1.0.zip"
       } 
    
    http://api.example.com/wordpress/?action=show&slug=plugins/herp-derp&version=latest
      => {
         version: "1.2",
         date: "2012-06-29 21:47:44 -0700",
         package: "http://api.example.com/wordpress/packages/plugins/herp-derp/1.2/herp-derp-1.2.zip"
       } 

**Latest:** Displays the version-specific information about the latest version of the plugin. `slug` param is required. Example: 

    http://api.example.com/wordpress/?action=latest&slug=plugins/herp-derp
    => {
         version: "1.2",
         date: "2012-06-29 21:47:44 -0700",
         package: "http://api.example.com/wordpress/packages/plugins/herp-derp/1.2/herp-derp-1.2.zip"
       } 

**Check:** Returns the information about the latest release of the requested `slug`, but adds a `new_version` attribute to the response if a newer version is available. `slug` and `version` params are required. Examples:

    http://api.example.com/wordpress/?action=latest&slug=plugins/herp-derp
      => {
           date: "2012-06-29 21:47:44 -0700",
           package: "http://api.example.com/wordpress/packages/plugins/herp-derp/1.2/herp-derp-1.2.zip",
           new_version: "1.2",
           slug: "plugins/herp-derp"
         } 
        

Plugin and Theme Updaters
-------------------------
todo

Authentication and Private Data
-------------------------------
todo

TODOs
-----
1. Fully support gzip responses
2. Support pulling release data in from web location (feed)
3. Herp the derp