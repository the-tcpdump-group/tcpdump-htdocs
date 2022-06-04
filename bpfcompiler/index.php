<?php

define ('PAGE_TITLE', 'BPF Compiler');
define ('VER_INPUT_NAME', 'ver');
define ('DLT_INPUT_NAME', 'dlt');
define ('EXPR_INPUT_NAME', 'filter');
define ('SUBMIT_INPUT_NAME', 'compile');
define ('DEFAULT_DLT', 'EN10MB');
define ('DEFAULT_FILTER', 'tcp or udp port 53 or 123');
define ('TIMESTAMP_FILE', '/tmp/bpf_timestamp.txt');
define ('RADARE2_BIN', '/usr/bin/r2');

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
	'NULL' => array
	(
		'descr' => 'BSD loopback encapsulation',
		'val' => 0,
	),
	'EN10MB' => array
	(
		'descr' => 'Ethernet',
		'val' => 1,
	),
	'IEEE802' => array
	(
		'descr' => '802.5 Token Ring',
		'val' => 6,
	),
	'ARCNET' => array
	(
		'descr' => 'ARCNET, with BSD-style header',
		'val' => 7,
	),
	'SLIP' => array
	(
		'descr' => 'Serial Line IP',
		'val' => 8,
	),
	'PPP' => array
	(
		'descr' => 'Point-to-point Protocol',
		'val' => 9,
	),
	'FDDI' => array
	(
		'descr' => 'Fiber Distributed Data Interface',
		'val' => 10,
	),
	'ATM_CLIP' => array
	(
		'descr' => 'Linux Classical IP over ATM',
		'val' => 19,
	),
	'PPP_SERIAL' => array
	(
		'descr' => 'PPP over serial with HDLC encapsulation',
		'val' => 50,
	),
	'PPP_ETHER' => array
	(
		'descr' => 'PPP over Ethernet',
		'val' => 51,
	),
	'SYMANTEC_FIREWALL' => array
	(
		'val' => 99,
	),
	'ATM_RFC1483' => array
	(
		'descr' => 'LLC-encapsulated ATM',
		'val' => 100,
	),
	'RAW' => array
	(
		'descr' => 'Raw IP',
		'val' => 101,
	),
	'C_HDLC' => array
	(
		'descr' => 'Cisco HDLC',
		'val' => 104,
	),
	'IEEE802_11' => array
	(
		'descr' => 'IEEE 802.11 WLAN',
		'val' => 105,
	),
	'LOOP' => array
	(
		'descr' => 'OpenBSD loopback encapsulation',
		'val' => 108,
	),
	'FRELAY' => array
	(
		'descr' => 'Frame Relay',
		'val' => 107,
	),
	'LINUX_SLL' => array
	(
		'descr' => 'Linux cooked',
		'val' => 113,
	),
	'LTALK' => array
	(
		'descr' => 'Apple LocalTalk',
		'val' => 114,
	),
	'PRISM_HEADER' => array
	(
		'descr' => 'Prism monitor mode + 802.11',
		'val' => 119,
	),
	'IP_OVER_FC' => array
	(
		'descr' => 'IP-over-Fibre Channel',
		'val' => 122,
	),
	'SUNATM' => array
	(
		'descr' => 'ATM with SunATM encapsulation',
		'val' => 123,
	),
	'IEEE802_11_RADIO' => array
	(
		'descr' => 'Radiotap + IEEE 802.11 WLAN',
		'val' => 127,
	),
	'ARCNET_LINUX' => array
	(
		'descr' => 'ARCNET, with Linux-style header',
		'val' => 129,
	),
	'APPLE_IP_OVER_IEEE1394' => array
	(
		'val' => 138,
	),
	'IEEE802_11_RADIO_AVS' => array
	(
		'val' => 163,
	),
	'BACNET_MS_TP' => array
	(
		'descr' => 'BACnet MS/TP frames',
		'val' => 165,
	),
	'PPP_PPPD' => array
	(
		'descr' => 'Point-to-point Protocol',
		'val' => 166,
	),
	'PPI' => array
	(
		'descr' => 'Per Packet Information encapsulated packets',
		'val' => 192,
	),
	'IPNET' => array
	(
		'descr' => 'Solaris ipnet pseudo-header',
		'val' => 226,
	),
	'IPV4' => array
	(
		'descr' => 'raw IPv4',
		'val' => 228,
	),
	'IPV6' => array
	(
		'descr' => 'raw IPv6',
		'val' => 229,
	),
	'NETANALYZER' => array
	(
		'val' => 240,
	),
	'NETANALYZER_TRANSPARENT' => array
	(
		'val' => 241,
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
printf ("<SELECT name='%s' id='%s' tabindex=100>\n", VER_INPUT_NAME, VER_INPUT_NAME);
foreach (array_keys ($versions) as $optval)
{
	echo "<OPTION value='${optval}'";
	if ($optval == $req_ver)
		echo ' selected';
	echo ">${optval}</OPTION>\n";
}
echo "</SELECT>\n</TD>\n</TR>\n";

echo "<TR>\n";
printf
(
	"<TH><LABEL for='%s'>Link-layer header type (<A href='/linktypes.html'>?</A>):</LABEL></TH>\n",
	DLT_INPUT_NAME
);
printf ("<TD><SELECT name='%s' tabindex=200>\n", DLT_INPUT_NAME);
foreach ($dltlist as $dlt_code => $dlt)
{
	echo "<OPTION value=${dlt_code}";
	if ($dlt_code == ($req_dlt_name ?? DEFAULT_DLT))
		echo ' selected';
	$dlt_descr = array_key_exists ('descr', $dlt) ? " (${dlt['descr']})" : '';
	echo ">${dlt['val']}: DLT_${dlt_code}${dlt_descr}</OPTION>\n";
}
echo "</SELECT>\n</TD>\n</TR>\n";

echo "<TR>\n";
printf
(
	"<TH><LABEL for='%s'>Filter expression (<A href='/manpages/pcap-filter.7.html'>?</A>):</LABEL></TH>\n",
	EXPR_INPUT_NAME
);
printf
(
	"<TD><INPUT type=text size=80 name='%s' id='%s' value='%s' tabindex=300></TD>\n",
	EXPR_INPUT_NAME,
	EXPR_INPUT_NAME,
	htmlentities ($req_filter ?? DEFAULT_FILTER)
);
echo "</TR>\n";
?>
						<TR>
							<TD></TD>
							<TD><INPUT type=submit value='<?php echo SUBMIT_INPUT_NAME; ?>' tabindex=1000></TD>
						</TR>
					</TABLE>
					</FORM>
				</DIV>
<?php
function gen_pcap_header (string $dlt_name): string
{
	global $dltlist;

	# Little-endian, without the trailing 4 octets of the link-layer header type.
	$ret = "\xd4\xc3\xb2\xa1\x02\x00\x04\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x00\x04\x00";
	$ret .= pack ('V', $dltlist[$dlt_name]['val']);
	return $ret;
}

# Feed the input to stdin and return the stdout and stderr as a 2-tuple.
function pipe_process (array $argv, string $input): array
{
	if (count ($argv) < 1)
		throw new Exception ('$argv must have at least one element');
	$bin = array_shift ($argv);
	if (strpos ($bin, '/') !== FALSE && ! is_executable ($bin))
		throw new Exception ("the binary ${bin} is not executable!");
	array_unshift ($argv, $bin);
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
		throw new Exception ("failed to run the ${bin} binary!");
	fwrite ($pipes[0], $input);
	fclose ($pipes[0]);
	$stdout = stream_get_contents ($pipes[1]);
	$stderr = stream_get_contents ($pipes[2]);
	proc_close ($po);
	return array ($stdout, $stderr);
}

function run_tcpdump (array $argv, string $dlt_name): string
{
	# tcpdump before 4.99.0, when run by an unprivileged user, fails trying to open
	# a network interface even if provided with the "-y" flag.  To work around that,
	# feed an empty .pcap file with the DLT of interest to stdin.
	if (count ($argv) < 1)
		throw new Exception ('$argv must have at least one element');
	array_splice ($argv, 1, 0, array ('-r', '-'));
	list ($stdout, $stderr) = pipe_process ($argv, gen_pcap_header ($dlt_name));
	$stderr = preg_replace ('/^reading from file -, link-type .+\n/', '', $stderr);
	$stderr = preg_replace ('/^Warning: interface names might be incorrect\n/', '', $stderr);
	if ($stderr != '')
		throw new Exception (htmlentities ($stderr));
	return $stdout;
}

# Parse "tcpdump -ddd" format and return binary BPF instructions.
function bpf_ddd2bin (string $ddd): string
{
	$ret = '';
	$lines = explode ("\n", rtrim ($ddd, "\n"));
	# Require one instruction counter line and at least one instruction line for a "ret".
	if (count ($lines) < 2)
		throw new Exception ('the input must comprise at least two lines of text');

	# The instruction counter.
	$line = array_shift ($lines);
	if (1 !== preg_match ('/^\d+$/', $line, $m))
		throw new Exception (htmlentities ("malformed instruction counter line: '${line}'"));
	$declared = (int) $m[0];
	if ($declared < 1)
		throw new Exception ("instruction counter ${declared} declared too low");
	if ($declared != count ($lines))
		throw new Exception ("instruction counter ${declared} does not match the contents");

	# The instructions.
	foreach ($lines as $line)
	{
		if (1 !== preg_match ('/^(?P<opcode>\d+) (?P<jt>\d+) (?P<jf>\d+) (?P<k>\d+)$/', $line, $m))
			throw new Exception (htmlentities ("malformed instruction line: '${line}'"));
		# 16-bit, 8-bit, 8-bit, 32-bit; nCCN for LE, vCCV for LE.
		$ret .= pack ('vCCV', $m['opcode'], $m['jt'], $m['jf'], $m['k']);
	}
	return $ret;
}

function run_radare2 (string $ddd): string
{
	list ($stdout, $stderr) = pipe_process
	(
		array
		(
			RADARE2_BIN,
			# These options require either Radare2 5.7.1 (after it is released)
			# or a recent build of the master branch.
			'-q',
			'-a', 'bpf.mr', # Not the Capstone BPF engine.
			'-e', 'scr.utf8=true', # Defaults to ASCII when LANG=C.
			'-e', 'scr.utf8.curvy=true',
			'-e', 'scr.html=true',
			'-e', 'scr.color=0',
			'-e', 'asm.nbytes=8', # 6 octets by default.
			'-e', 'asm.lines.wide=true',
			'-e', 'asm.lines.width=30',
			'-c', 'pD $s', # Disassemble the input file size worth of bytes.
			'stdin://'
		),
		bpf_ddd2bin ($ddd)
	);
	if ($stderr != '')
		throw new Exception ($stderr);
	return $stdout;
}

function limit_request_rate(): void
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
}

function process_request (string $tcpdump_bin, string $req_dlt_name, string $req_filter): void
{
	$libpcap_before =
		$libpcap_after =
		$radare2_before =
		$radare2_after = '(N/A)';
	try
	{
		$libpcap_before = htmlentities (run_tcpdump (array ($tcpdump_bin, '-Od', '--', $req_filter), $req_dlt_name));
		$libpcap_after = htmlentities (run_tcpdump (array ($tcpdump_bin, '-d', '--', $req_filter), $req_dlt_name));
		$radare2_before = run_radare2 (run_tcpdump (array ($tcpdump_bin, '-Oddd', '--', $req_filter), $req_dlt_name));
		$radare2_after = run_radare2 (run_tcpdump (array ($tcpdump_bin, '-ddd', '--', $req_filter), $req_dlt_name));
	}
	finally
	{
		echo <<<"ENDOFTEXT"
		<H2 class=title>Packet-matching code</H2>
		<DIV class=entry>
		<TABLE>
			<TR>
				<TD>
					<H3 class=subtitle>before optimization (libpcap format)</H3>
					<PRE>${libpcap_before}</PRE>
				</TD>
				<TD>
					<H3 class=subtitle>after optimization (libpcap format)</H3>
					<PRE>${libpcap_after}</PRE>
				</TD>
			</TR>
			<TR>
				<TD colspan=2>
					<H3 class=subtitle>before optimization (Radare2 format)</H3>
					<PRE>${radare2_before}</PRE>
				</TD>
			</TR>
			<TR>
				<TD colspan=2>
					<H3 class=subtitle>after optimization (Radare2 format)</H3>
					<PRE>${radare2_after}</PRE>
				</TD>
			</TR>
		</TABLE>
		</DIV>
ENDOFTEXT;
	}
}

if ($req_ver !== NULL && $req_dlt_name !== NULL && $req_filter !== NULL)
{
	limit_request_rate();
	try
	{
		process_request ($versions[$req_ver], $req_dlt_name, $req_filter);
	}
	catch (Exception $e)
	{
		echo '<PRE class=stderr>' . $e->getMessage() . '</PRE>';
	}
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
