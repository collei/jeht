<?php


class UriRewriter
{
	private static $regexes = [
		'/([^/]+)/resources/(.*)' => '/resources/$1/$2',
	];

	private static $root = '';

	public static function setRoot(string $root)
	{
		self::$root = $root;
	}

	public static function rewrite(string $in)
	{
		foreach (self::$regexes as $from => $to) {
			[$reqq, $d_from, $d_to] = [
				$in,
				self::$root . $from,
				self::$root . $to
			];
			//
			if (preg_match("#{$d_from}#", $reqq, $material)) {
				$reqq = $d_to;
				foreach ($material as $n => $x) {
					$reqq = str_replace('$'.$n, $x, $reqq);
				}
				//
				return $reqq;
			}
		}
		//
		return false;
	}

}

function pathToRegex(string $path)
{
	if (preg_match_all('/\\{(\\w+)(\\?)?\\}/', $path, $matches, PREG_SET_ORDER)) {
		//
		$regexi = $path;
		//
		foreach ($matches as $set) {
			$optional = isset($set[2]);

			$piece = '(?P<'.$set[1].'>[^\\/]+)' . ($optional ? '?' : '');

			$regexi = str_replace($set[0], $piece, $regexi);
		}
		//
		return $regexi; //compact('regexi','matches');
	}
	//
	return $path;
}

$routes = [
	'/',
	'/home',
	'/home/users/{id}',
	'/sections/{section}/product/{productid}',
	'/search/{category}/{term?}',
];

$real_ones = [
	'/',
	'/home',
	'/home/users/1384',
	'/sections/cama-mesa-banho/product/27697',
	'/search/brinquedos/ferrorama',
];

$regexes = [];


function dd(...$any)
{
	echo '<fieldset><textarea style="width:100% !important;" rows="40">'.print_r($any,true).'</textarea></fieldset>';
}

foreach ($routes as $rout) {
	$regexy = '#' . pathToRegex($rout) . '#';
	//
	foreach ($real_ones as $uri) {
		$paramSet = [];
		//
		if (1 == preg_match($regexy, $uri, $params)) {
			$paramSet[$uri] = [];
			if (!empty($params)) foreach ($params as $k => $v) {
				if (is_string($k)) {
					$paramSet[$uri][$k] = $v;
				}
			}
			//
			$regexes[] = [
				'rout' => $rout,
				'regex' => $regexy,
				'matches' => $paramSet,
			];
		}
	}
}

dd($regexes);

$fileq = '.' . ($reqq = $_SERVER['REQUEST_URI']);

if (file_exists($fileq)) {
	http_redirect($fileq);
}

UriRewriter::setRoot($pathq = basename(dirname(__FILE__)));

$rewritten = UriRewriter::rewrite($reqq);

//dd(compact('pathq','fileq','reqq','rewritten'));

phpinfo();

