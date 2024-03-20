<?php

/*
 * Copyright (c) 2021 The TCPDUMP project
 * All rights reserved.
 * SPDX-License-Identifier: BSD-2-Clause
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 * 1. Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in the
 *    documentation and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 */

define ('URI_PREFIX', '/manpages/');
define ('HEADER_FILE', '../autoindex_header.html');
define ('FOOTER_FILE', '../autoindex_footer.html');
define ('GIT_MASTER', 'git master branch');
define ('TITLE_SUFFIX', ' | TCPDUMP &amp; LIBPCAP');

# Put the most frequent URIs first.
$taxonomy = array
(
	'tcpdump' => array
	(
		'versions' => array
		(
			GIT_MASTER,
			'4.99.4',
			'4.99.2',
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
			'1.10.4',
			'1.10.2',
			'1.10.1',
			'1.10.0',
			'1.9.1',
			'1.8.1',
			'1.7.4',
			'1.6.2',
			'1.5.3',
		),
		'match_master' => '@^(cbpf.+|pcap.+)\.html$@',
		'match_release' => '@^libpcap-([0-9.]+)/(cbpf.+|pcap.+)\.html$@',
		'print_master' => '%s.html',
		'print_release' => 'libpcap-%s/%s.html',
		'name_first' => FALSE,
	),
	'tcpslice' => array
	(
		'versions' => array
		(
			GIT_MASTER,
			'1.7',
			'1.6',
			'1.5',
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
			'1.10.4',
			'1.10.2',
			'1.10.1',
			'1.10.0',
			'1.9.1',
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
		405 => 'Method Not Allowed',
		500 => 'Internal Server Error',
	);
	if (! array_key_exists ($status, $statusmap))
		$status = 500;
	$line = sprintf ('%u %s', $status, $statusmap[$status]);
	header ("{$_SERVER['SERVER_PROTOCOL']} {$line}");
	echo "<!DOCTYPE html>
<HTML lang='en'>
<HEAD>
<TITLE>{$line}</TITLE>
</HEAD>
<BODY>
<H1>{$line}</H1>
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

if (! in_array ($_SERVER['REQUEST_METHOD'], array ('GET', 'HEAD')))
	fail (405);

$uri_path = $_SERVER['REQUEST_URI'];
# Make the following two checks in the PHP space because in the server
# configuration space it would be notably more complicated.
if (! array_key_exists ('REDIRECT_URL', $_SERVER))
	# The request did not come via the RewriteRule in .htaccess.
	fail (403);
# REDIRECT_URL contains an URI path in the nominal form (with neither
# duplicate slashes nor a query string nor percent-encoding).  If the client
# is trying to use any other form, normalize it explicitly.
if ($uri_path != $_SERVER['REDIRECT_URL'])
{
	# The value is just the path, so the reference is relative.
	header ("Location: ${_SERVER['REDIRECT_URL']}", TRUE, 301);
	exit;
}

# On a correctly configured server this condition is always false, but let's
# not fail to fail correctly.
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
	$see_also = count ($other_versions) ?
		('see also: ' . implode (', ', $other_versions)) :
		'only this version has this man page';

	$toreplace = array
	(
		array
		(
			'@(.*<BODY>)$@m',
			"\$1\n" . read_file (HEADER_FILE) . "<DIV id=page class=manpage>\n<DIV class=post>\n"
		),
		array
		(
			'@<li>(<a href="' . URI_PREFIX . '">)@',
			'<li class=current_page_item>$1'
		),
		array
		(
			"@(<H4>This man page documents {$pname} version .+)(.</H4>)@",
			"\$1 ($see_also)\$2"
		),
		array
		(
			'@(<TITLE>)(Man page of .+)(</TITLE>)@',
			1 === preg_match ('/^(.+)\.([1-9][a-z]*)$/', $basename, $m) ?
				sprintf ('$1%s(%s) man page%s$3', $m[1], strtoupper ($m[2]), TITLE_SUFFIX) :
				sprintf ('$1$2%s$3', TITLE_SUFFIX)
		),
		array
		(
			'@(<H1>)(Man page of .+)(</H1>)@',
			1 === preg_match ('/^(.+)\.([1-9][a-z]*)$/', $basename, $m) ?
				sprintf ('$1%s(%s) man page$3', $m[1], strtoupper ($m[2])) :
				'$1$2$3'
		),
		# In those few pages that still have a ToC put the index
		# hyperlink on a separate line to make it easier for the next
		# two substitutions to match their input.  Insert an extra
		# delimiter in the same go.
		array
		(
			'@(<BR>)(<A HREF="#index">Index</A>)$@m',
			"\$1\n\$2 &bull;"
		),
		array
		(
			'@^Section: .+<BR>(.+<BR>)$@m',
			'$1'
		),
		# Combine two header lines into one and make it a mini-menu.
		# Month days can occur both with and without leading zeroes,
		# which comes from the man pages rather than man2html.
		array
		(
			'@^(Updated: [0123]?[0-9] [[:alpha:]]+ 20[0-9][0-9])<BR>$@m',
			"\$1 &bull;\n<A href='{$txt_href}'>View in plain text</A> &bull;"
		),
		array
		(
			'@^(</HEAD>.*)$@m',
			"<link href=\"{$txt_href}\" rel=\"alternate\" type=\"text/plain\">\n\$1"
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
		# In the live version use /style.css and /images/ instead of the relative
		# paths to simplify the visible URL space.
		array
		(
			'@( href=")\.\.(/style\.css")@',
			'$1$2'
		),
		array
		(
			'@( href=")\.\.(/images/)@',
			'$1$2'
		),
		# Prevent out of place line breaks inside various pcap man page names.
		array
		(
			'@<B><A HREF="\./(pcap|cbpf)[_-].+\.html">(pcap|cbpf)[_-].+</A></B>\(.+\)@',
			'<SPAN class=manref>$0</SPAN>'
		),
	);
	foreach ($toreplace as $each)
	{
		list ($pattern, $replacement) = $each;
		$html = preg_replace ($pattern, $replacement, $html);
	}

	# As far as an HTTP client cache is concerned, it does not matter when
	# the requested .html file or this script were modified or what their
	# contents or filesystem location are on the server, but it does matter
	# what the resulting HTML output is and whether it is exactly the same
	# as before; hence use only a digest of the latter as the entity tag.
	$etag = base64_encode (sha1 ($html, TRUE));
	# Rely on mod_headers custom configuration in ../.htaccess to see the
	# original entity tag in If-None-Match header of the request.
	if
	(
		array_key_exists ('HTTP_IF_NONE_MATCH', $_SERVER) &&
		in_array ("\"{$etag}\"", explode (', ', $_SERVER['HTTP_IF_NONE_MATCH']))
	)
	{
		header ("{$_SERVER['SERVER_PROTOCOL']} 304 Not Modified");
		exit;
	}
	# Omit Last-Modified so the client omits If-Modified-Since and there is
	# less logic to implement in this script.
	header ("ETag: \"$etag\"");
	header ('Content-Length: ' . strlen ($html));
	if ($_SERVER['REQUEST_METHOD'] == 'GET')
		echo $html;
	exit;
}
fail (400);
