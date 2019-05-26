<?php
/**
 * Created by PhpStorm.
 * User: laiconglin
 * Date: 2018/10/14
 * Time: 20:10
 */

/**
 * An example of a project-specific implementation.
 *
 * After registering this autoload function with SPL, the following line
 * would cause the function to attempt to load the \Foo\Bar\Baz\Qux class
 * from /path/to/project/src/Baz/Qux.php:
 *
 *      new \Foo\Bar\Baz\Qux;
 *
 * @param string $class The fully-qualified class name.
 * @return void
 */

$namespaceToDirHash = [
	'Library\\Dao\\' => __DIR__ . '/Dao/',
	'Library\\TwoDao\\' => __DIR__ . '/TwoDao/',
];

foreach ($namespaceToDirHash as $prefix => $baseDir) {
	spl_autoload_register(function ($class) use ($prefix, $baseDir){
		// project-specific namespace prefix
		// $prefix = 'Library\\Dao\\';

		// base directory for the namespace prefix
		// $base_dir = __DIR__ . '/Dao/';

		// does the class use the namespace prefix?
		$len = strlen($prefix);
		if (strncmp($prefix, $class, $len) !== 0) {
			// no, move to the next registered autoloader
			return;
		}

		// get the relative class name
		$relative_class = substr($class, $len);

		// replace the namespace prefix with the base directory, replace namespace
		// separators with directory separators in the relative class name, append
		// with .php
		$file = $baseDir . str_replace('\\', '/', $relative_class) . '.php';

		echo $file . PHP_EOL;

		// if the file exists, require it
		if (file_exists($file)) {
			require $file;
		}
	});
}
