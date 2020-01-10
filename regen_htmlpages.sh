#!/bin/sh

# This script generates (updates, but not commits) HTML pages for the
# www.tcpdump.org web-site.
#
# This script has been tested to work on the following systems:
#
# * Ubuntu Linux 18.04
# * Fedora Linux 30
#

readonly HTML_HEAD='htmlsrc/_html_head.html'
readonly TOP_MENU='htmlsrc/_top_menu.html'
readonly BODY_HEADER='htmlsrc/_body_header.html'
readonly SIDEBAR='htmlsrc/_sidebar.html'
readonly BODY_FOOTER='htmlsrc/_body_footer.html'

substitute_page_title()
{
	local readonly f="${1:?}"
	local readonly b=`basename "$f" .html`
	local title

	case "$b" in
	disclosure-policy)
		title='Vulnerability Disclosure Policy | '
		;;
	faq)
		title='FAQ | '
		;;
	index|license)
		title=''
		;;
	linktypes)
		title='Link-Layer Header Types | '
		;;
	related)
		title='Related Projects | '
		;;
	broadcom-switch-tag)
		title='Broadcom switch tag | '
		;;
	marvell-switch-tag)
		title='Marvell switch tag | '
		;;
	LINKTYPE_*)
		title="$b | "
		;;
	*)
		echo "Internal error: cannot tell page title for $f" >&2
		exit 1
	esac
	sed "s#%PAGE_TITLE%#${title}TCPDUMP/LIBPCAP public repository#"
}

# Instead of using absolute hyperlinks in all .html files use relative ones
# and amend them for the files under linktypes/ at the generation time. The
# advantage of this is that the user can open the resulting static files
# from their filesystem in a browser and confirm everything works as expected
# before committing the changes and deploying them to the web-server.
rewrite_URLs()
{
	if [ "${1:?}" = "${1#linktypes/}" ]; then
		cat
	else
		sed 's#<link href="style.css#<link href="../style.css#' | \
		sed 's#<img src="images/#<img src="../images/#' | \
		sed 's#<a href="index.html#<a href="../index.html#' | \
		sed 's#<a href="related.html#<a href="../related.html#' | \
		sed 's#<a href="linktypes.html#<a href="../linktypes.html#'
	fi
}

highlight_top_menu()
{
	local readonly f="${1:?}"
	sed "s#<li><a href=\"${f}\">#<li class=\"current_page_item\"><a href=\"${f}\">#"
}

print_html_page()
{
	local readonly infile="${1:?}"
	local show_sidebar
	case `basename "$infile" .html` in
	disclosure-policy|faq|index|license|related)
		show_sidebar='yes'
		;;
	linktypes|broadcom-switch-tag|marvell-switch-tag|LINKTYPE_*)
		show_sidebar='no'
		;;
	*)
		echo "Internal error: cannot tell if $infile should have sidebar" >&2
		exit 1
		;;
	esac

	cat <<ENDOFTEXT
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<!--
Created by       : Luis MartinGarcia <http://www.aldabaknocking.com>
Original design  : "Collaboration" by Free CSS Templates <http://www.freecsstemplates.org>
Original license : Creative Commons Attribution 2.5 License
-->
<html>

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

regenerate_pages()
{
	local f_in f_out
	for f_in in htmlsrc/[^_]*.html htmlsrc/linktypes/*.html; do
		f_out="${f_in#htmlsrc/}"
		file_exists_in_repository "$f_in" || echo "Warning: input file $f_in does not exist in git" >&2
		if file_exists_in_repository "$f_out"; then
			file_differs_from_repository "$f_out" && echo "Warning: unsaved changes to $f_out were lost" >&2
		else
			echo "Warning: output file $f_out does not exist in git" >&2
		fi

		print_html_page "$f_in" | \
		substitute_page_title "$f_out" | \
		highlight_top_menu "$f_out" | \
		rewrite_URLs "$f_out" > "$f_out"

		if [ $? -ne 0 ]; then
			echo "Error: failed to overwrite output file $f_out" >&2
			continue
		fi
		file_differs_from_repository "$f_out" && echo "Regenerated: $f_out"
	done
}

which git >/dev/null 2>&1 || {
	echo "git must be installed to proceed"
	exit 1
}
which sed >/dev/null 2>&1 || {
	echo "sed must be installed to proceed"
	exit 1
}
regenerate_pages

