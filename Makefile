# This file is licensed under the Affero General Public License version 3 or
# later. See the COPYING file.
#
# Thin wrapper around the shared Makefile of the build-system submodule,
# only the app-specific settings live here.

SRCDIR = .
ABSSRCDIR = $(CURDIR)
DEV_LIB_DIR = $(ABSSRCDIR)/dev-scripts/lib

TS_PHP_SOURCE_DIRS = lib

include $(DEV_LIB_DIR)/makefile/app.mk

toolkit-l10n: vendor-scoped/autoload.php
vue-components-l10n: node_modules
