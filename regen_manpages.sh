#!/bin/sh

# This script regenerates (updates but not commits) tcpdump and libpcap manpages
# for the www.tcpdump.org web-site. It is intended to be run in tcpdump-htdocs
# git repository with the original manpages available in ../tcpdump and
# ../libpcap. It works on a Linux system and may work on other systems as well.

MAN2HTML_PFX=/cgi-bin/man/man2html
WEBSITE_PFX=/manpages

# man2html prepends its HTML output with a Content-type header.
function stripContentTypeHeader
{
	# FIXME: use sed
	tail --lines +3
}

# Convert links to non-local pages to plain text, fixup links to local pages and
# "main contents".
function conditionAnchors
{
     sed "s@<A HREF=\"$MAN2HTML_PFX\">Return to Main Contents</A>@<A HREF=\".\">Return to Main Contents</A>@g" | \
     sed "s@<A HREF=\"$MAN2HTML_PFX\">man2html</A>@man2html@g" | \
     sed "s@<A HREF=\"$MAN2HTML_PFX?3N+ethers\">ethers</A>@ethers@g" | \
     sed "s@<A HREF=\"$MAN2HTML_PFX?4P+tcp\">tcp</A>@tcp@g" | \
     sed "s@<A HREF=\"$MAN2HTML_PFX?4P+udp\">udp</A>@udp@g" | \
     sed "s@<A HREF=\"$MAN2HTML_PFX?4P+ip\">ip</A>@ip@g" | \
     sed "s@<A HREF=\"$MAN2HTML_PFX?4+pf\">pf</A>@pf@g" | \
     sed "s@<A HREF=\"$MAN2HTML_PFX?8+pfconfig\">pfconfig</A>@pfconfig@g" | \
     sed "s@<A HREF=\"$MAN2HTML_PFX?2+select\">select</A>@select@g" | \
     sed "s@<A HREF=\"$MAN2HTML_PFX?2+poll\">poll</A>@poll@g" | \
     sed "s@<A HREF=\"$MAN2HTML_PFX?1+autoconf\">autoconf</A>@autoconf@g" | \
     sed "s@<A HREF=\"$MAN2HTML_PFX?1M+usermod\">usermod</A>@usermod@g" | \
     sed "s@<A HREF=\"$MAN2HTML_PFX?1+tcpslice\">tcpslice</A>@tcpslice@g" | \
     sed "s@<A HREF=\"$MAN2HTML_PFX?3+strerror\">strerror</A>@strerror@g" | \
     sed "s@<A HREF=\"$MAN2HTML_PFX?1+kill\">kill</A>@kill@g" | \
     sed "s@<A HREF=\"$MAN2HTML_PFX?1+stty\">stty</A>@stty@g" | \
     sed "s@<A HREF=\"$MAN2HTML_PFX?1+ps\">ps</A>@ps@g" | \
     sed "s@<A HREF=\"$MAN2HTML_PFX?3+strftime\">strftime</A>@strftime@g" | \
     sed "s@<A HREF=\"$MAN2HTML_PFX?4+bpf\">bpf</A>@bpf@g" | \
     sed "s@<A HREF=\"$MAN2HTML_PFX?4P+nit\">nit</A>@nit@g" | \
     sed "s@<A HREF=\"$MAN2HTML_PFX?7+pcap-linktype\"@<A HREF=\"$WEBSITE_PFX/pcap-linktype.7.html\"@g" | \
     sed "s@<A HREF=\"$MAN2HTML_PFX?7+pcap-tstamp\"@<A HREF=\"$WEBSITE_PFX/pcap-tstamp.7.html\"@g" | \
     sed "s@<A HREF=\"$MAN2HTML_PFX?7+pcap-filter\"@<A HREF=\"$WEBSITE_PFX/pcap-filter.7.html\"@g" | \
     sed "s@<A HREF=\"$MAN2HTML_PFX?5+pcap-savefile\"@<A HREF=\"$WEBSITE_PFX/pcap-savefile.5.html\"@g" | \
     sed "s@<A HREF=\"$MAN2HTML_PFX?1+tcpdump\"@<A HREF=\"/tcpdump_man.html\"@g"
}

function produceHTML
{
	local infile=${1:?argument required}
	local outfile=${2:?argument required}
	man2html -M $MAN2HTML_PFX $infile | stripContentTypeHeader | conditionAnchors > $outfile
	# If the output file is git-tracked and the new revision is different in
	# timestamp only, discard the new revision.
	git show $outfile >/dev/null 2>&1 || {
		echo "Updated but not in repository: $outfile"
		return
	}
	git diff $outfile | tail --lines +5 | egrep -q '^[-+][^T][^i][^m][^e][^:][^ ]' || {
		git checkout $outfile
		return
	}
	echo "Updated: $outfile"
}

function produceTXT
{
	local infile=${1:?argument required}
	local outfile=${2:?argument required}
	man -E ascii $infile > $outfile
}

# $COLUMNS doesn't always work
COLS=`stty size | cut -d' ' -f2`
if [ "$COLS" != "80" ]; then
	echo "This terminal must be 80 ($COLS right now) columns wide"
	exit 1
fi

produceHTML ../tcpdump/tcpdump.1 tcpdump_man.html
produceHTML ../libpcap/pcap.3pcap pcap3_man.html

produceTXT ../libpcap/pcap-filter.manmisc manpages/pcap-filter.7.txt
produceTXT ../libpcap/pcap-linktype.manmisc manpages/pcap-linktype.7.txt
produceTXT ../libpcap/pcap-savefile.manfile manpages/pcap-savefile.5.txt
produceTXT ../libpcap/pcap-tstamp.manmisc manpages/pcap-tstamp.7.txt
    
produceHTML ../libpcap/pcap-filter.manmisc manpages/pcap-filter.7.html
produceHTML ../libpcap/pcap-linktype.manmisc manpages/pcap-linktype.7.html
produceHTML ../libpcap/pcap-savefile.manfile manpages/pcap-savefile.5.html
produceHTML ../libpcap/pcap-tstamp.manmisc manpages/pcap-tstamp.7.html

for f in ../libpcap/*.3pcap ../libpcap/pcap-config.1; do
	produceTXT $f manpages/`basename $f`.txt
	produceHTML $f manpages/`basename $f`.html
done
