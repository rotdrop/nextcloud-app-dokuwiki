#! /bin/bash

perl ./l10n.pl read
msgmerge -vU --previous --backup=numbered de/dokuwikiembed.po  templates/dokuwikiembed.pot
perl ./l10n.pl write

