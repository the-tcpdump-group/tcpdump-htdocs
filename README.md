# web site sources for www.tcpdump.org
Most of what you see on the project's web site comes from this git repository.
All of the HTML files and man pages in this repository are pre-generated using
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

## history and credits
[Michael Richardson](http://www.sandelman.ca/) started www.tcpdump.org in 1999.
As was common at the time, it was a static web site with a few pages and a very
simple design, which was probably contributed by someone known as "Mr. Man" or
"JWS".  The web site grew with time.

[Luis MartinGarcia](https://www.luismg.com/) upgraded the static web site to a
more modern look in 2010 using a mix of original artwork and the
"Collaboration" template from Free CSS Templates project, which later became
known as "TEMPLATED", but eventually went offline.  That template's licence was
Creative Commons Attribution 2.5.  The resulting design mostly remains to this
day.

The web site contents lived in a CVS repository from 1999 to 2011.  In 2012 it
was migrated into this git repository; the contents was preserved, but the
commit history wasn't.

In 2013 [Denis Ovsienko](https://ovsienko.info/) started to introduce various
scripting into the contents generation and delivery, this has been a slow work
in progress ever since.

Besides the developments above, over many years many other people contributed
effort, contents and ideas to make www.tcpdump.org what it is now.  Thank you
for that!
