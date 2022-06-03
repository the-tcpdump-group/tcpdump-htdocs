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

function run_tcpdump (array $argv, string $dlt_name): array
{
	try
	{
		# tcpdump before 4.99.0, when run by an unprivileged user, fails trying to open
		# a network interface even if provided with the "-y" flag.  To work around that,
		# feed an empty .pcap file with the DLT of interest to stdin.
		if (count ($argv) < 1)
			throw new Exception ('$argv must have at least one element');
		array_splice ($argv, 1, 0, array ('-r', '-'));
		list ($stdout, $stderr) = pipe_process ($argv, gen_pcap_header ($dlt_name));
	}
	catch (Exception $e)
	{
		return array (NULL, $e->getMessage());
	}
	$stderr = preg_replace ('/^reading from file -, link-type .+\n/', '', $stderr);
	$stderr = preg_replace ('/^Warning: interface names might be incorrect\n/', '', $stderr);
	return array ($stdout, $stderr);
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
		throw new Exception ("malformed instruction counter line: '${line}'");
	$declared = (int) $m[0];
	if ($declared < 1)
		throw new Exception ("instruction counter ${declared} declared too low");
	if ($declared != count ($lines))
		throw new Exception ("instruction counter ${declared} does not match the contents");

	# The instructions.
	foreach ($lines as $line)
	{
		if (1 !== preg_match ('/^(?P<opcode>\d+) (?P<jt>\d+) (?P<jf>\d+) (?P<k>\d+)$/', $line, $m))
			throw new Exception ("malformed instruction line: '${line}'");
		# 16-bit, 8-bit, 8-bit, 32-bit; nCCN for LE, vCCV for LE.
		$ret .= pack ('vCCV', $m['opcode'], $m['jt'], $m['jf'], $m['k']);
	}
	return $ret;
}

function run_radare2 (array $stdout_stderr): array
{
	list ($ddd, $stderr) = $stdout_stderr;
	if ($stderr != '')
		return $stdout_stderr;
	try
	{
		return pipe_process
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
	}
	catch (Exception $e)
	{
		return array (NULL, $e->getMessage());
	}
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

function print_exec_block (string $header, array $stdout_stderr): void
{
	list ($stdout, $stderr) = $stdout_stderr;
	echo "<H3 class=subtitle>${header}</H3>\n";
	if ($stdout != '')
		echo '<PRE>' . htmlentities ($stdout) . '</PRE>';
	if ($stderr != '')
		echo '<PRE class=stderr>' . htmlentities ($stderr) . '</PRE>';
}

# Assume the stdout is a properly escaped HTML.
function print_exec_block_html (string $header, array $stdout_stderr): void
{
	list ($stdout, $stderr) = $stdout_stderr;
	echo "<H3 class=subtitle>${header}</H3>\n";
	if ($stdout != '')
		echo "<PRE>${stdout}</PRE>";
	if ($stderr != '')
		echo '<PRE class=stderr>' . htmlentities ($stderr) . '</PRE>';
}

if ($req_ver !== NULL && $req_dlt_name !== NULL && $req_filter !== NULL)
{
	limit_request_rate();
	$tcpdump_bin = $versions[$req_ver];
?>
				<H2 class=title>Packet-matching code (libpcap format)</H2>
				<DIV class=entry>
					<TABLE>
						<TR>
							<TD>
<?php
	print_exec_block
	(
		'before optimization',
		run_tcpdump (array ($tcpdump_bin, '-Od', '--', $req_filter), $req_dlt_name)
	);
?>
							</TD>
							<TD>
<?php
	print_exec_block
	(
		'after optimization',
		run_tcpdump (array ($tcpdump_bin, '-d', '--', $req_filter), $req_dlt_name)
	);
?>
							</TD>
						</TR>
					</TABLE>
				</DIV>

				<H2 class=title>Packet-matching code (Radare2 format)</H2>
				<DIV class=entry>
<?php
	print_exec_block_html
	(
		'before optimization',
		run_radare2 (run_tcpdump (array ($tcpdump_bin, '-Oddd', '--', $req_filter), $req_dlt_name))
	);
	print_exec_block_html
	(
		'after optimization',
		run_radare2 (run_tcpdump (array ($tcpdump_bin, '-ddd', '--', $req_filter), $req_dlt_name))
	);
?>
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
