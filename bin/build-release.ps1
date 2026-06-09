<#
.SYNOPSIS
    Build a clean, distributable Zignites Chat plugin zip (dev files excluded).

.DESCRIPTION
    Exports the committed tree (git-ignored vendor/, node_modules/ and caches
    are already excluded), strips the dev-only tracked files listed in
    .distignore, and zips the result into build/zignites-chat-<version>.zip with
    a top-level zignites-chat/ folder — the layout WordPress expects.

    Uses only git, tar and Compress-Archive, all native on Windows 10/11, so no
    wp-cli or extra tooling is required. CI on Linux can instead run
    `wp dist-archive .` which reads the same .distignore.

.EXAMPLE
    powershell -File bin/build-release.ps1
#>
$ErrorActionPreference = 'Stop'

$slug = 'zignites-chat'
$root = Split-Path -Parent $PSScriptRoot
Set-Location $root

# Read the version straight from the plugin header so the zip name matches.
$header  = Get-Content 'zignites-chat.php' -TotalCount 30
$verLine = $header | Select-String -Pattern 'Version:\s*([0-9][0-9A-Za-z.\-]*)'
if (-not $verLine) { throw 'Could not read Version from zignites-chat.php header.' }
$version = $verLine.Matches[0].Groups[1].Value

$build = Join-Path $root 'build'
$stage = Join-Path $build $slug
if (Test-Path $build) { Remove-Item $build -Recurse -Force }
New-Item -ItemType Directory -Path $stage -Force | Out-Null

# Export only committed files. git-ignored paths (vendor, node_modules,
# *.log, caches) never enter the archive.
$tar = Join-Path $build 'export.tar'
git archive HEAD --format=tar -o $tar
if ($LASTEXITCODE -ne 0) { throw 'git archive failed (are there any commits on HEAD?).' }
tar -x -f $tar -C $stage
Remove-Item $tar

# Strip dev-only tracked files (mirror of .distignore). Kept explicit so the
# build never depends on wp-cli parsing .distignore.
$devPaths = @(
    '.github', 'bin', 'docs', '.gitignore', '.distignore',
    'composer.json', 'composer.lock',
    'tests', 'phpunit.xml.dist', 'phpcs.xml.dist',
    'PROGRESS.md', 'README.md', 'WooChat-Master-Development-Prompt.md'
)
foreach ($p in $devPaths) {
    $full = Join-Path $stage $p
    if (Test-Path $full) { Remove-Item $full -Recurse -Force }
}

$zipName = "$slug-$version.zip"
$zip     = Join-Path $build $zipName
if (Test-Path $zip) { Remove-Item $zip -Force }

# Zip with bsdtar (bundled on Windows 10/11). Compress-Archive is slow and
# lock-prone, and .NET's ZipFile on Windows PowerShell writes backslash entry
# names that violate the ZIP spec; bsdtar writes spec-compliant forward slashes.
# Running from $build keeps the top-level zignites-chat/ folder WP expects.
Push-Location $build
try {
    tar -c --format=zip -f $zipName $slug
    if ($LASTEXITCODE -ne 0) { throw 'tar failed to create the release zip.' }
} finally {
    Pop-Location
}

$fileCount = (Get-ChildItem $stage -Recurse -File).Count
Write-Host "Built $zip ($fileCount files, version $version)."
