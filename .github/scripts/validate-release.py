#!/usr/bin/env python3
# SPDX-License-Identifier: AGPL-3.0-or-later
"""Validate release metadata and extract the matching changelog section."""

import argparse
import datetime
import json
from pathlib import Path
import re
import sys
import xml.etree.ElementTree as ET


VERSION = r"(?:0|[1-9][0-9]*)\.(?:0|[1-9][0-9]*)\.(?:0|[1-9][0-9]*)(?:-rc[1-9][0-9]*)?"


def validate(root, tag=None):
    info = ET.parse(root / "appinfo/info.xml").getroot()
    version = info.findtext("version")
    if not version or not re.fullmatch(VERSION, version):
        raise ValueError("info.xml version must be X.Y.Z or X.Y.Z-rcW (W >= 1)")
    if info.findtext("id") != "dokuwiki":
        raise ValueError("info.xml app id must be dokuwiki")
    if tag is not None and (not re.fullmatch("v" + VERSION, tag) or tag != "v" + version):
        raise ValueError("Release tag must equal v followed by the info.xml version")

    package = json.loads((root / "package.json").read_text())
    lock = json.loads((root / "package-lock.json").read_text())
    versions = {
        "package.json": package.get("version"),
        "package-lock.json": lock.get("version"),
        "package-lock.json packages root": lock.get("packages", {}).get("", {}).get("version"),
    }
    for source, value in versions.items():
        if value != version:
            raise ValueError(f"{source} version {value!r} does not match info.xml {version}")

    changelog = (root / "CHANGELOG.md").read_text()
    headings = list(re.finditer(r"^## \[([^\]]+)\](.*)$", changelog, re.MULTILINE))
    releases = [heading for heading in headings if heading[1] != "Unreleased"]
    if not releases or releases[0][1] != version:
        raise ValueError("The newest dated CHANGELOG.md entry must match info.xml")
    matches = [heading for heading in releases if heading[1] == version]
    if len(matches) != 1:
        raise ValueError("CHANGELOG.md must contain exactly one entry for the release version")
    heading = matches[0]
    date_match = re.fullmatch(r" - ([0-9]{4}-[0-9]{2}-[0-9]{2})", heading[2])
    if not date_match:
        raise ValueError("The release changelog heading must include a YYYY-MM-DD date")
    datetime.date.fromisoformat(date_match[1])
    index = headings.index(heading)
    end = headings[index + 1].start() if index + 1 < len(headings) else len(changelog)
    notes = changelog[heading.end():end].strip()
    if not re.search(r"^\s*[-*] \S", notes, re.MULTILINE):
        raise ValueError("The release changelog section must contain at least one change")
    return version, "-rc" in version, notes


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--tag")
    parser.add_argument("--root", type=Path, default=Path("."))
    parser.add_argument("--notes-file", type=Path)
    parser.add_argument("--github-output", type=Path)
    args = parser.parse_args()
    try:
        version, prerelease, notes = validate(args.root, args.tag)
    except (ValueError, OSError, ET.ParseError) as error:
        print(f"Release metadata validation failed: {error}", file=sys.stderr)
        return 1
    if args.notes_file:
        args.notes_file.write_text(notes + "\n")
    if args.github_output:
        with args.github_output.open("a") as output:
            output.write(f"version={version}\nprerelease={str(prerelease).lower()}\n")
    print(f"Release metadata is consistent: {version}")
    return 0


if __name__ == "__main__":
    sys.exit(main())
