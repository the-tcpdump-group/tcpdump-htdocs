#!/bin/sh -ue

# Copyright (c) 2024 The Tcpdump Group
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

cleanup()
{
	# WORKDIR can be not set yet.
	[ "${WORKDIR:-}" ] && rm -rf "$WORKDIR"
}

usage_and_exit()
{
	cat >&2 <<-ENDOFTEXT
		Usage: $MYNAME <tarball A> <tarball B>
		Compare two tarballs and produce a recursive unified diff.
		Supported formats: .tar .tar.gz .tar.bz2 .tar.xz

		This script prints the difference between the contents of two tarball
		archives as if these were directories.  It has been tested to work on the
		following OSes:
		* Linux
		* OpenBSD

		The current implementation extracts the tarballs, each into its separate
		temporary directory, then runs "diff -Nur" on the two directories and exits
		with the exit status of diff.  If diff fails, the script prints a warning.

		This approach handles well the use case of comparing two tarballs of the same
		version of the same program.  For example, one tarball could be
		tcpdump-4.99.4.tar.gz and the other could be tcpdump-4.99.4.tar.xz, and/or
		the tarballs could be produced on different hosts using different versions of
		Autoconf, Automake, m4, make etc.  It does not handle well the use case of
		comparing two different versions of the same program because the topmost
		extracted directory name is different in each of the two temporary directories.
		This can be improved in future as and if necessary.
	ENDOFTEXT
	cleanup
	exit "$1"
}

# $1: tarball file
# $2: destination directory
extract_contents()
{
	# GNU tar supports --one-top-level[=DIR], but BSD tar does not.
	mkdir "$2"
	cd "$2"
	case "$1" in
	*.tar)
		tar xf "$1"
		;;
	*.tar.gz)
		tar xzf "$1"
		;;
	*.tar.bz2)
		tar xjf "$1"
		;;
	*.tar.xz)
		# GNU tar supports "xaf" or at least "xJf", but BSD tar does
		# not, so it needs to be a pipeline.  To that end, the pipefail
		# shell option would be a perfect fit, but it is a bashism, so
		# use independent commands.
		unxz -T0 <"$1" >"$WORKDIR/tmp.tar"
		tar xf "$WORKDIR/tmp.tar"
		rm -f "$WORKDIR/tmp.tar"
		;;
	*)
		echo "Error: input the file '$1' is not of a known format!" >&2
		usage_and_exit 65 # EX_DATAERR
		;;
	esac
	cd - >/dev/null
}

MYNAME=$(basename "$0")
if [ $# -ne 2 ]; then
	echo "Error: exactly two arguments required!" >&2
	usage_and_exit 64 # EX_USAGE
fi
TARBALL_A=$(readlink -f "$1")
TARBALL_B=$(readlink -f "$2")
# Shortcut: identical archives have identical contents.
cmp -s "$TARBALL_A" "$TARBALL_B" && exit 0

trap 'cleanup ; exit 1' HUP PIPE INT QUIT TERM
WORKDIR=$(mktemp -d -t "$MYNAME.XXXXXXXX")
DIFFFILE="$WORKDIR/A-B.diff"
extract_contents "$TARBALL_A" "$WORKDIR/A"
extract_contents "$TARBALL_B" "$WORKDIR/B"

# Omit WORKDIR from the diff.
cd "$WORKDIR"
diff -Nur A B >"$DIFFFILE" || rc=$?
cd - >/dev/null

if [ "${rc:=0}" -ne 0 ]; then
	echo "A: $1"
	echo "B: $2"
	if [ -s "$DIFFFILE" ]; then
		cat "$DIFFFILE"
	else
		# This script will likely be used by a human, so let's make
		# sure any diff non-zero exit status always generates some
		# output.
		echo "WARNING: diff exited with status $rc and empty output" >&2
	fi
fi
cleanup
exit "$rc"
