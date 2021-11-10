# web site sources for www.tcpdump.org
Most of what you see on the project's web site comes from this git repository.
Most HTML files and all man pages in this repository are pre-generated using
two shell scripts and committed to the master branch, the latest revision of
which is automatically (with a small lag) used by the web server.

## man pages
Content updates to the man pages must be committed to the git repository of
respective project ([more on this](https://www.tcpdump.org/faq.html#q11)), then
`regen_man_pages.sh` can be used to regenerate the `.html` and `.txt` versions
of the man pages in this repository.  The script contains a detailed comment on
what to expect from a clean commit.

When ready, `git add manpages` and commit with a single-line message
(`Regenerate man pages. [skip ci]` would be sufficient).  With all changes
committed and the git repository clean `regen_man_pages.sh` should exit with
status 0 without warnings or errors.

## other HTML files
To update an existing HTML file (for example, `linktypes.html`), identify its
source in the `htmlsrc` directory (`htmlsrc/linktypes.html`) and update the
source file, then run `regen_html_pages.sh`, which will produce the output file
(`linktypes.html`).  To confirm that the updated output file looks as intended,
open it from the working directory (`firefox linktypes.html`).  This may have
to be repeated more than once to get clean HTML, a clean diff and a good look
in the browser.

When ready, `git add` both the source and the output files and commit with a
meaningful message.  With all changes committed and the git repository clean
`regen_html_pages.sh` should exit with status 0 without warnings or errors.
