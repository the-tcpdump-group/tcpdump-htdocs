<?php

define ('PAGE_TITLE', 'BPF Compiler');
define ('VER_INPUT_NAME', 'ver');
define ('DLT_INPUT_NAME', 'dlt');
define ('EXPR_INPUT_NAME', 'filter');
define ('SUBMIT_INPUT_NAME', 'compile');
define ('DEFAULT_DLT', 'EN10MB');
define ('DEFAULT_FILTER', 'tcp or udp port 53 or 123');
# Little-endian, without the trailing 4 octets of the link-layer header type.
define ('DLT_HEADER_PREFIX', "\xd4\xc3\xb2\xa1\x02\x00\x04\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x04\x00");
define ('TIMESTAMP_FILE', '/tmp/bpf_timestamp.txt');

$versions = array
(
	# libpcap 1.9.1 in Ubuntu 20.04 (/usr/sbin/tcpdump, v4.9.3)
	# libpcap 1.10.0 in Debian 11 (/usr/bin/tcpdump, v4.99.0)
	'default' => 'tcpdump',
	'1.10.1' => '/usr/local/bin/tcpdump-libpcap-1.10.1',
	'1.9.1' => '/usr/local/bin/tcpdump-libpcap-1.9.1',
	'1.8.1' => '/usr/local/bin/tcpdump-libpcap-1.8.1',
	'1.7.4' => '/usr/local/bin/tcpdump-libpcap-1.7.4',
	'1.6.2' => '/usr/local/bin/tcpdump-libpcap-1.6.2',
	'1.5.3' => '/usr/local/bin/tcpdump-libpcap-1.5.3',
);

$dltlist = array
(
	'EN10MB' => array
	(
		'descr' => 'Ethernet',
		'val' => 1,
	),
	'RAW' => array
	(
		'descr' => 'Raw IP',
		'val' => 101,
	),
	'LINUX_SLL' => array
	(
		'descr' => 'Linux cooked',
		'val' => 113,
	),
	'IEEE802_11' => array
	(
		'descr' => 'IEEE 802.11 WLAN',
		'val' => 105,
	),
	'IEEE802_11_RADIO' => array
	(
		'descr' => 'Radiotap + IEEE 802.11 WLAN',
		'val' => 127,
	),
	'LINUX_SLL2' => array
	(
		'descr' => 'Linux cooked v2',
		'val' => 276,
	),
);

function array_fetch (array $a, $key, $dfl)
{
	return array_key_exists ($key, $a) ? $a[$key] : $dfl;
}

function fail (int $status): void
{
	ob_end_clean();
	$statusmap = array
	(
		400 => 'Bad Request',
		405 => 'Method Not Allowed',
		429 => 'Too Many Requests',
	);
	if (! array_key_exists ($status, $statusmap))
		$status = 500;
	$line = sprintf ('%u %s', $status, $statusmap[$status]);
	header ("${_SERVER['SERVER_PROTOCOL']} ${line}");
	echo <<<"ENDOFTEXT"
<!DOCTYPE html>
<HTML lang='en'>
	<HEAD>
		<META charset="utf-8">
		<TITLE>${line}</TITLE>
	</HEAD>
	<BODY>
		<H1>${line}</H1>
	</BODY>
</HTML>
ENDOFTEXT;
	exit;
}

if ($_SERVER['REQUEST_METHOD'] != 'GET')
	fail (405);
$req_ver = array_fetch ($_REQUEST, VER_INPUT_NAME, NULL);
if ($req_ver !== NULL && ! array_key_exists ($req_ver, $versions))
	fail (400);
$req_dlt_name = array_fetch ($_REQUEST, DLT_INPUT_NAME, NULL);
if ($req_dlt_name !== NULL && ! array_key_exists ($req_dlt_name, $dltlist))
	fail (400);
$req_filter = array_fetch ($_REQUEST, EXPR_INPUT_NAME, NULL);

# HTTP status code can be changed only before the document, so start buffering
# now to enable HTTP 429 and other errors later on, when some of the output
# has already been generated.
ob_start();

?>
<!DOCTYPE html>
<HTML lang='en'>
	<HEAD>
		<META charset="utf-8">
		<TITLE><?php echo PAGE_TITLE . ' | TCPDUMP &amp; LIBPCAP'; ?></TITLE>
		<LINK rel="stylesheet" href="/style.css" type="text/css">
		<LINK href="/images/T-32x32.png" rel="shortcut icon" type="image/png">
		<STYLE type="text/css">
TH {
	text-align: right;
	white-space: nowrap;
}
TD {
	vertical-align: top;
	padding-right: 1em;
}
PRE.stderr {
	color: red;
}
		</STYLE>
	</HEAD>
	<BODY>
<?php
readfile ('../autoindex_header.html');
?>
		<DIV id=page>
			<DIV class=post>
				<H2 class=title><?php echo PAGE_TITLE; ?></H2>
				<DIV class=entry>
					<FORM method=get>
					<TABLE>
<?php
echo "<TR>\n";
printf
(
	"<TH><LABEL for='%s'>libpcap version (<A href='/release/'>?</A>):</LABEL></TH>\n",
	VER_INPUT_NAME
);
echo "<TD>\n";
printf ("<SELECT name='%s' id='%s'>\n", VER_INPUT_NAME, VER_INPUT_NAME);
foreach (array_keys ($versions) as $optval)
{
	echo "<OPTION value='${optval}'";
	if ($optval == $req_ver)
		echo ' selected';
	echo ">${optval}</OPTION>\n";
}
echo "</SELECT>\n</TD>\n</TR>\n";
?>

<?php
echo "<TR>\n";
printf
(
	"<TH><LABEL for='%s'>Link-layer header type (<A href='/linktypes.html'>?</A>):</LABEL></TH>\n",
	DLT_INPUT_NAME
);
printf ("<TD><SELECT name='%s'>\n", DLT_INPUT_NAME);
foreach ($dltlist as $dlt_code => $dlt)
{
	echo "<OPTION value=${dlt_code}";
	if ($dlt_code == ($req_dlt_name ?? DEFAULT_DLT))
		echo ' selected';
	echo ">DLT_${dlt_code} (${dlt['descr']})</OPTION>\n";
}
echo "</SELECT>\n</TD>\n</TR>\n";
?>

<?php
echo "<TR>\n";
printf
(
	"<TH><LABEL for='%s'>Filter expression (<A href='/manpages/pcap-filter.7.html'>?</A>):</LABEL></TH>\n",
	EXPR_INPUT_NAME
);
printf
(
	"<TD><INPUT type=text size=80 name='%s' id='%s' value='%s'></TD>\n",
	EXPR_INPUT_NAME,
	EXPR_INPUT_NAME,
	htmlentities ($req_filter ?? DEFAULT_FILTER)
);
echo "</TR>\n";
?>
						<TR>
							<TD></TD>
							<TD><INPUT type=submit value='<?php echo SUBMIT_INPUT_NAME; ?>'></TD>
						</TR>
					</TABLE>
					</FORM>
				</DIV>
<?php
function run_tcpdump (string $tcpdump_bin, string $dlt_name, string $filter, bool $optimize): void
{
	global $dltlist;

	if (strpos ($tcpdump_bin, '/') !== FALSE && ! is_executable ($tcpdump_bin))
	{
		printf ("<PRE class=stderr>ERROR: the requested tcpdump binary is not executable!</PRE>");
		return;
	}
	$argv = array ($tcpdump_bin, '-r', '-', '-d', $filter);
	if (! $optimize)
		$argv []= '-O';
	# tcpdump before 4.99.0, when run by an unprivileged user, fails trying to open
	# a network interface even if provided with the "-y" flag.  To work around that,
	# feed an empty .pcap file with the DLT of interest to stdin.
	$po = proc_open
	(
		$argv,
		array
		(
			0 => array ('pipe', 'r'), # stdin
			1 => array ('pipe', 'w'), # stdout
			2 => array ('pipe', 'w'), # stderr
		),
		$pipes
	);
	# FIXME: When trying to execute a non-existent binary, proc_open() returns
	# a resource that is indistinguishable from a successful invocation.
	if ($po === FALSE)
	{
		printf ('<PRE class=stderr>ERROR: failed to run tcpdump binary!</PRE>');
		return;
	}
	fwrite ($pipes[0], DLT_HEADER_PREFIX . pack ('V', $dltlist[$dlt_name]['val']));
	$stdout = stream_get_contents ($pipes[1]);
	$stderr = stream_get_contents ($pipes[2]);
	$stderr = preg_replace ('/^reading from file -, link-type .+\n/', '', $stderr);
	$stderr = preg_replace ('/^Warning: interface names might be incorrect\n/', '', $stderr);
	proc_close ($po);
	if ($stdout != '')
		echo "<PRE>${stdout}</PRE>";
	if ($stderr != '')
		echo "<PRE class=stderr>${stderr}</PRE>";
}

if ($req_ver !== NULL && $req_dlt_name !== NULL && $req_filter !== NULL)
{
	# Enforce at lest 1 second between requests that spawn tcpdump.
	if (file_exists (TIMESTAMP_FILE))
	{
		$now = time();
		if (FALSE === $mtime = @filemtime (TIMESTAMP_FILE))
			fail (500);
		if ($mtime > $now)
			fail (500);
		if ($now - $mtime < 1)
			fail (429);
	}
	if (! @touch (TIMESTAMP_FILE))
		fail (500);
?>
				<H2 class=title>Packet-matching code</H2>
				<DIV class=entry>
					<TABLE>
						<TR>
							<TD><H3 class=subtitle>before optimization</H3>
<?php
run_tcpdump ($versions[$req_ver], $req_dlt_name, $req_filter, FALSE);
?>
							</TD>
							<TD><H3 class=subtitle>after optimization</H3>
<?php
run_tcpdump ($versions[$req_ver], $req_dlt_name, $req_filter, TRUE);
?>
							</TD>
						</TR>
					</TABLE>
				</DIV>
<?php
}
?>
			</DIV>
		</DIV>
<?php
ob_end_flush();
readfile ('../autoindex_footer.html');
?>
	</BODY>
</HTML>
