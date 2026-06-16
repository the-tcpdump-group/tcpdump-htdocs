#!/bin/sh -eu

# Given a git working copy that has all required changes already committed and
# has no modified or untracked files, verify that regen_html_pages.sh runs
# successfully without any effects, and that all known PHP and shell scripts
# are free from syntax errors.
#
# This script has been tested to work on the following systems:
#
# Debian 13 (apt-get install git sed shellcheck php-cli)
# FreeBSD 14.4 (pkg install git-tiny gsed hs-ShellCheck php85)

if tty -s; then
	ANSIFAIL='\033[31;1m' # bold red
	ANSIPASS='\033[32;1m' # bold green
	ANSIRESET='\033[0m'
else
	ANSIFAIL=''
	ANSIPASS=''
	ANSIRESET=''
fi

fail()
{
	printf "${ANSIFAIL}FAIL:${ANSIRESET} %s\n" "$*"
	exit 1
}
pass()
{
	printf "${ANSIPASS}PASS:${ANSIRESET} %s\n" "$*"
}

workdir_is_clean()
{
	if [ "$(git status --porcelain | wc -l)" -ne 0 ]; then
		git status
		fail "The working copy is not clean${1:+ ($1)}!"
	fi
}

test_html()
{
	cmd="SED='${1:?}' ./regen_html_pages.sh"
	SED="$1" ./regen_html_pages.sh || fail "$cmd"
	workdir_is_clean "after \"$cmd\""
	pass "$cmd"
}

cd "$(dirname "$(realpath "$0")")"
workdir_is_clean 'before all tests'

test_html sed
# If "sed" has worked, but is not GNU sed, require another pass using GNU sed.
sed --version >/dev/null 2>&1 || test_html gsed

for phpfile in bpfexam/index.php manpages/restyle.php; do
	php --syntax-check "$phpfile" >/dev/null || fail 'php --syntax-check'
done
pass 'php --syntax-check'

for shellfile in regen_man_pages.sh regen_html_pages.sh release/tarballdiff.sh $(basename "$(realpath "$0")"); do
	shellcheck -f gcc "$shellfile" || fail 'shellcheck'
done
pass 'shellcheck'
