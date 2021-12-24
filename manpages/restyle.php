<?php

define ('URI_PREFIX', '/manpages/');
define ('HEADER_FILE', '../autoindex_header.html');
define ('FOOTER_FILE', '../autoindex_footer.html');
define ('GIT_MASTER', 'git master branch');

# Put the most frequent URIs first.
$taxonomy = array
(
	'tcpdump' => array
	(
		'versions' => array
		(
			GIT_MASTER,
			'4.99.1',
			'4.99.0',
			'4.9.3',
			'4.9.2',
			'4.9.1',
			'4.9.0',
			'4.8.1',
			'4.7.4',
			'4.6.2',
			'4.5.1',
			'4.4.0',
		),
		'match_master' => '@^(tcpdump\.1)\.html$@',
		'match_release' => '@^(tcpdump\.1)-([0-9.]+)\.html$@',
		'print_master' => '%s.html',
		'print_release' => '%s-%s.html',
		'name_first' => TRUE,
	),
	'libpcap' => array
	(
		'versions' => array
		(
			GIT_MASTER,
			'1.10.1',
			'1.10.0',
			'1.9.1',
			'1.9.0',
			'1.8.1',
			'1.7.4',
			'1.6.2',
			'1.5.3',
		),
		'match_master' => '@^(pcap.+)\.html$@',
		'match_release' => '@^libpcap-([0-9.]+)/(pcap.+)\.html$@',
		'print_master' => '%s.html',
		'print_release' => 'libpcap-%s/%s.html',
		'name_first' => FALSE,
	),
	'tcpslice' => array
	(
		'versions' => array
		(
			GIT_MASTER,
			'1.4',
			'1.3',
		),
		'match_master' => '@^(tcpslice\.1)\.html$@',
		'match_release' => '@^(tcpslice\.1)-([0-9.]+)\.html$@',
		'print_master' => '%s.html',
		'print_release' => '%s-%s.html',
		'name_first' => TRUE,
	),
	'rpcapd' => array
	(
		'versions' => array
		(
			GIT_MASTER,
			'1.10.1',
			'1.10.0',
			'1.9.1',
			'1.9.0',
		),
		'match_master' => '@^(rpcapd.+)\.html$@',
		'match_release' => '@^libpcap-([0-9.]+)/(rpcapd.+)\.html$@',
		'print_master' => '%s.html',
		'print_release' => 'libpcap-%s/%s.html',
		'name_first' => FALSE,
	),
);

function fail (int $status): void
{
	$statusmap = array
	(
		400 => 'Bad Request',
		403 => 'Forbidden',
		404 => 'Not Found',
		500 => 'Internal Server Error',
	);
	if (! array_key_exists ($status, $statusmap))
		$status = 500;
	$line = sprintf ('%u %s', $status, $statusmap[$status]);
	header ("${_SERVER['SERVER_PROTOCOL']} ${line}");
	echo "<!DOCTYPE html>
<HTML lang='en'>
<HEAD>
<TITLE>${line}</TITLE>
</HEAD>
<BODY>
<H1>${line}</H1>
</BODY>
</HTML>
";
	exit;
}

function read_file (string $filename): string
{
	if (FALSE === $ret = @file_get_contents ($filename))
		fail (500);
	return $ret;
}

if (! array_key_exists ('REQUEST_URI', $_SERVER))
	fail (400);
$uri_path = $_SERVER['REQUEST_URI'];
if ($uri_path == $_SERVER['SCRIPT_NAME'])
	fail (403);

if (0 !== strpos ($uri_path, URI_PREFIX))
	fail (400);
$uri_path = str_replace (URI_PREFIX, '', $uri_path);
# This is relative to the client-visible URL path, so remove any
# directory prefix and change the filename extension.
$txt_href = preg_replace ('@^(?:.+/)?(.+)\.html$@', '$1.txt', $uri_path);

foreach ($taxonomy as $pname => $project)
{
	if (1 === preg_match ($project['match_master'], $uri_path, $m))
		list ($basename, $version) = array ($m[1], GIT_MASTER);
	elseif (1 === preg_match ($project['match_release'], $uri_path, $m))
		list ($basename, $version) = $project['name_first'] ? array ($m[1], $m[2]) : array ($m[2], $m[1]);
	else
		continue;

	# Now the file path is valid enough to try opening.
	if (! file_exists ($uri_path))
		fail (404);
	$html = read_file ($uri_path);

	$other_versions = array();
	foreach ($project['versions'] as $v)
	{
		if ($v == $version)
			continue;
		if ($v == GIT_MASTER)
			$href = sprintf ($project['print_master'], $basename);
		elseif ($project['name_first'])
			$href = sprintf ($project['print_release'], $basename, $v);
		else
			$href = sprintf ($project['print_release'], $v, $basename);
		if (! file_exists ($href))
			continue;
		$other_versions[] = sprintf ('<a href="%s%s">%s</a>', URI_PREFIX, $href, $v);
	}

	$toreplace = array
	(
		array
		(
			'@(.*<BODY>)$@m',
			"\$1\n" . read_file (HEADER_FILE) . "<DIV id=page>\n<DIV class=post>\n"
		),
		array
		(
			'@<li>(<a href="' . URI_PREFIX . '">)@',
			'<li class=current_page_item>$1'
		),
		array
		(
			"@(<H4>This man page documents ${pname} version .+)(.</H4>)@",
			"\$1 (see also: " . implode (', ', $other_versions) . ")\$2"
		),
		array
		(
			'@(<TITLE>.+)(</TITLE>)@',
			'$1 | TCPDUMP &amp; LIBPCAP$2'
		),
		array
		(
			'@^(</HEAD>.*)$@m',
			"<link href=\"${txt_href}\" rel=\"alternate\" type=\"text/plain\">\n\$1"
		),
		array
		(
			'@<H1>(.+)</H1>@',
			"<H1 class=title>\$1</H1>\n<DIV class=entry>"
		),
		array
		(
			'@(<H2)(.*>.+</H2>)@',
			"</DIV>\n\$1 class=title\$2\n<DIV class=entry>"
		),
		array
		(
			'@(<H3)(.*>.+</H3>)@',
			'$1 class=subtitle$2'
		),
		# Fix HREFs from libpcap release pages.
		array
		(
			'@<A HREF="\./(tcpdump|tcpslice)\.1\.html">@',
			'<A HREF="' . URI_PREFIX . '$1.1.html">'
		),
		array
		(
			'@(</BODY>.*)$@m',
			"</DIV>\n</DIV>\n</DIV>\n" . read_file (FOOTER_FILE) . "\n\$1"
		),
	);
	foreach ($toreplace as $each)
	{
		list ($pattern, $replacement) = $each;
		$html = preg_replace ($pattern, $replacement, $html);
	}

	header ('Last-Modified: ' . gmdate (DATE_RFC7231, filemtime ($uri_path)));
	echo $html;
	exit;
}
fail (400);
