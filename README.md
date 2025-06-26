# Get Pattern - Plugin
This is a plugin that contains a modularised version of the old get-pattern.php script. For more information on creating block patterns in the CMS head over to [confluence](https://pernod-ricard.atlassian.net/wiki/spaces/IR/pages/30790451221/Creating+child+pattern+blocks)

  
## Plugin Setup 
### Installation
This package is in satis, you simply add via composer.

```composer require totalonion/get-pattern```

### Updating 
Check latest version [here](https://github.com/TotalOnion/get-pattern/releases), run command bellow to update.
```composer update totalonion/get-pattern```

## Local Dev 
### Installation

I probably haven't cracked the best local dev method here, any input would be appreciated.

#### Step 1
Currently I have been adding a small snippet to the top of composer.json "repositories"

```
"repositories": [
	{
		"type": "path",
		"url": "../get-pattern", // change this to your plugin path
		"options": {
			"symlink": false <- setting this to true breaks lando
		}
	},
	...
```

> Note: When composer looks to install packages, it checks the sources
> from top to bottom so ensure that your "path" is at the top of the
> repositories object!

#### Step 2
Now within get-pattern repo, update the plugin version number both in the composer.json and the get-pattern.php. Once you have done this you can run:

remove satis version:

```rm -rf web/wp-content/plugins/get-pattern```

and then install local version:

``` composer update totalonion/get-pattern```

 You should now have a local version of the plugin running in your lando environment. You can easily check by checking the plugin version is matching up from within the dashboard.

#### Step 3
Since symlink is turned off you will have to fire the following command each time you update the plugin locally and want to test the changes in lando.

```rm -rf web/wp-content/plugins/get-pattern```

``` composer update totalonion/get-pattern```

### Debugging

There are lots of ways to debug the script, the easiest method I have found is using `error_log()`.

Once you run the script on your local envrionment, you can check the local lando php logs to see the results of your logs using: `lando logs -s appserver > lando-logs.txt`

If you run the `yarn create-new-child-block block-name 123` and run into an error on your lando, checking the php logs will give you a good idea of what failed and where!

### Updating Plugin

 Updating the plugin to make it available to all the lovely people requires a few steps

#### Step 1
In your update commit update the version number in both `composer.json` and `get-pattern.php`

#### Step 2
Commit your changes, and then publish a release in github with the version number.

#### Step 3
When the release is ready you need to head over to the satis repo and add the new version to the Json. 

[Satis repo here](https://github.com/theabsolutcompany/GCMS.Support/blob/php.pr-globalcms.com/satis/get-pattern.json)

Once that is complete everyone can install your release via composer.

## File Overview

 ```
 ┣ 📂inc
┣ ┣ 📂dom
┣ ┃ ┣ 📜Clean.php
┣ ┃ ┗ 📜Utils.php
┣ ┣ 📂transforms
┣ ┃ ┣ 📜Fields.php
┣ ┃ ┣ 📜Images.php
┣ ┃ ┣ 📜Links.php
┣ ┃ ┗ 📜Videos.php
┣ ┣ 📜AjaxHandler.php
┣ ┗ 📜Renderer.php
┣ 📜get-pattern.php
```

More documentation to follow...

Next steps:
	-	Splitting out the repeater logic from Fields.php
	-	Splitting out the post-info logic from Fields.php
