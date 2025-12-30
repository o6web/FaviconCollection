# FaviconCollection



## Usage

```php
use o6web\FaviconCollection\Builder;

$builder = new Builder(new IcoConverter());

$builder->build($sourceFilePath, $backgroundColorHex, $gutterSpace, $arrayOfValidSizes, $shouldGenerateFaviconIco);
if ($builder->hasOutputFiles()) {
	$builder->zipOutputFiles($outputFilePath); // archive the output files to the specified zip
}
```

## Installation

Simply add a dependency on o6web/favicon-collection to your composer.json file if you use [Composer](https://getcomposer.org/) to manage the dependencies of your project:

```sh
composer require o6web/favicon-collection
```

Although it's recommended to use Composer, you can actually include the file(s) any way you want.


## License

FaviconCollection is [MIT](http://opensource.org/licenses/MIT) licensed.