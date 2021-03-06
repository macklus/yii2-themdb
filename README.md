The Movie DB API Client for yii2
================================
The Movie DB API Client for yii2

Features
--------

* Implements most of The Movie DB API methods
* Respect API limits, and wait if you are near to overpass API limit
* Simple usage
* Simple download of related images
* Log info messages using Yii::info for you to know when sleep due to API limits

Installation
------------

The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
composer require --prefer-dist macklus/yii2-themdb "*"
```

or add

```
"macklus/yii2-themdb": "*"
```

to the require section of your `composer.json` file.


Usage
-----

Once the extension is installed, simply use it in your code by  :

```php
use macklus\themdb\Tmdbapi;

class SiteController extends Controller
{
    public function actionTest()
    {
        $tmdb = new Tmdbapi();
        $tmdb->api_key = '__INSERT_HERE_YOUR_API_KEY__';
        if ($tmdb->getTvSeason(46952, 3, ['language' => 'es'])) {
            print_R($tmdb->data);
        } else {
            echo 'error';
        }

		// Find something by his imdb_id
		$tmdb->findByExternal('imdb_id', 'tt0816692', ['language' => 'es']);

    }
}```
