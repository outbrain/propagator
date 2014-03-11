<?php
/**
 *
 * Global helper functions for the weatherstation app
 * @author Gavin Towey <gavin@box.net>
 * @license Apache 2.0 license.  See LICENSE document for more info
 * @created 2012-01-01
 */

// @todo validation?
/**
 * search global request variables $_POST and $_GET in that order and return
 * the first defined value for the given key
 *
 * @param string $name        	
 * @return mixed the value of the variable name (if any) or null.
 */
function get_var($name) {
	$sources = array (
			$_POST,
			$_GET 
	);
	foreach ( $sources as $s ) {
		if (isset($s [$name])) {
			return $s [$name];
		}
	}
	return null;
}

/**
 * return the full URL for the base page of the site.
 *
 * @return string
 */
function site_url() {
	if(in_array(php_sapi_name(), array('cli', 'cgi-fcgi'))) {
		return "./index.php";
	}
	
	if (isset($_SERVER ['HTTPS'])) {
		$proto = 'https://';
	} else {
		$proto = 'http://';
	}
	if (!array_key_exists('HTTP_HOST', $_SERVER)) {
		$_SERVER['HTTP_HOST'] = "/";
	}
	return $proto . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'];
}




/**
 * Takes an associative array in the layout of parse_url, and constructs a URL from it
 *
 * see http://www.php.net/manual/en/function.http-build-url.php#96335
 *
 * @param   mixed   (Part(s) of) an URL in form of a string or associative array like parse_url() returns
 * @param   mixed   Same as the first argument
 * @param   int     A bitmask of binary or'ed HTTP_URL constants (Optional)HTTP_URL_REPLACE is the default
 * @param   array   If set, it will be filled with the parts of the composed url like parse_url() would return
 *
 * @return  string  constructed URL
 */
// Based on https://github.com/fuel/core/blob/974281dde67345ca8d7cfa27bcf4aa55c984d48e/base.php#L248
// Bug http://stackoverflow.com/questions/7751679/php-http-build-url-and-pecl-install/7753154#comment11239561_7753154
if (!function_exists('http_build_url'))
{
	define('HTTP_URL_REPLACE', 1);				// Replace every part of the first URL when there's one of the second URL
	define('HTTP_URL_JOIN_PATH', 2);			// Join relative paths
	define('HTTP_URL_JOIN_QUERY', 4);			// Join query strings
	define('HTTP_URL_STRIP_USER', 8);			// Strip any user authentication information
	define('HTTP_URL_STRIP_PASS', 16);			// Strip any password authentication information
	define('HTTP_URL_STRIP_AUTH', 32);			// Strip any authentication information
	define('HTTP_URL_STRIP_PORT', 64);			// Strip explicit port numbers
	define('HTTP_URL_STRIP_PATH', 128);			// Strip complete path
	define('HTTP_URL_STRIP_QUERY', 256);		// Strip query string
	define('HTTP_URL_STRIP_FRAGMENT', 512);		// Strip any fragments (#identifier)
	define('HTTP_URL_STRIP_ALL', 1024);			// Strip anything but scheme and host

	function http_build_url($url, $parts = array(), $flags = HTTP_URL_REPLACE, &$new_url = false)
	{
		$keys = array('user','pass','port','path','query','fragment');

		// HTTP_URL_STRIP_ALL becomes all the HTTP_URL_STRIP_Xs
		if ($flags & HTTP_URL_STRIP_ALL)
		{
			$flags |= HTTP_URL_STRIP_USER;
			$flags |= HTTP_URL_STRIP_PASS;
			$flags |= HTTP_URL_STRIP_PORT;
			$flags |= HTTP_URL_STRIP_PATH;
			$flags |= HTTP_URL_STRIP_QUERY;
			$flags |= HTTP_URL_STRIP_FRAGMENT;
		}
		// HTTP_URL_STRIP_AUTH becomes HTTP_URL_STRIP_USER and HTTP_URL_STRIP_PASS
		else if ($flags & HTTP_URL_STRIP_AUTH)
		{
			$flags |= HTTP_URL_STRIP_USER;
			$flags |= HTTP_URL_STRIP_PASS;
		}

		// parse the current URL
		//$current_url = parse_url(current_url());

		// parse the original URL
		$parse_url = is_array($url) ? $url : parse_url($url);

		// make sure we always have a scheme, host and path
		//empty($parse_url['scheme']) and $parse_url['scheme'] = $current_url['scheme'];
		//empty($parse_url['host']) and $parse_url['host'] = $current_url['host'];
		isset($parse_url['path']) or $parse_url['path'] = '';

		// make the path absolute if needed
		if ( ! empty($parse_url['path']) and substr($parse_url['path'], 0, 1) != '/')
		{
			$parse_url['path'] = '/'.$parse_url['path'];
		}

		// scheme and host are always replaced
		isset($parts['scheme']) and $parse_url['scheme'] = $parts['scheme'];
		isset($parts['host']) and $parse_url['host'] = $parts['host'];

		// replace the original URL with it's new parts (if applicable)
		if ($flags & HTTP_URL_REPLACE)
		{
			foreach ($keys as $key)
			{
				if (isset($parts[$key]))
					$parse_url[$key] = $parts[$key];
			}
		}
		else
		{
			// join the original URL path with the new path
			if (isset($parts['path']) && ($flags & HTTP_URL_JOIN_PATH))
			{
				if (isset($parse_url['path']))
					$parse_url['path'] = rtrim(str_replace(basename($parse_url['path']), '', $parse_url['path']), '/') . '/' . ltrim($parts['path'], '/');
				else
					$parse_url['path'] = $parts['path'];
			}

			// join the original query string with the new query string
			if (isset($parts['query']) && ($flags & HTTP_URL_JOIN_QUERY))
			{
				if (isset($parse_url['query']))
					$parse_url['query'] .= '&' . $parts['query'];
				else
					$parse_url['query'] = $parts['query'];
			}
		}

		// strips all the applicable sections of the URL
		// note: scheme and host are never stripped
		foreach ($keys as $key)
		{
			if ($flags & (int)constant('HTTP_URL_STRIP_' . strtoupper($key)))
				unset($parse_url[$key]);
		}


		$new_url = $parse_url;

		return
		((isset($parse_url['scheme'])) ? $parse_url['scheme'] . '://' : '')
		.((isset($parse_url['user'])) ? $parse_url['user'] . ((isset($parse_url['pass'])) ? ':' . $parse_url['pass'] : '') .'@' : '')
		.((isset($parse_url['host'])) ? $parse_url['host'] : '')
		.((isset($parse_url['port'])) ? ':' . $parse_url['port'] : '')
		.((isset($parse_url['path'])) ? $parse_url['path'] : '')
		.((isset($parse_url['query'])) ? '?' . $parse_url['query'] : '')
		.((isset($parse_url['fragment'])) ? '#' . $parse_url['fragment'] : '')
		;
	}
}

/**
 * wrap html pre tags around the given string, with class="prettyprint"
 *
 * @param string $string        	
 */
function prettyprint($string) {
	print '<pre class="prettyprint">';
	print $string;
	print "</pre>";
}


function split_text($input_text, $delimiters, $quoting_characters, $trim_tokens = true) {

	$token_start_pos = 0;
	$result = array();
	$terminating_quote_pos = 0;
	$terminating_quote_found = false;
	$current_char = '';
	$current_pos = 0;
	while ($current_pos <= strlen($input_text)) {
		if ($current_pos == strlen($input_text)) {
			// make sure a delimiter "exists" at the end of input_text, so as to gracefully parse
			// the last token in list.
			$current_char = $delimiters[0];
		} else {
			$current_char = $input_text[$current_pos];
		}
		if (in_array($current_char, $quoting_characters)) {
			// going into string state: search for terminating quote.
			$quoting_char = $current_char;
			$terminating_quote_found = false;
			while (!$terminating_quote_found) {
				$terminating_quote_pos = strpos($input_text, $quoting_char, $current_pos + 1);
				if ($terminating_quote_pos === false) {
				// This is an error: non-terminated string!
					return null;
				}
				if ($terminating_quote_pos == $current_pos + 1) {
					// an empty text
					$terminating_quote_found = true;
				} else {
					// We've gone some distance to find a possible terminating character. Is it really terminating,
			        // or is it escaped?
			        $terminating_quote_escape_char = $input_text[$terminating_quote_pos - 1];
					if (($terminating_quote_escape_char == $quoting_char) || ($terminating_quote_escape_char == '\\')) {
			            // This isn't really a quote end: the quote is escaped.
			            // We do nothing; just a trivial assignment.
			            $terminating_quote_found = false;
					} else {
			            $terminating_quote_found = true;
					}
				}
		        $current_pos = $terminating_quote_pos;
			}
		} elseif (in_array($current_char, $delimiters)) {
			// Found a delimiter (outside of quotes).
			$current_token = substr($input_text, $token_start_pos, $current_pos - $token_start_pos);
			if ($trim_tokens) {
		    	$current_token = trim($current_token);
			}
			$result[] = $current_token;
			$token_start_pos = $current_pos + 1;
		}
		$current_pos = $current_pos + 1;
	}
	return $result;
}



function parse_script_queries($sql) {
	$queries = split_text($sql, array(';'), array("'", "`"));
	$queries = array_map("trim", $queries);
	$queries = array_filter($queries);
	$queries = array_merge($queries); // condense the indexes
	return $queries;
}

function safe_presentation_query_mappings($query_mappings) {
    foreach($query_mappings as &$query_mapping) {
        if ($query_mapping["mapping_type"] == "federated") {
            $query_mapping["mapping_value"] = preg_replace("~^(?:mysql://)?([^@]+@)?~i", "mysql://******@", $query_mapping["mapping_value"]);
        }
    }
    return $query_mappings;
}

function rewrite_query($query, $mapping_rules) {
	foreach ($mapping_rules as $mapping) { 
		if ($mapping["mapping_type"] == "regex") {
			$query = preg_replace($mapping["mapping_key"], $mapping["mapping_value"], $query);
		}
		if ($mapping["mapping_type"] == "federated") {
			preg_match("/^.*[)][\s]*ENGINE[\s]*=[\s]*FEDERATED[\s]*.*CONNECTION[\s]*=[\s]*'(.*?)'.*$/is", $query, $matches);
			if ($matches) {
				// There is a ENGINE=FEDERATED clause. We rewrite the CONNECTION part.
				$matched_connection_url = $matches[1];
				$connection_url_tokens = parse_url($matched_connection_url);
				$replacement_url = $mapping["mapping_value"];
				$replacement_url = preg_replace("@^(?:mysql://)?@i", "mysql://", $replacement_url); // make sure starts with "mysql://"
				$replacement_url_tokens = parse_url($replacement_url);
				// $replacement_url may include path (in which case we only use the schema name)
				// or it may not include path (ie only lists mysql://user:password@host:port or subset) (in which case we take the path from original query)
				if (isset($replacement_url_tokens["path"]) && ($replacement_url_tokens["path"] != "/")) {
					$schema = next(explode('/', $replacement_url_tokens["path"]));
					$table_name = end(explode('/', $connection_url_tokens['path']));
					$replacement_url_tokens["path"] = "/$schema/$table_name";
				}
				else {
					$replacement_url_tokens["path"] = $connection_url_tokens['path'];
				}
				$replacement_url = http_build_url($matched_connection_url, $replacement_url_tokens);
				$query = str_replace($matched_connection_url, $replacement_url, $query);
			}
		}
	}
	return $query;
}


function checksum_query($query) {
    $query = preg_replace('/[\\s]+/', "", $query);
    return md5($query);
}


function convert_ips_to_hostnames($text) {
	$text = preg_replace_callback(
			"/([0-9]{1,3}.[0-9]{1,3}.[0-9]{1,3}.[0-9]{1,3})/s",
			function ($matches) {
				return gethostbyaddr($matches[0]);
			},
			$text
	);
	return $text;
}

/**
 *
 * @param unknown $sql
 * @return unknown
 */
function cleanup_query($sql) {
	// Remvoe comments
	$sql = preg_replace('/[\/][*].*?[*][\/]/', '', $sql);
	$sql = preg_replace('/ -- .*$/', '', $sql);
	// compress spaces: this is dangerous because it will do same for strings
	$sql = preg_replace('/[\s]+/', ' ', $sql);
	
	$sql = trim($sql);
	return $sql;
}

/**
 *
 * @param unknown $full_qualified_table_name
 * @return multitype:string NULL
 */
function parse_table_name($fully_qualified_object_name) {
	$result = array (
			"object_name" => "",
			"schema_name" => ""
	);
	$fully_qualified_object_name = trim($fully_qualified_object_name);
	$tokens = explode(".", $fully_qualified_object_name);

	if (count($tokens) == 1) {
		$result ["object_name"] = trim($tokens [0], "`");
	}
	if (count($tokens) == 2) {
		$result ["schema_name"] = trim($tokens [0], "`");
		$result ["object_name"] = trim($tokens [1], "`");
	}
	return $result;
}

/**
 *
 * @param unknown $fully_qualified_object_name
 */
function parse_schema_name($fully_qualified_object_name) {
	return trim(trim($fully_qualified_object_name), "`");
}

/**
 *
 * @param unknown $sql
 * @return multitype: multitype:string <> Ambigous <NULL>
 */
function parse_query($sql) {
	$sql = cleanup_query($sql);
	if (empty($sql))
		throw new Exception("Empty query found");

	$tokens = explode(" ", $sql);
	if (empty($tokens))
		throw new Exception("Error tokenizing query: no tokens found");

	$query_type = '';
	$parse_tokens = array ();
	if (in_array(strtolower($tokens [0]), array (
			'alter',
			'create',
			'drop'
	))) {
		$query_type = strtolower($tokens [0]);
		if (in_array(strtolower($tokens [1]), array (
				'table',
				'view',
				'procedure',
				'function',
				'event',
				'trigger'
		))) {
			$parse_tokens = parse_table_name($tokens [2]);
		} else if (! strcasecmp($tokens [1], 'database')) {
			$parse_tokens ["schema_name"] = parse_schema_name($tokens [2]);
		} else {
			throw new Exception("Unsupported " . $tokens [0] . " statement");
		}
	} else {
		throw new Exception("Unsupported statement: " . $tokens [0]);
	}
	return array (
			"query_type" => $query_type,
			"schema_name" => $parse_tokens ["schema_name"],
			"object_name" => $parse_tokens ["object_name"]
	);
}

