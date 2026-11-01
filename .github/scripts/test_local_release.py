# SPDX-License-Identifier: AGPL-3.0-or-later
import json
import os
from pathlib import Path
import shutil
import subprocess
import tempfile
import unittest


REPOSITORY = Path(__file__).resolve().parents[2]


class LocalReleaseTests(unittest.TestCase):
    def setUp(self):
        self.directory = tempfile.TemporaryDirectory()
        self.addCleanup(self.directory.cleanup)
        self.root = Path(self.directory.name) / "checkout"
        self.remote = Path(self.directory.name) / "origin.git"
        self.root.mkdir()
        self.git("init", "--initial-branch=master")
        self.git("config", "user.name", "Release test")
        self.git("config", "user.email", "release-test@example.invalid")
        subprocess.run(["git", "init", "--bare", str(self.remote)], check=True, capture_output=True)
        self.git("remote", "add", "origin", str(self.remote))
        for name in ("dev-scripts/release.sh", ".github/scripts/validate-release.py", "Makefile"):
            target = self.root / name
            target.parent.mkdir(parents=True, exist_ok=True)
            shutil.copy(REPOSITORY / name, target)
        (self.root / "appinfo").mkdir()
        self.write_metadata("1.2.2")
        self.commit_and_push()

    def git(self, *arguments, check=True):
        return subprocess.run(
            ["git", *arguments], cwd=self.root, check=check,
            capture_output=True, text=True,
        )

    def write_metadata(self, version, notes="- A release fix"):
        (self.root / "appinfo/info.xml").write_text(
            f"<info><id>dokuwiki</id><version>{version}</version></info>"
        )
        (self.root / "package.json").write_text(json.dumps({"version": version}))
        (self.root / "package-lock.json").write_text(
            json.dumps({"version": version, "packages": {"": {"version": version}}})
        )
        (self.root / "CHANGELOG.md").write_text(
            f"## [{version}] - 2026-11-01\n\n### Fixed\n\n{notes}\n"
        )

    def commit_and_push(self):
        self.git("add", ".")
        self.git("commit", "-m", "Release metadata")
        self.git("push", "origin", "master")

    def run_release(self, *arguments, make=False):
        command = ["make", "release", *arguments] if make else ["bash", "dev-scripts/release.sh", *arguments]
        return subprocess.run(
            command, cwd=self.root, capture_output=True, text=True,
            env={**os.environ, "PYTHONDONTWRITEBYTECODE": "1"},
        )

    def assert_no_tags(self):
        self.assertEqual(self.git("tag", "--list").stdout, "")
        self.assertEqual(self.git("ls-remote", "--tags", "origin").stdout, "")

    def test_make_release_without_build_dependencies(self):
        result = self.run_release(make=True)
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertEqual(self.git("cat-file", "-t", "v1.2.2").stdout.strip(), "tag")
        self.assertIn("refs/tags/v1.2.2", self.git("ls-remote", "--tags", "origin").stdout)
        self.assertEqual(self.git("rev-parse", "v1.2.2^{}").stdout, self.git("rev-parse", "HEAD").stdout)

    def test_explicit_rc_release(self):
        self.write_metadata("1.2.3-rc2")
        self.commit_and_push()
        result = self.run_release("VERSION=1.2.3-rc2", make=True)
        self.assertEqual(result.returncode, 0, result.stderr)
        self.assertIn("refs/tags/v1.2.3-rc2", self.git("ls-remote", "--tags", "origin").stdout)

    def test_mismatched_requested_version(self):
        self.assertNotEqual(self.run_release("1.2.3").returncode, 0)
        self.assert_no_tags()

    def test_invalid_metadata_or_empty_notes(self):
        (self.root / "package.json").write_text('{"version":"1.2.1"}')
        self.commit_and_push()
        self.assertNotEqual(self.run_release().returncode, 0)
        self.assert_no_tags()
        self.write_metadata("1.2.2", notes="")
        self.commit_and_push()
        self.assertNotEqual(self.run_release().returncode, 0)
        self.assert_no_tags()

    def test_dirty_checkout(self):
        (self.root / "untracked.txt").write_text("Not committed")
        self.assertNotEqual(self.run_release().returncode, 0)
        self.assert_no_tags()

    def test_wrong_branch(self):
        self.git("switch", "-c", "feature")
        self.assertNotEqual(self.run_release().returncode, 0)
        self.assert_no_tags()

    def test_unpushed_commit(self):
        (self.root / "committed.txt").write_text("Not pushed")
        self.git("add", ".")
        self.git("commit", "-m", "Unpushed")
        self.assertNotEqual(self.run_release().returncode, 0)
        self.assert_no_tags()

    def test_local_tag_already_exists(self):
        self.git("tag", "v1.2.2")
        result = self.run_release()
        self.assertNotEqual(result.returncode, 0)
        self.assertIn("already exists locally", result.stderr)
        self.assertEqual(self.git("ls-remote", "--tags", "origin").stdout, "")

    def test_remote_tag_already_exists(self):
        self.git("tag", "v1.2.2")
        self.git("push", "origin", "refs/tags/v1.2.2")
        self.git("tag", "-d", "v1.2.2")
        result = self.run_release()
        self.assertNotEqual(result.returncode, 0)
        self.assertIn("already exists on origin", result.stderr)
        self.assertEqual(self.git("tag", "--list").stdout, "")

    def test_unreachable_remote(self):
        self.git("remote", "set-url", "origin", str(self.remote / "missing"))
        self.assertNotEqual(self.run_release().returncode, 0)
        self.assertEqual(self.git("tag", "--list").stdout, "")

    def test_failed_push_retains_tag(self):
        hook = self.remote / "hooks/pre-receive"
        hook.write_text("#!/bin/sh\nexit 1\n")
        hook.chmod(0o755)
        result = self.run_release()
        self.assertNotEqual(result.returncode, 0)
        self.assertIn("Local tag v1.2.2 has been retained", result.stderr)
        self.assertEqual(self.git("tag", "--list").stdout.strip(), "v1.2.2")
        self.assertEqual(self.git("ls-remote", "--tags", "origin").stdout, "")


if __name__ == "__main__":
    unittest.main()
