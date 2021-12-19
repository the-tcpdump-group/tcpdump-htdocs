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
		'versions' => array ('4.9.0', '4.9.1', '4.9.2', '4.9.3', '4.99.0', '4.99.1'),
		'match_master' => '@^(tcpdump\.1)\.html$@',
		'match_release' => '@^(tcpdump\.1)-([0-9.]+)\.html$@',
		'print_master' => '%s.html',
		'print_release' => '%s-%s.html',
		'name_first' => TRUE,
	),
	'libpcap' => array
	(
		'versions' => array ('1.9.0', '1.9.1', '1.10.0', '1.10.1'),
		'match_master' => '@^(pcap.+)\.html$@',
		'match_release' => '@^libpcap-([0-9.]+)/(pcap.+)\.html$@',
		'print_master' => '%s.html',
		'print_release' => 'libpcap-%s/%s.html',
		'name_first' => FALSE,
	),
	'tcpslice' => array
	(
		'versions' => array ('1.3', '1.4'),
		'match_master' => '@^(tcpslice\.1)\.html$@',
		'match_release' => '@^(tcpslice\.1)-([0-9.]+)\.html$@',
		'print_master' => '%s.html',
		'print_release' => '%s-%s.html',
		'name_first' => TRUE,
	),
	'rpcapd' => array
	(
		'versions' => array ('1.9.0', '1.9.1', '1.10.0', '1.10.1'),
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
	$allversions = $project['versions'];
	$allversions[] = GIT_MASTER;
	foreach ($allversions as $v)
	{
		if ($v == $version)
			continue;
		if ($v == GIT_MASTER)
			$href = sprintf ($project['print_master'], $basename);
		elseif ($project['name_first'])
			$href = sprintf ($project['print_release'], $basename, $v);
		else
			$href = sprintf ($project['print_release'], $v, $basename);
		$other_versions[] = sprintf ('<a href="%s%s">%s</a>', URI_PREFIX, $href, $v);
	}

	$toreplace = array
	(
		array
		(
			'@^<!DOCTYPE HTML PUBLIC .+>@',
			'<!DOCTYPE html>'
		),
		array
		(
			'@<HTML>@',
			'<HTML lang="en">'
		),
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
			'<link rel="shortcut icon" href="/images/T-32x32.png" type="image/png">' . "\n\$1"
		),
		array
		(
			'@^(</HEAD>.*)$@m',
			'<link href="/style.css" rel="stylesheet" type="text/css" media="screen">' . "\n\$1"
		),
		array
		(
			'@^(</HEAD>.*)$@m',
			"<link href=\"${basename}.txt\" rel=\"alternate\" type=\"text/plain\">\n\$1"
		),
		# In today's snapshot of HTML spec <DL compact>, <TT> and <A name=> are obsolete.
		array
		(
			'@<DL COMPACT>@',
			"<DL>"
		),
		array
		(
			'@</?TT>@',
			''
		),
		array
		(
			'@<H1>(.+)</H1>@',
			"<H1 class=title>\$1</H1>\n<DIV class=entry>"
		),
		array
		(
			'@<A NAME="(.+)">&nbsp;</A>\n?<H2>(.+)</H2>@',
			"</DIV>\n<H2 class=title id=\$1>\$2</H2>\n<DIV class=entry>"
		),
		array
		(
			'@<A NAME="(.+)">&nbsp;</A>\n?<H3>(.+)</H3>@',
			"<H3 class=subtitle id=\$1>\$2</H3>"
		),
		array
		(
			"@<HR>\nThis document was created by.+Time: (.+, 20[0-9]{2}).+(</BODY>)@s",
			'</DIV>
<H2 class=title>COLOPHON</H2>
<DIV class=entry>
This HTML man page was generated at $1
from a source man page in "The Tcpdump Group" git repositories
using man2html and other tools.
</DIV>
$2'
		),
		array
		(
			'@(</BODY>.*)$@m',
			"</DIV>\n</DIV>\n" . read_file (FOOTER_FILE) . "\n\$1"
		),
	);
	foreach ($toreplace as $each)
	{
		list ($pattern, $replacement) = $each;
		$html = preg_replace ($pattern, $replacement, $html);
	}

	echo $html;
	exit;
}
fail (400);
