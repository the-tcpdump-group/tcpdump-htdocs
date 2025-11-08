#!/bin/sh -e

# Copyright (c) 2020 The TCPDUMP project
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
# This script generates (updates, but not commits) HTML pages for the
# www.tcpdump.org web-site.
#
# This script has been tested to work on the following systems:
#
# * FreeBSD
# * GNU/Linux
# * Haiku
# * illumos
# * NetBSD
# * OpenBSD
# * Solaris 11
#

HTML_HEAD='htmlsrc/_html_head.html'
TOP_MENU='htmlsrc/_top_menu.html'
BODY_HEADER='htmlsrc/_body_header.html'
SIDEBAR='htmlsrc/_sidebar.html'
BODY_FOOTER='htmlsrc/_body_footer.html'
: "${SED:=sed}"

substitute_page_title()
{
	basename=$(basename "${1:?}" .html)

	case "$basename" in
	autoindex_header)
		cat
		return
		;;
	security)
		title='Security | '
		;;
	faq)
		title='FAQ | '
		;;
	index)
		title='Home | '
		;;
	linktypes)
		title='Link-layer header types | '
		;;
	old_releases)
		title='Old releases | '
		;;
	pcap)
		title='Programming with pcap | '
		;;
	wpcap)
		title='Information on WinPcap and WinDump | '
		;;
	libpcap-module-HOWTO)
		title='How to write a libpcap module | '
		;;
	related)
		title='See also | '
		;;
	broadcom-switch-tag)
		title='Broadcom switch tag | '
		;;
	marvell-switch-tag)
		title='Marvell switch tag | '
		;;
	netanalyzer-header)
		title='netANALYZER header | '
		;;
	season-of-docs)
		title='Season of Docs | '
		;;
	ci)
		title='Continuous integration | '
		;;
	thanks)
		title='Thank you! | '
		;;
	bpfisa)
		title='BPF ISA | '
		;;
	LINKTYPE_*)
		title="$basename | "
		;;
	*)
		echo "Internal error: cannot tell page title for $1" >&2
		exit 10
	esac
	"$SED" "s#%PAGE_TITLE%#${title}TCPDUMP \&amp; LIBPCAP#"
}

# Instead of using absolute hyperlinks in all .html files use relative ones
# and amend them for the files under linktypes/ at the generation time. The
# advantage of this is that the user can open the resulting static files
# from their filesystem in a browser and confirm everything works as expected
# before committing the changes and deploying them to the web-server.
rewrite_URLs()
{
	if [ "${1:?}" != "${1#linktypes/}" ]; then
		"$SED" 's#\(<link href="\)\(images/\)#\1../\2#' |
			"$SED" 's#\(<link href="\)\(style.css\)#\1../\2#' |
			"$SED" 's#\(<img src="\)\(images/\)#\1../\2#' |
			"$SED" 's#\(<a href="\)\(manpages/\)#\1../\2#' |
			"$SED" 's#\(<a href="\)\(bpfexam/\)#\1../\2#' |
			"$SED" 's#\(<a href="\)\([a-z_-]\{1,\}.html\)#\1../\2#'
	elif [ "$1" = autoindex_header.html ]; then
		"$SED" 's#\(<img src="\)\(images/\)#\1/\2#' |
			"$SED" 's#\(<a href="\)\([a-z]\{1,\}/\)#\1/\2#' |
			"$SED" 's#\(<a href="\)\([a-z_-]\{1,\}.html\)#\1/\2#'
	else
		cat
	fi
}

highlight_top_menu()
{
	"$SED" "s#<li><a href=\"${1:?}\">#<li class=\"current_page_item\"><a href=\"${1}\">#"
}

print_html_page()
{
	infile="${1:?}"
	case $(basename "$infile" .html) in
	_top_menu)
		cat "$infile" "$BODY_HEADER"
		return
		;;
	index)
		show_sidebar='yes'
		;;
	*)
		show_sidebar='no'
		;;
	esac

	cat <<ENDOFTEXT
<!DOCTYPE html>
<html lang="en">

    <!-- HEAD -->
$(cat "$HTML_HEAD")
    <!-- END OF HTML HEAD -->

    <!-- BODY -->
    <body>

        <!-- TOP MENU -->
$(cat "$TOP_MENU")
        <!-- END OF TOP MENU -->

        <!-- PAGE HEADER -->
$(cat "$BODY_HEADER")
        <!-- END OF PAGE HEADER -->

        <!-- PAGE CONTENTS -->
        <div id="page">

ENDOFTEXT
	if [ "$show_sidebar" = 'yes' ]; then
		cat <<ENDOFTEXT
            <!-- RIGHT HAND SIDE PAGE CONTENTS  -->
ENDOFTEXT
	fi
	cat "$infile"
	if [ "$show_sidebar" = 'yes' ]; then
		cat <<ENDOFTEXT
            <!-- RIGHT HAND SIDE PAGE CONTENTS -->

ENDOFTEXT
	fi

	if [ "$show_sidebar" = 'yes' ]; then
		cat <<ENDOFTEXT
            <!-- LEFT SIDEBAR -->
$(cat "$SIDEBAR")
            <!-- END OF LEFT SIDEBAR -->

ENDOFTEXT
	fi

	cat <<ENDOFTEXT
        </div>
        <!-- END OF PAGE CONTENTS -->

        <!-- FOOTER -->
$(cat "$BODY_FOOTER")
        <!-- END OF FOOTER -->

    </body>
    <!-- END OF HTML BODY -->
</html>
ENDOFTEXT
}

file_exists_in_repository()
{
	git cat-file -e HEAD:"${1:?}" 2>/dev/null
}

file_differs_from_repository()
{
	! git diff --quiet -- "${1:?}"
}

regenerate_page()
{
	f_in=${1:?}
	f_out=${2:?}
	file_exists_in_repository "$f_in" || echo "Warning: input file $f_in does not exist in git" >&2
	file_exists_in_repository "$f_out" || echo "Warning: output file $f_out does not exist in git" >&2

	# None of the functions below read from $f_out, they only need to know
	# what the filename is.
	# shellcheck disable=SC2094
	print_html_page "$f_in" |
		substitute_page_title "$f_out" |
		highlight_top_menu "$f_out" |
		rewrite_URLs "$f_out" >"${f_tmp:?}"

	# The pipeline is too long to fit into the "if" nicely.
	# shellcheck disable=SC2181
	if [ $? -ne 0 ]; then
		echo "Error: failed to overwrite output file $f_out" >&2
		return 1
	fi
	if ! cmp -s "$f_tmp" "$f_out"; then
		cp "$f_tmp" "$f_out"
		echo "Updated: $f_out"
	fi
	return 0
}

regenerate_pages()
{
	f_tmp=$(mktemp -t regen_html_pages.XXXXXX)
	for f_in in htmlsrc/[!_]*.html htmlsrc/linktypes/*.html; do
		# Skip editor backup files.
		[ "$f_in" != "${f_in#\~}" ] && continue
		regenerate_page "$f_in" "${f_in#htmlsrc/}" || return 1
	done
	regenerate_page "$TOP_MENU" autoindex_header.html
	rm -f "$f_tmp"
}

command -v git >/dev/null 2>&1 || {
	echo "git must be installed to proceed" >&2
	exit 12
}
command -v "$SED" >/dev/null 2>&1 || {
	echo "sed must be installed to proceed" >&2
	exit 13
}
regenerate_pages

