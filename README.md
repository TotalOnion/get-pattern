## Documentation coming soon...

for info on creating a block pattern [confluence here](https://pernod-ricard.atlassian.net/wiki/spaces/IR/pages/30790451221/Creating+child+pattern+blocks)


```composer require totalonion/get-pattern```


## Local dev


Add this to top of composer.json repositorys, repositrories go from top to bottom for specificty so make sure its above the statis entry.
```
"repositories": [
    {
        "type": "path",
        "url": "../get-pattern", // change this to your plugin path
        "options": {
            "symlink": false <- setting this to true breaks lando
        }
    },
```

In your local copy of the plugin bump your version in the composer.json and the get-pattern.php 

Once you have done this you can run 

``` composer update totalonion/get-pattern```

you will now have a local version of hte plugin in your sites wordpress. 

Since symlink is turned off you will have to fire the following command each time you update the plugin code!

There must be a better way please update this file if you find one.

```rm -rf web/wp-content/plugins/get-pattern```

``` composer update totalonion/get-pattern```


## debugging

`error_log()` <- this will print to php logs to view php logs `lando logs -s appserver > lando-logs.txt`