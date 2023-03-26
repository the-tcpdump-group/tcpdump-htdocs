#!/bin/sh

# Copyright (c) 2013 The TCPDUMP project
# All rights reserved.
# SPDX-License-Identifier: BSD-2-Clause
#
# Redistribution and use in source and binary forms, with or without
# modification, are permitted provided that the following conditions
# are met:
# 1. Redistributions of source code must retain the above copyright
#    notice, this list of conditions and the following disclaimer.
# 2. Redistributions in binary form must reproduce the above copyright
#    notice, this list of conditions and the following disclaimer in the
#    documentation and/or other materials provided with the distribution.
#
# THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
# "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
# LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
# FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
# COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
# INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
# BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
# LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
# CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
# LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
# ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
# POSSIBILITY OF SUCH DAMAGE.
#
# This script regenerates (updates but not commits) tcpdump, libpcap and
# tcpslice man pages for the www.tcpdump.org web-site. It is intended to be
# run in tcpdump-htdocs git repository clone with the source man pages
# available in ../tcpdump, ../libpcap and ../tcpslice git clones respectively.
#
# A number of man pages in the tcpdump and libpcap repositories require
# autotools processing, so if you have just updated any .in files there,
# remember to run ./config.status (or even ./configure for complex changes) in
# that working tree before running this script in this working tree.
#
# This script has been tested to work on the following Linux systems:
#
# * Debian 11
# * Ubuntu 22.04
#
# Please mind that the generated contents of the .txt files may change much
# more than respective source man pages, sometimes even when the source man
# pages didn't change at all. Specifically, the amount of whitespace and
# hyphenation may fluctuate for no obvious reason when this script runs on a
# different distribution or a different version of the same distribution or
# even on the same system as the previous time! Do not worry about it as long
# as the source man pages are the newest you can get from git.

MAN2HTML_PFX=/cgi-bin/man/man2html
WEBSITE_PFX=.

# Both Fedora and Ubuntu man2html versions prepend their output with a
# Content-type header. Only the Ubuntu version generates a document type
# declaration. For consistent and valid results replace everything before
# the <HTML> tag with an explicit hard-coded <!DOCTYPE ...> line.
stripContentTypeHeader()
{
	echo '<!DOCTYPE html>'
	sed -n '/^<HTML><HEAD><TITLE>/,$p'
}

# Most (but not all) of the generated HTML files have a short useless
# "Index" section near EOF.
stripIndexSection()
{
	case $(basename "${1:?}") in
	tcpdump.1|pcap.3pcap)
		cat
		;;
	*)
		sed '/^<A NAME="index">/,/^<HR>/d' | sed 's@<A HREF="#index">Index</A>@@'
		;;
	esac
}

printSedFile()
{
	# Fixup custom links.
	# Suppress some output difference between Fedora and Ubuntu versions of man2html.
	# Modernize a few HTML elements so the syntax conforms to the DOCTYPE; among other
	# things lose the now obsolete TT as it is within PRE anyway and convert named
	# anchors into IDs of the subsequent headers.
	# Convert file:// schema hyperlinks to plain text.
	# Idem mailto: HREFs.
	# Undo a very specific false positive case of man page detection.
	# Customize the page footer.
	cat <<-ENDOFFILE
		s@<A HREF="$MAN2HTML_PFX">Return to Main Contents</A>@<A HREF="$WEBSITE_PFX/">Return to Main Contents</A>@g
		s@<A HREF="$MAN2HTML_PFX">man2html</A>@man2html@g
		s@^<HTML><HEAD><TITLE>Manpage of @<HTML><HEAD><TITLE>Man page of @
		s@<HTML>@<HTML lang="en">@
		s@</HEAD><BODY>@<LINK rel="shortcut icon" href="../images/T-32x32.png" type="image/png">\n\0@
		s@</HEAD><BODY>@<LINK rel="stylesheet" type="text/css" href="../style.css">\n\0@
		s@</HEAD><BODY>@<meta charset="utf-8">\n</HEAD><BODY>@
		s@<DL COMPACT>@<DL>@g
		s@<TT>@@g
		s@</TT>@@g
		s@<H1>@<H1>Man page of @
		s@\(<A NAME="index">&nbsp;</A>\)\(<H2>Index</H2>\)@\1\n\2@
		/^<A NAME=.*>\$/ {N;s@<A NAME=\(".*"\)>&nbsp;</A>\n<H2>\(.*\)</H2>@<H2 id=\1>\2</H2>@}
		/^<A NAME=.*>\$/ {N;s@<A NAME=\(".*"\)>&nbsp;</A>\n<H3>\(.*\)</H3>@<H3 id=\1>\2</H3>@}
		s@<A HREF="file://\(.*\)">\(.*\)</A>@\2@g
		s@<A HREF="mailto:.*">\(.*\)</A>@\1@g
		s@<A HREF="/cgi-bin/man/man2html?1+[123]:[234]">\([123]:[234]\)</A>(1)@\1(1)@
		/^This document was created by\$/ {N;N;N;s@.*\n.*\n.*\nTime: \(.*\)\$@<H2>COLOPHON</H2>\nThis HTML man page was generated at \1\nfrom a source man page in "The Tcpdump Group" git repositories\nusing man2html and other tools.@}
	ENDOFFILE

	# Fixup links to local pages, part 1.
	printNon3PCAPFiles | while read -r f; do
		bn="${f##*/}"
		mansection="$(translateManSection "${bn##*.}")"
		manpage="${bn%.*}"
		echo "s@<A HREF=\"${MAN2HTML_PFX}?\($mansection\)+\($manpage\)\"@<A HREF=\"$WEBSITE_PFX/\\\\2.\\\\1.html\"@g"
	done

	# Fixup links to local pages, part 2.
	print3PCAPMap | while read -r mantopic manfile; do
		manfile="${WEBSITE_PFX}/${manfile:-$mantopic}.3pcap.html"
		# Two substitutions below make up for the new smartness added
		# in man2html-1.6-13.g.fc20.
		echo "s@<B><A HREF=\"$MAN2HTML_PFX?3PCAP+${mantopic}\">$mantopic</A></B>(3PCAP)@<B>$mantopic</B>(3PCAP)@g"
		echo "s@<A HREF=\"$MAN2HTML_PFX?3PCAP+${mantopic}\">$mantopic</A>(3PCAP)@$mantopic(3PCAP)@g"
		echo "s@$mantopic(3PCAP)@<A HREF='$manfile'>$mantopic</A>(3PCAP)@g"
		echo "s@<B>$mantopic</B>(3PCAP)@<A HREF='$manfile'><B>$mantopic</B></A>(3PCAP)@g"
	done

	# Hyperlinks to any other man pages are non-local, convert these to plain text.
	echo "s@<A HREF=\"$MAN2HTML_PFX?[1-9][a-zA-Z]\?+.\+\">\(.\+\)</A>@\\\\1@g"
}

printNon3PCAPFiles()
{
	cat <<-ENDOFLIST
		libpcap/pcap-filter.manmisc
		libpcap/pcap-linktype.manmisc
		libpcap/pcap-savefile.manfile
		libpcap/cbpf-savefile.manfile
		libpcap/pcap-tstamp.manmisc
		libpcap/rpcapd/rpcapd.manadmin
		libpcap/rpcapd/rpcapd-config.manfile
		libpcap/pcap-config.1
		tcpdump/tcpdump.1
		tcpslice/tcpslice.1
	ENDOFLIST
}

# There are several conventions, traditionally the web site uses Linux one.
translateManSection()
{
	case "${1:?}" in
	manfile)
		echo 5
		;;
	manmisc)
		echo 7
		;;
	manadmin)
		echo 8
		;;
	*)
		echo "$1"
		;;
	esac
}

print3PCAPMap()
{
	cat <<-ENDOFLIST
		pcap
		pcap_activate
		pcap_breakloop
		pcap_can_set_rfmon
		pcap_close
		pcap_compile
		pcap_create
		pcap_datalink
		pcap_datalink_name_to_val
		pcap_datalink_val_to_description				pcap_datalink_val_to_name
		pcap_datalink_val_to_description_or_dlt				pcap_datalink_val_to_name
		pcap_datalink_val_to_name
		pcap_dispatch							pcap_loop
		pcap_dump
		pcap_dump_close
		pcap_dump_file
		pcap_dump_flush
		pcap_dump_fopen							pcap_dump_open
		pcap_dump_ftell
		pcap_dump_ftell64						pcap_dump_ftell
		pcap_dump_open
		pcap_dump_open_append						pcap_dump_open
		pcap_file
		pcap_fileno
		pcap_findalldevs
		pcap_fopen_offline						pcap_open_offline
		pcap_fopen_offline_with_tstamp_precision			pcap_open_offline
		pcap_freealldevs						pcap_findalldevs
		pcap_freecode
		pcap_free_datalinks						pcap_list_datalinks
		pcap_free_tstamp_types						pcap_list_tstamp_types
		pcap_get_required_select_timeout
		pcap_geterr
		pcap_getnonblock						pcap_setnonblock
		pcap_get_selectable_fd
		pcap_get_tstamp_precision
		pcap_init
		pcap_inject
		pcap_is_swapped
		pcap_lib_version
		pcap_list_datalinks
		pcap_list_tstamp_types
		pcap_lookupdev
		pcap_lookupnet
		pcap_loop
		pcap_major_version
		pcap_minor_version						pcap_major_version
		pcap_next_ex
		pcap_next							pcap_next_ex
		pcap_offline_filter
		pcap_open_dead
		pcap_open_dead_with_tstamp_precision				pcap_open_dead
		pcap_open_live
		pcap_open_offline
		pcap_open_offline_with_tstamp_precision				pcap_open_offline
		pcap_perror							pcap_geterr
		pcap_sendpacket							pcap_inject
		pcap_set_buffer_size
		pcap_set_datalink
		pcap_setdirection
		pcap_setfilter
		pcap_set_immediate_mode
		pcap_setnonblock
		pcap_set_promisc
		pcap_set_protocol_linux
		pcap_set_rfmon
		pcap_set_snaplen
		pcap_set_timeout
		pcap_set_tstamp_precision
		pcap_set_tstamp_type
		pcap_snapshot
		pcap_stats
		pcap_statustostr
		pcap_strerror
		pcap_tstamp_type_name_to_val
		pcap_tstamp_type_val_to_description				pcap_tstamp_type_val_to_name
		pcap_tstamp_type_val_to_name
	ENDOFLIST
}

detectProject()
{
	project_dir=$(dirname "${1:?}")
	project_name=$(basename "$project_dir")
	[ "$project_name" = rpcapd ] && project_dir="$project_dir/.."
	project_version=$(cat "$project_dir/VERSION")
}

BOILERPLATE_BODY='Your system may have a different version installed, possibly with some
local modifications.  To achieve the best results, please make sure this
version of this man page suits your needs.  If necessary, try to look for
a different version on this web site or in the man pages available in your
installation.'

printHTMLBoilerplate()
{
	detectProject "${1:?}"
	cat <<-ENDOFTEXT
		<DIV class=version_boilerplate>
		<H4>This man page documents ${project_name} version ${project_version}.</H4>
		$BOILERPLATE_BODY
		</DIV>
	ENDOFTEXT
}

insertHTMLBoilerplate()
{
	HBTMP=$(mktemp --tmpdir html_boilerplate.XXXXXX)
	printHTMLBoilerplate "${1:?}" >"$HBTMP"
	sed -E "/<A HREF=\".+\">Return to Main Contents<\/A><HR>/r $HBTMP"
	rm -f "$HBTMP"
}

# Starting from the description section (which usually follows the synopsis,
# which uses bold font differently), convert most freestanding bold text to
# inline code.
definitelyBoldToCode()
{
	# First convert everything, then undo specific substitutions that do
	# not look well.  This seems to be the easiest approach since sed
	# regexps do not support negative matching and this function is the
	# only source of <CODE> tags in the page.
	sed -E '/^<H2 .+>DESCRIPTION/,$s@^<DT><B>([^<>]+)</B>@<DT><CODE>\1</CODE>@' |
		sed -E '/^<H2 .+>DESCRIPTION/,$s@^(non-<|\(<|<)B(>[^<>]+</)B(>[,.;)s]?)$@\1CODE\2CODE\3@' |
		sed -E 's@<CODE>(NOT|not|0|1|-1|If|This .+|has been .+)</CODE>@<B>\1</B>@'
}

# The substitution does not produce good results for every page, so do not
# modify pages that for one or another reason would not look good afterwards.
maybeBoldToCode()
{
	mbtc_bn=$(basename "${1:?}")
	mbtc_bn=${mbtc_bn%.*}
	mbtc_filter='definitelyBoldToCode'
	while read -r mbtc_fn; do
		if [ "$mbtc_fn" = "$mbtc_bn" ]; then
			mbtc_filter='cat'
			break
		fi
	done <<-EOF
		pcap
		pcap-filter
		rpcapd-config
		tcpdump
	EOF
	"$mbtc_filter"
}

in_repository()
{
	git cat-file -e HEAD:"${1:?}" >/dev/null 2>&1
}

produceHTML()
{
	infile=${1:?}
	html_sedfile="${2:?}"
	outfile=${3:?}
	[ -s "$infile" ] || {
		echo "Skipped: $infile, which does not exist or is empty"
		return
	}
	# A possible alternative: mandoc -T html $infile > $outfile
	man2html -M $MAN2HTML_PFX "$infile" | stripContentTypeHeader |
		insertHTMLBoilerplate "$infile" |
		stripIndexSection "$infile" |
		sed --file="$html_sedfile" |
		maybeBoldToCode "$infile" >"$outfile"
	# If the output file is git-tracked and the new revision is different in
	# timestamp only, discard the new revision.
	in_repository "$outfile" || {
		echo "Generated, but not in the repository: $outfile"
		return
	}
	git diff "$outfile" | tail --lines +5 | grep -E '^[-+]' | grep -E -q -v '^[-+]This HTML man page was generated at ' || {
		git checkout --quiet "$outfile"
		return
	}
	echo "Updated: $outfile"
}

printTXTBoilerplate()
{
	detectProject "${1:?}"
	echo '+----------------------------------------------------------------------------+'
	while read -r line; do
		printf '| %-74s |\n' "$line"
	done <<-ENDOFTEXT

		This man page documents ${project_name} version ${project_version}.

		$BOILERPLATE_BODY

	ENDOFTEXT
	echo '+----------------------------------------------------------------------------+'
	echo
}

get_columns()
{
	stty size | cut -d' ' -f2
}

produceTXT()
{
	infile=${1:?}
	outfile=${2:?}
	[ -s "$infile" ] || {
		echo "Skipped: $infile, which does not exist or is empty"
		return
	}
	[ "$(get_columns)" -eq 80 ] || {
		echo 'ERROR: the terminal width has reset, possibly after a focus loss' >&2
		exit 2
	}
	printTXTBoilerplate "$infile" > "$outfile"
	man -E ascii "$infile" >> "$outfile"
	in_repository "$outfile" || {
		echo "Generated, but not in the repository: $outfile"
		return
	}
	git diff "$outfile" | grep -E -q '^[-+]' && echo "Updated: $outfile"
}

known3PCAPFile()
{
	basename=$(basename "${1:?}" .3pcap)
	print3PCAPMap | while read -r mantopic manfile; do
		if [ "${manfile:-$mantopic}" = "$basename" ]; then
			# Cannot just return 0 or 1 because the while end of
			# the pipe may be in a sub-shell.
			echo 'yes'
			return
		fi
	done
	echo 'no'
}

updateOutputFiles()
{
	command -v man2html >/dev/null 2>&1 || {
		echo "man2html must be installed to proceed" >&2
		exit 1
	}

	# The .txt version of a man page assumes an 80 columns wide terminal,
	# which used to be the default in PC text mode console, xterm etc.
	# Use stty because $COLUMNS doesn't always work.
	cols=$(get_columns)
	[ "$cols" -ne 80 ] && stty columns 80

	sedfile=$(mktemp --tmpdir manpages_sedfile.XXXXXX)
	printSedFile > "$sedfile"

	# A while loop would not work because of the stty invocation within.
	for f in $(printNon3PCAPFiles); do
		ofb="${f##*/}"
		ofb="${ofb%.*}.$(translateManSection "${ofb##*.}")"
		produceTXT ../"$f" "manpages/${ofb}.txt"
		produceHTML ../"$f" "$sedfile" "manpages/${ofb}.html"
	done

	for f in ../libpcap/*.3pcap; do
		[ "$(known3PCAPFile "$f")" = 'no' ] && echo "WARNING: file $f is not in the 3PCAP map"
		produceTXT "$f" "manpages/$(basename "$f").txt"
		produceHTML "$f" "$sedfile" "manpages/$(basename "$f").html"
	done

	rm -f "$sedfile"
	[ "$cols" -ne 80 ] && stty columns "$cols"
	return 0
}

updateOutputFiles
