# SPDX-License-Identifier: AGPL-3.0-or-later
import importlib.util
import json
from pathlib import Path
import subprocess
import sys
import tempfile
import unittest


spec = importlib.util.spec_from_file_location("validate_release", Path(__file__).with_name("validate-release.py"))
release = importlib.util.module_from_spec(spec)
spec.loader.exec_module(release)


class ReleaseValidationTests(unittest.TestCase):
    def setUp(self):
        self.directory = tempfile.TemporaryDirectory()
        self.addCleanup(self.directory.cleanup)
        self.root = Path(self.directory.name)
        (self.root / "appinfo").mkdir()
        self.write_metadata("1.2.2")

    def write_metadata(self, version):
        (self.root / "appinfo/info.xml").write_text(
            f"<info><id>dokuwiki</id><version>{version}</version></info>"
        )
        (self.root / "package.json").write_text(json.dumps({"version": version}))
        (self.root / "package-lock.json").write_text(
            json.dumps({"version": version, "packages": {"": {"version": version}}})
        )
        (self.root / "CHANGELOG.md").write_text(
            f"# Changelog\n\n## [{version}] - 2026-11-01\n\n### Fixed\n\n- A fix\n"
            "\n## [1.2.1] - 2026-09-29\n\n- Older fix\n"
        )

    def test_stable(self):
        self.assertEqual(release.validate(self.root, "v1.2.2"), ("1.2.2", False, "### Fixed\n\n- A fix"))

    def test_prerelease(self):
        self.write_metadata("1.2.2-rc12")
        self.assertTrue(release.validate(self.root, "v1.2.2-rc12")[1])

    def test_invalid_or_mismatched_tags(self):
        for tag in ("1.2.2", "v1.2.3", "v1.2.2-rc0", "v01.2.2", "v1.2.2-beta1", "v1.2.2/extra"):
            with self.subTest(tag=tag), self.assertRaises(ValueError):
                release.validate(self.root, tag)

    def test_package_mismatch(self):
        (self.root / "package.json").write_text('{"version": "1.2.1"}')
        with self.assertRaises(ValueError):
            release.validate(self.root)

    def test_lock_mismatches(self):
        for lock in (
            {"version": "1.2.1", "packages": {"": {"version": "1.2.2"}}},
            {"version": "1.2.2", "packages": {"": {"version": "1.2.1"}}},
        ):
            with self.subTest(lock=lock):
                (self.root / "package-lock.json").write_text(json.dumps(lock))
                with self.assertRaises(ValueError):
                    release.validate(self.root)

    def test_invalid_changelog(self):
        for changelog in (
            "## [1.2.1] - 2026-09-29\n- Old\n",
            "## [1.2.2] - 2026-02-30\n- Fix\n",
            "## [1.2.2]\n- Fix\n",
            "## [1.2.2] - 2026-11-01\n### Fixed\n",
            "## [1.2.2] - 2026-11-01\n- Fix\n## [1.2.2] - 2026-11-01\n- Duplicate\n",
        ):
            with self.subTest(changelog=changelog):
                (self.root / "CHANGELOG.md").write_text(changelog)
                with self.assertRaises(ValueError):
                    release.validate(self.root)

    def test_unreleased_section_is_not_in_release_notes(self):
        changelog = self.root / "CHANGELOG.md"
        changelog.write_text("## [Unreleased]\n- Future\n" + changelog.read_text())
        self.assertNotIn("Future", release.validate(self.root)[2])

    def test_cli_outputs(self):
        notes = self.root / "notes.md"
        outputs = self.root / "outputs"
        result = subprocess.run(
            [
                sys.executable, str(Path(release.__file__)), "--root", str(self.root),
                "--tag", "v1.2.2", "--notes-file", str(notes), "--github-output", str(outputs),
            ],
            capture_output=True, text=True,
        )
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertEqual(notes.read_text(), "### Fixed\n\n- A fix\n")
        self.assertEqual(outputs.read_text(), "version=1.2.2\nprerelease=false\n")

    def test_cli_rejects_invalid_tag_without_outputs(self):
        outputs = self.root / "outputs"
        result = subprocess.run(
            [
                sys.executable, str(Path(release.__file__)), "--root", str(self.root),
                "--tag", "v1.2.3", "--github-output", str(outputs),
            ],
            capture_output=True, text=True,
        )
        self.assertEqual(result.returncode, 1)
        self.assertIn("validation failed", result.stderr)
        self.assertFalse(outputs.exists())


if __name__ == "__main__":
    unittest.main()
