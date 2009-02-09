#!/bin/bash
exec ctags-exuberant -f ./tags \
-h ".php" -R \
--exclude="\.svn" \
--totals=yes \
--tag-relative=yes \
--PHP-kinds=+cf \
--regex-PHP='/abstract\s+class\s+([^ ]+)/\1/c/' \
--regex-PHP='/interface\s+([^ ]+)/\1/c/' \
--regex-PHP='/(public\s+|static\s+|abstract\s+|protected\s+|private\s+)function\s+\&?\s*([^ (]+)/\2/f/'
