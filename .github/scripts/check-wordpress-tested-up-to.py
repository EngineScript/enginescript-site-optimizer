#!/usr/bin/env python3
"""Check WordPress "Tested up to" metadata against the latest release."""

from __future__ import annotations

import json
import os
import re
import subprocess
import sys
import urllib.request
from pathlib import Path


WORDPRESS_VERSION_CHECK_URL = os.environ.get(
    "WORDPRESS_VERSION_CHECK_URL",
    "https://api.wordpress.org/core/version-check/1.7/",
)
WORDPRESS_LATEST_VERSION = os.environ.get("WORDPRESS_LATEST_VERSION")
SCAN_EXTENSIONS = {".php", ".md", ".txt"}
DEFAULT_EXCLUDED_DIRS = {
    ".git",
    "build",
    "coverage",
    "dist",
    "node_modules",
    "plugin-check-build",
    "vendor",
}
TESTED_UP_TO_PATTERN = re.compile(
    r"\btested\s+up\s+to\s*:\s*([0-9]+(?:\.[0-9]+){1,2})\b",
    re.IGNORECASE,
)
TESTED_UP_TO_LABEL_PATTERN = re.compile(r"\btested\s+up\s+to\s*:", re.IGNORECASE)
VERSION_PATTERN = re.compile(r"^[0-9]+(?:\.[0-9]+){1,2}$")


def main() -> int:
    latest_version = get_latest_wordpress_major_minor()
    excluded_dirs = get_excluded_dirs()
    findings = find_tested_up_to_entries(excluded_dirs)

    if not findings:
        message = "No Tested up to metadata was found in tracked PHP, Markdown, or text files."
        print_github_error(message)
        write_summary(latest_version, [], [message])
        return 1

    failures = []
    for finding in findings:
        if finding["version"] is None:
            failures.append(
                f"{finding['path']}:{finding['line']}: Could not parse Tested up to version."
            )
            print_github_error(
                "Could not parse Tested up to version.",
                finding["path"],
                finding["line"],
            )
            continue

        tested_version = normalize_major_minor(finding["version"])
        if tested_version != latest_version:
            message = (
                f"Tested up to is {finding['version']}; expected {latest_version} "
                "for the latest WordPress release."
            )
            failures.append(f"{finding['path']}:{finding['line']}: {message}")
            print_github_error(message, finding["path"], finding["line"])

    write_summary(latest_version, findings, failures)

    if failures:
        entry_label = "entry" if len(failures) == 1 else "entries"
        print(f"Found {len(failures)} stale or invalid Tested up to {entry_label}.")
        for failure in failures:
            print(f"- {failure}")
        return 1

    print(f"All Tested up to entries match WordPress {latest_version}.")
    return 0


def get_latest_wordpress_major_minor() -> str:
    if WORDPRESS_LATEST_VERSION:
        return normalize_major_minor(WORDPRESS_LATEST_VERSION)

    request = urllib.request.Request(
        WORDPRESS_VERSION_CHECK_URL,
        headers={"User-Agent": "wordpress-tested-up-to-check/1.0"},
    )

    with urllib.request.urlopen(request, timeout=20) as response:
        payload = json.load(response)

    versions = []
    for offer in payload.get("offers", []):
        version = offer.get("current") or offer.get("version")
        if isinstance(version, str) and VERSION_PATTERN.match(version):
            versions.append(version)

    if not versions:
        raise RuntimeError("Could not determine the latest WordPress version from the version-check API.")

    return normalize_major_minor(max(versions, key=version_sort_key))


def normalize_major_minor(version: str) -> str:
    parts = version.split(".")

    if len(parts) < 2 or not all(part.isdigit() for part in parts):
        raise ValueError(f"Invalid WordPress version: {version}")

    return ".".join(parts[:2])


def version_sort_key(version: str) -> tuple[int, ...]:
    return tuple(int(part) for part in version.split("."))


def get_excluded_dirs() -> set[str]:
    configured = os.environ.get("WORDPRESS_TESTED_UP_TO_EXCLUDE_DIRS", "")
    extra_dirs = {item.strip() for item in configured.split(",") if item.strip()}

    return DEFAULT_EXCLUDED_DIRS | extra_dirs


def find_tested_up_to_entries(
    excluded_dirs: set[str],
) -> list[dict[str, str | int | None]]:
    findings = []

    for path in get_tracked_files():
        if not should_scan(path, excluded_dirs):
            continue

        lines = path.read_text(encoding="utf-8", errors="replace").splitlines()

        for line_number, line in enumerate(lines, 1):
            if not TESTED_UP_TO_LABEL_PATTERN.search(line):
                continue

            match = TESTED_UP_TO_PATTERN.search(line)
            findings.append(
                {
                    "path": path.as_posix(),
                    "line": line_number,
                    "version": match.group(1) if match else None,
                }
            )

    return findings


def get_tracked_files() -> list[Path]:
    result = subprocess.run(
        ["git", "ls-files", "*.php", "*.md", "*.txt"],
        check=True,
        stdout=subprocess.PIPE,
        stderr=subprocess.PIPE,
        text=True,
    )

    return [Path(line) for line in result.stdout.splitlines() if line.strip()]


def should_scan(path: Path, excluded_dirs: set[str]) -> bool:
    if path.suffix.lower() not in SCAN_EXTENSIONS:
        return False

    return not any(part in excluded_dirs for part in path.parts)


def write_summary(
    latest_version: str,
    findings: list[dict[str, str | int | None]],
    failures: list[str],
) -> None:
    summary_path = os.environ.get("GITHUB_STEP_SUMMARY")
    if not summary_path:
        return

    lines = [
        "# WordPress Tested Up To Check",
        "",
        f"Latest WordPress major.minor release: `{latest_version}`",
        f"Metadata entries checked: `{len(findings)}`",
        "",
    ]

    if failures:
        lines.append("## Failures")
        lines.extend(f"- {failure}" for failure in failures)
    else:
        lines.append("All Tested up to entries are current.")

    with open(summary_path, "a", encoding="utf-8") as summary:
        summary.write("\n".join(lines))
        summary.write("\n")


def print_github_error(message: str, path: str | None = None, line: int | None = None) -> None:
    if path is None:
        print(f"::error::{escape_github_command_data(message)}")
        return

    properties = f"file={escape_github_command_property(path)}"
    if line is not None:
        properties += f",line={line}"

    print(f"::error {properties}::{escape_github_command_data(message)}")


def escape_github_command_property(value: str) -> str:
    return (
        value.replace("%", "%25")
        .replace("\r", "%0D")
        .replace("\n", "%0A")
        .replace(":", "%3A")
        .replace(",", "%2C")
    )


def escape_github_command_data(value: str) -> str:
    return value.replace("%", "%25").replace("\r", "%0D").replace("\n", "%0A")


if __name__ == "__main__":
    try:
        sys.exit(main())
    except Exception as exc:
        print_github_error(str(exc))
        sys.exit(1)
