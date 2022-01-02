#!/bin/sh

# This script generates (updates, but not commits) HTML pages for the
# www.tcpdump.org web-site.
#
# This script has been tested to work on the following systems:
#
# * Ubuntu Linux 18.04
# * Ubuntu Linux 20.04
# * Fedora Linux 30
# * Fedora Linux 32
# * Fedora Linux 34
# * Devuan Linux
# * FreeBSD 12
# * FreeBSD 13.0
# * OpenBSD 7.0
# * NetBSD 9.2
#

HTML_HEAD='htmlsrc/_html_head.html'
TOP_MENU='htmlsrc/_top_menu.html'
BODY_HEADER='htmlsrc/_body_header.html'
SIDEBAR='htmlsrc/_sidebar.html'
BODY_FOOTER='htmlsrc/_body_footer.html'

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
	season-of-docs)
		title='Season of Docs | '
		;;
	ci)
		title='Continuous integration | '
		;;
	LINKTYPE_*)
		title="$basename | "
		;;
	*)
		echo "Internal error: cannot tell page title for $1" >&2
		exit 10
	esac
	sed "s#%PAGE_TITLE%#${title}TCPDUMP \&amp; LIBPCAP#"
}

# Instead of using absolute hyperlinks in all .html files use relative ones
# and amend them for the files under linktypes/ at the generation time. The
# advantage of this is that the user can open the resulting static files
# from their filesystem in a browser and confirm everything works as expected
# before committing the changes and deploying them to the web-server.
rewrite_URLs()
{
	if [ "${1:?}" != "${1#linktypes/}" ]; then
		sed 's#\(<link href="\)\(images/\|style.css\)#\1../\2#' |
			sed 's#\(<img src="\)\(images/\)#\1../\2#' |
			sed 's#\(<a href="\)\(manpages/\)#\1../\2#' |
			sed 's#\(<a href="\)\([a-z_-]\+.html\)#\1../\2#'
	elif [ "$1" = autoindex_header.html ]; then
		sed 's#\(<img src="\)\(images/\)#\1/\2#' |
			sed 's#\(<a href="\)\([a-z]\+/\)#\1/\2#' |
			sed 's#\(<a href="\)\([a-z_-]\+.html\)#\1/\2#'
	else
		cat
	fi
}

highlight_top_menu()
{
	sed "s#<li><a href=\"${1:?}\">#<li class=\"current_page_item\"><a href=\"${1}\">#"
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
<!--
Created by       : Luis MartinGarcia <http://www.aldabaknocking.com>
Original design  : "Collaboration" by Free CSS Templates (later "TEMPLATED")
Original license : Creative Commons Attribution 2.5 License
-->
<html lang="en">

    <!-- HEAD -->
ENDOFTEXT
	cat "$HTML_HEAD"
	cat <<ENDOFTEXT
    <!-- END OF HTML HEAD -->

    <!-- BODY -->
    <body>

        <!-- TOP MENU -->
ENDOFTEXT
	cat "$TOP_MENU"
	cat <<ENDOFTEXT
        <!-- END OF TOP MENU -->

        <!-- PAGE HEADER -->
ENDOFTEXT
	cat "$BODY_HEADER"
	cat <<ENDOFTEXT
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
ENDOFTEXT
		cat "$SIDEBAR"
		cat <<ENDOFTEXT
            <!-- END OF LEFT SIDEBAR -->

ENDOFTEXT
	fi

	cat <<ENDOFTEXT
        </div>
        <!-- END OF PAGE CONTENTS -->

        <!-- FOOTER -->
ENDOFTEXT
	cat "$BODY_FOOTER"
	cat <<ENDOFTEXT
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
	if file_exists_in_repository "$f_out"; then
		file_differs_from_repository "$f_out" && echo "Warning: unsaved changes to $f_out were lost" >&2
	else
		echo "Warning: output file $f_out does not exist in git" >&2
	fi

	# None of the functions below read from $f_out, they only need to know
	# what the filename is.
	# shellcheck disable=SC2094
	print_html_page "$f_in" |
		substitute_page_title "$f_out" |
		highlight_top_menu "$f_out" |
		rewrite_URLs "$f_out" > "$f_out"

	# The pipeline is too long to fit into the "if" nicely.
	# shellcheck disable=SC2181
	if [ $? -ne 0 ]; then
		echo "Error: failed to overwrite output file $f_out" >&2
		return 1
	fi
	file_differs_from_repository "$f_out" && echo "Regenerated: $f_out"
	return 0
}

regenerate_pages()
{
	for f_in in htmlsrc/[!_]*.html htmlsrc/linktypes/*.html; do
		# Skip editor backup files.
		[ "$f_in" != "${f_in#\~}" ] && continue
		regenerate_page "$f_in" "${f_in#htmlsrc/}" || return 1
	done
	regenerate_page "$TOP_MENU" autoindex_header.html
}

command -v git >/dev/null 2>&1 || {
	echo "git must be installed to proceed" >&2
	exit 12
}
command -v sed >/dev/null 2>&1 || {
	echo "sed must be installed to proceed" >&2
	exit 13
}
regenerate_pages

