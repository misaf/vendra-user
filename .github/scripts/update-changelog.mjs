import { readFileSync, writeFileSync, existsSync } from 'fs';

const CHANGELOG_PATH = 'CHANGELOG.md';
const RELEASE_NOTES_PATH = '/tmp/release-notes.md';
const UNRELEASED_PATTERN = /^## Unreleased\s*[\s\S]*?(?=^## |\s*$)/m;

function buildEntry(version, notes) {
  const releaseVersion = version.replace(/^v/, '');
  const date = new Date().toISOString().slice(0, 10);

  return `## ${releaseVersion} - ${date}\n\n${notes}\n`;
}

function readChangelog() {
  if (existsSync(CHANGELOG_PATH)) {
    return readFileSync(CHANGELOG_PATH, 'utf8');
  }

  const packageName = process.env.GITHUB_REPOSITORY?.split('/')[1] ?? 'this package';

  return `# Changelog\n\nAll notable changes to \`${packageName}\` will be documented in this file.\n`;
}

function insertEntry(changelog, entry) {
  if (UNRELEASED_PATTERN.test(changelog)) {
    return changelog.replace(UNRELEASED_PATTERN, entry);
  }

  const firstReleaseIndex = changelog.search(/^## /m);

  if (firstReleaseIndex === -1) {
    return `${changelog.trimEnd()}\n\n${entry}`;
  }

  return `${changelog.slice(0, firstReleaseIndex)}${entry}\n${changelog.slice(firstReleaseIndex)}`;
}

const version = process.env.VERSION;
const notes = readFileSync(RELEASE_NOTES_PATH, 'utf8').trim();
const entry = buildEntry(version, notes);
const changelog = readChangelog();
const updated = insertEntry(changelog, entry);

writeFileSync(CHANGELOG_PATH, updated.trimEnd() + '\n');
