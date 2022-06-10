<?php

define ('HEADER_FILE', '../autoindex_header.html');
define ('FOOTER_FILE', '../autoindex_footer.html');
define ('PAGE_TITLE', 'BPF Exam');
define ('VER_INPUT_NAME', 'ver');
define ('DLT_INPUT_NAME', 'dlt');
define ('EXPR_INPUT_NAME', 'filter');
define ('SUBMIT_INPUT_NAME', 'examine');
define ('DEFAULT_VER', '1.10.1');
define ('DEFAULT_DLT', 'EN10MB');
define ('DEFAULT_FILTER', 'icmp or udp port 53 or bootpc');
define ('TIMESTAMP_FILE', '/tmp/bpf_timestamp.txt');
# Enforce an RPS limit for requests that submit the form, as these spawn
# external processes, which together take a while (0.5s to 1.0s) to complete.
define ('MAX_RPS_LIMIT', 1.0);
define ('RADARE2_BIN', '/usr/bin/r2');
define ('DOT_BIN', '/usr/bin/dot');

# filtertest, where specified, was copied from libpcap built
# using "./configure --enable-optimizer-dbg"
$versions = array
(
	'master' => array
	(
		'descr' => 'git master snapshot',
		'tcpdump' => '/usr/local/bin/tcpdump-libpcap-git',
		'filtertest' => '/usr/local/bin/filtertest-git-optdebug',
	),
	'1.10.1' => array
	(
		'tcpdump' => '/usr/local/bin/tcpdump-libpcap-1.10.1',
		'filtertest' => '/usr/local/bin/filtertest-1.10.1-optdebug',
	),
	'1.9.1' => array
	(
		'tcpdump' => '/usr/local/bin/tcpdump-libpcap-1.9.1',
		'filtertest' => '/usr/local/bin/filtertest-1.9.1-optdebug',
	),
	'1.8.1' => array
	(
		'tcpdump' => '/usr/local/bin/tcpdump-libpcap-1.8.1',
	),
	'1.7.4' => array
	(
		'tcpdump' => '/usr/local/bin/tcpdump-libpcap-1.7.4',
	),
	'1.6.2' => array
	(
		'tcpdump' => '/usr/local/bin/tcpdump-libpcap-1.6.2',
	),
	'1.5.3' => array
	(
		'tcpdump' => '/usr/local/bin/tcpdump-libpcap-1.5.3',
	),
	# libpcap 1.9.1 in Ubuntu 20.04 (/usr/sbin/tcpdump, v4.9.3)
	# libpcap 1.10.0 in Debian 11 (/usr/bin/tcpdump, v4.99.0)
	'random' => array
	(
		'descr' => 'random (OS default)',
		'tcpdump' => 'tcpdump',
	),
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

# Throw this to indicate that the message has already been HTML-escaped
# (Radare2 escapes both stdout and stderr in HTML output mode).
class EscapedException extends Exception
{
}

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

function read_file (string $filename): string
{
	if (FALSE === $ret = @file_get_contents ($filename))
		fail (500);
	return $ret;
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
echo preg_replace
(
	sprintf ('@<li>(<a href="%s/">)@', dirname ($_SERVER['SCRIPT_NAME'])),
	'<li class=current_page_item>$1',
	read_file (HEADER_FILE)
);
?>
		<DIV id=page>
			<DIV class=post>
				<H2 class=title>Input parameters</H2>
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
foreach ($versions as $ver => $vdata)
{
	$optlabel = array_fetch ($vdata, 'descr', $ver);
	if (array_key_exists ('filtertest', $vdata))
		$optlabel .= ' (with optimizer debugging)';
	echo "<OPTION value='${ver}'";
	if ($ver == ($req_ver ?? DEFAULT_VER))
		echo ' selected';
	echo ">${optlabel}</OPTION>\n";
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
		throw new Exception ($stderr);
	return $stdout;
}

function run_filtertest ($filtertest_bin, $dlt_name, $filter): string
{
	list ($stdout, $stderr) = pipe_process
	(
		array
		(
			$filtertest_bin,
			'-g',
			$dlt_name,
			'--',
			$filter
		),
		''
	);
	if ($stderr != '')
		throw new Exception ($stderr);
	return $stdout;
}

define ('S_SKIP', 1);
define ('S_TITLE', 2);
define ('S_DEF', 3);
function detect_graphs (string $text): array
{
	$ret = array();
	$i = 1;
	$state = S_SKIP;
	foreach (explode ("\n", $text) as $line)
	{
		switch ($state)
		{
		case S_SKIP:
			if (0 === strpos ($line, 'after ') || 0 === strpos ($line, 'opt_loop('))
			{
				$title = $line;
				$state = S_TITLE;
			}
			break;
		case S_TITLE:
			if ($line !== 'digraph BPF {')
				throw new Exception ("FSM error: unexpected line ${line}");
			$deflines = array ($line);
			$state = S_DEF;
			break;
		case S_DEF:
			$deflines[] = $line;
			if ($line == '}')
			{
				$ret["${i}. ${title}"] = implode ("\n", $deflines);
				$i++;
				$state = S_SKIP;
			}
			break;
		default:
			throw new Exception ('FSM error: invalid state');
		}
	}
	if ($state !== S_SKIP)
		throw new Exception ('FSM error: unexpected end of input');
	return $ret;
}

function restyle_libpcap_graph (string $graphdef): string
{
	# Do not change the default edge type to "ortho", it does not look well.
	# Undo explicit node shapes and set the default the same as in Radare2.
	$ret = preg_replace ('/^([[:space:]]*block.+ \[)shape=ellipse, /m', '\1', $graphdef);
	$ret = preg_replace ('/^(digraph BPF {)$/m', "\\1\n        node [shape=box fontname=\"Courier\"];", $ret);
	# Colourize the edges same as in Radare2, but keep the labels.
	$ret = preg_replace ('/^([[:space:]]*.+ -> .+ \[label="T")(\])/m', '\1 color="#13a10e"\2', $ret);
	$ret = preg_replace ('/^([[:space:]]*.+ -> .+ \[label="F")(\])/m', '\1 color="#c50f1f"\2', $ret);
	# Align all node label text to the left.
	$ret = preg_replace ('/\\\\n/', '\\\\l', $ret);
	$ret = preg_replace ('/^([[:space:]]*block.+ \[.+ label="[^"]+)(")/m', '\1\\\\l\2', $ret);
	# Let Graphviz decide where to place incoming edges arrows, so they don't jam as much.
	$ret = preg_replace ('/^([[:space:]].+:s. -> .+):n /m', '\1 ', $ret);
	return $ret;
}

function restyle_r2_graph (string $graphdef): string
{
	# Drop the default background.
	$ret = preg_replace ('/^([[:space:]]*graph \[)bgcolor=azure /m', '\1', $graphdef);
	# Label the coloured true/false edges for consistency with libpcap format.
	$ret = preg_replace ('/^([[:space:]]*.+ -> .+ \[color="#13a10e")(];)/m', '\1 xlabel="T"\2', $ret);
	$ret = preg_replace ('/^([[:space:]]*.+ -> .+ \[color="#c50f1f")(];)/m', '\1 xlabel="F"\2', $ret);
	return $ret;
}

function run_dot (string $graphdef): string
{
	list ($stdout, $stderr) = pipe_process (array (DOT_BIN, '-Tsvg'), $graphdef);
	if ($stderr != '')
		throw new Exception ($stderr);
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

function r2_disasm (string $bytecode): string
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
		$bytecode
	);
	if ($stderr != '')
		throw new EscapedException ($stderr);
	return $stdout;
}

function r2_graph (string $bytecode): string
{
	list ($stdout, $stderr) = pipe_process
	(
		array
		(
			RADARE2_BIN,
			'-q',
			'-a', 'bpf.mr', # Not the Capstone BPF engine.
			'-e', 'anal.cc=reg', # Squelch a warning.
			'-e', 'asm.cmt.col=0', # Condense horizontally.
			'-c', 'af',
			'-c', 'agfd',
			'stdin://'
		),
		$bytecode
	);
	if ($stderr != '')
		throw new EscapedException ($stderr);
	return $stdout;
}

function limit_request_rate(): void
{
	if (FALSE === $f = fopen (TIMESTAMP_FILE, 'c+'))
		fail (500);
	if (FALSE === flock ($f, LOCK_EX))
		fail (500);
	if (FALSE === $prev_txt = stream_get_contents ($f, 256))
		fail (500);
	if ($prev_txt == '')
		$prev_txt = '0';
	$prev = (float) $prev_txt;
	$now = gettimeofday (TRUE);
	if ($prev > $now)
		fail (500);
	if ($now - $prev < 1.0 / MAX_RPS_LIMIT)
		fail (429);
	# It is certainly fine to proceed.  Update the timestamp and release
	# the lock early so any concurrent requests can bounce without a delay.
	if (FALSE === ftruncate ($f, 0))
		fail (500);
	if (FALSE === rewind ($f))
		fail (500);
	if (FALSE === fwrite ($f, $now))
		fail (500);
	if (FALSE === fclose ($f))
		fail (500);
}

function inline_img (string $data): string
{
	return '<IMG src="data:image/svg+xml;base64,' . base64_encode ($data) . '" width="100%">';
}

function process_request (array $vdata, string $req_dlt_name, string $req_filter): void
{
	$libpcap_before =
		$libpcap_after =
		$r2_disasm_before =
		$r2_graph_before =
		$r2_disasm_after =
		$r2_graph_after =
		'(N/A)';
	$optimizer_steps = NULL;
	try
	{
		$libpcap_before = htmlentities (run_tcpdump (array ($vdata['tcpdump'], '-Od', '--', $req_filter), $req_dlt_name));
		$bytecode_before = bpf_ddd2bin (run_tcpdump (array ($vdata['tcpdump'], '-Oddd', '--', $req_filter), $req_dlt_name));
		$r2_disasm_before = r2_disasm ($bytecode_before);
		$r2_graph_before = inline_img (run_dot (restyle_r2_graph (r2_graph ($bytecode_before))));
		$libpcap_after = htmlentities (run_tcpdump (array ($vdata['tcpdump'], '-d', '--', $req_filter), $req_dlt_name));
		$bytecode_after = bpf_ddd2bin (run_tcpdump (array ($vdata['tcpdump'], '-ddd', '--', $req_filter), $req_dlt_name));
		$r2_disasm_after = r2_disasm ($bytecode_after);
		$r2_graph_after = inline_img (run_dot (restyle_r2_graph (r2_graph ($bytecode_after))));
		if (array_key_exists ('filtertest', $vdata))
		{
			$graphs = detect_graphs (run_filtertest ($vdata['filtertest'], $req_dlt_name, $req_filter));
			if (count ($graphs))
			{
				$optimizer_steps = '';
				foreach ($graphs as $title => $deftext)
				{
					$optimizer_steps .= '<H3 class=subtitle>' . htmlentities ($title) . "</H3>\n";
					$optimizer_steps .= inline_img (run_dot (restyle_libpcap_graph ($deftext)));
				}
			}
		}
	}
	finally
	{
		echo <<<"ENDOFTEXT"
		<H2 class=title>Final packet-matching code</H2>
		<DIV class=entry>
		<TABLE>
			<TR>
				<TD>
					<H3 class=subtitle>without optimization (libpcap dump)</H3>
					<PRE>${libpcap_before}</PRE>
				</TD>
				<TD>
					<H3 class=subtitle>with optimization (libpcap dump)</H3>
					<PRE>${libpcap_after}</PRE>
				</TD>
			</TR>
			<TR>
				<TD colspan=2>
					<H3 class=subtitle>without optimization (Radare2 bytecode disassembly)</H3>
					<PRE>${r2_disasm_before}</PRE>
				</TD>
			</TR>
			<TR>
				<TD colspan=2>
					<H3 class=subtitle>with optimization (Radare2 bytecode disassembly)</H3>
					<PRE>${r2_disasm_after}</PRE>
				</TD>
			</TR>
		</TABLE>
		</DIV>

		<H2 class=title>Final control flow graph (Radare2)</H2>
		<DIV class=entry>
			<H3 class=subtitle>without optimization</H3>
			<P>
				${r2_graph_before}
			</P>
			<H3 class=subtitle>with optimization</H3>
			<P>
				${r2_graph_after}
			</P>
		</DIV>
ENDOFTEXT;

		if ($optimizer_steps !== NULL)
			echo <<<"ENDOFTEXT"
		<H2 class=title>Optimization step-by-step (libpcap)</H2>
		<DIV class=entry>
			${optimizer_steps}
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
	catch (EscapedException $e)
	{
		echo '<PRE class=stderr>' . $e->getMessage() . '</PRE>';
	}
	catch (Exception $e)
	{
		echo '<PRE class=stderr>' . htmlentities ($e->getMessage()) . '</PRE>';
	}
}
?>
			</DIV>
		</DIV>
<?php
ob_end_flush();
echo read_file (FOOTER_FILE);
?>
	</BODY>
</HTML>
