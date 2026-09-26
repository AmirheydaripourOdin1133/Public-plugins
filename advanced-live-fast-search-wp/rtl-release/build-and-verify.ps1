# Build & verify RTL encoded release package
# Run from anywhere:
#   powershell -ExecutionPolicy Bypass -File .\rtl-release\build-and-verify.ps1

$ErrorActionPreference = 'Stop'
$src = Split-Path -Parent $PSScriptRoot
if (-not (Test-Path (Join-Path $src 'advanced-live-fast-search-wp.php'))) {
	$src = $PSScriptRoot
	if (-not (Test-Path (Join-Path $src 'advanced-live-fast-search-wp.php'))) {
		throw 'Project root not found.'
	}
	$release = Join-Path $src 'rtl-release'
} else {
	$release = Join-Path $src 'rtl-release'
}

$encInput = Join-Path $release 'encoded-input'
$build = Join-Path $release 'build\advanced-live-fast-search-wp'
$pkgPlugin = Join-Path $release 'package\Plugin'
$pkgHelp = Join-Path $release 'package\help'
$reports = Join-Path $release 'reports'
$utf8 = New-Object System.Text.UTF8Encoding($false)

New-Item -ItemType Directory -Path $build, $pkgPlugin, $pkgHelp, $reports -Force | Out-Null
if (Test-Path $build) { Remove-Item $build -Recurse -Force }
New-Item -ItemType Directory -Path $build -Force | Out-Null

$encodedFiles = @(
	'includes\class-mfs-assets.php',
	'includes\class-mfs-cache.php',
	'includes\class-mfs-icons.php',
	'includes\class-mfs-license.php',
	'includes\class-mfs-plugin.php',
	'includes\class-mfs-render.php',
	'includes\class-mfs-rest.php',
	'includes\class-mfs-settings.php',
	'includes\elementor\class-mfs-elementor.php',
	'includes\elementor\class-mfs-search-widget.php',
	'skins\inline\template.php',
	'skins\modal\template.php',
	'skins\split-panel\template.php',
	'skins\top-panel\template.php'
)

# Assemble from clean source
$copyRoot = @('assets', 'includes', 'skins', 'readme.txt', 'uninstall.php', 'RTL_License_df44093b0d904fd2.php', 'advanced-live-fast-search-wp.php')
foreach ($item in $copyRoot) {
	$from = Join-Path $src $item
	if (-not (Test-Path $from)) { throw "Missing source item: $item" }
	Copy-Item -LiteralPath $from -Destination (Join-Path $build $item) -Recurse -Force
}

# Overlay encoded PHP
foreach ($rel in $encodedFiles) {
	$from = Join-Path $encInput $rel
	if (-not (Test-Path $from)) { throw "Missing encoded file in encoded-input: $rel" }
	$to = Join-Path $build $rel
	New-Item -ItemType Directory -Path (Split-Path $to) -Force | Out-Null
	Copy-Item -LiteralPath $from -Destination $to -Force
}

function Test-IsEncoded([string]$path) {
	$h = (Get-Content -LiteralPath $path -TotalCount 4 -ErrorAction SilentlyContinue) -join "`n"
	return [bool]($h -match 'ionCube|Encrypted by|ICB0')
}

$errors = New-Object System.Collections.Generic.List[string]
$warnings = New-Object System.Collections.Generic.List[string]
$ok = New-Object System.Collections.Generic.List[string]

$mainPath = Join-Path $build 'advanced-live-fast-search-wp.php'
$mainText = [System.IO.File]::ReadAllText($mainPath)
if ($mainText -notmatch 'Plugin Name:') { [void]$errors.Add('MAIN_MISSING_PLUGIN_NAME') } else { [void]$ok.Add('main has Plugin Name') }
if ($mainText -match 'ICB0') { [void]$errors.Add('MAIN_MUST_NOT_BE_ENCODED') } else { [void]$ok.Add('main is plaintext') }

$lic = Join-Path $build 'RTL_License_df44093b0d904fd2.php'
$expectedHash = 'debbb9d05a3b8fb63bbb71e614384ed7504d42a6'
if (-not (Test-Path $lic)) {
	[void]$errors.Add('LICENSE_FILE_MISSING')
} else {
	$actualHash = (Get-FileHash -LiteralPath $lic -Algorithm SHA1).Hash.ToLower()
	if ($actualHash -ne $expectedHash) {
		[void]$errors.Add("LICENSE_HASH_MISMATCH expected=$expectedHash actual=$actualHash")
	} else {
		[void]$ok.Add("license SHA1 ok ($actualHash)")
	}
	if (-not (Test-IsEncoded $lic)) { [void]$warnings.Add('LICENSE_NOT_DETECTED_AS_ENCODED') }
}

foreach ($rel in $encodedFiles) {
	$p = Join-Path $build $rel
	if (-not (Test-Path $p)) { [void]$errors.Add("MISSING:$rel"); continue }
	if (-not (Test-IsEncoded $p)) { [void]$errors.Add("NOT_ENCODED:$rel") }
}
[void]$ok.Add("encoded overlay count=$($encodedFiles.Count)")

if (Test-IsEncoded (Join-Path $build 'uninstall.php')) {
	[void]$errors.Add('UNINSTALL_SHOULD_BE_PLAINTEXT')
} else {
	[void]$ok.Add('uninstall plaintext')
}

foreach ($icon in @('assets\icons\menu_icon.svg', 'assets\icons\icon-plugin.webp', 'assets\icons\look_system.svg', 'assets\icons\unlook_system.svg')) {
	if (-not (Test-Path (Join-Path $build $icon))) { [void]$errors.Add("ICON_MISSING:$icon") }
}

$zipPath = Join-Path $pkgPlugin 'advanced-live-fast-search-wp.zip'
if (Test-Path $zipPath) { Remove-Item $zipPath -Force }
Add-Type -AssemblyName System.IO.Compression
Add-Type -AssemblyName System.IO.Compression.FileSystem
# IMPORTANT: WordPress/Linux needs forward-slash zip entry names.
# Windows ZipFile::CreateFromDirectory often writes backslashes and breaks activation.
$zipStream = [System.IO.File]::Open($zipPath, [System.IO.FileMode]::Create)
$zip = New-Object System.IO.Compression.ZipArchive($zipStream, [System.IO.Compression.ZipArchiveMode]::Create, $false)
foreach ($file in (Get-ChildItem -LiteralPath $build -Recurse -File)) {
	$rel = $file.FullName.Substring($build.Length).TrimStart('\', '/')
	$entryName = 'advanced-live-fast-search-wp/' + ($rel -replace '\\', '/')
	[void][System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile(
		$zip,
		$file.FullName,
		$entryName,
		[System.IO.Compression.CompressionLevel]::Optimal
	)
}
$zip.Dispose()
$zipStream.Dispose()
[void]$ok.Add("zip built with unix paths: $zipPath")

$status = if ($errors.Count -eq 0) { 'PASS' } else { 'FAIL' }
$lines = @(
	"STATUS=$status",
	"TIME=$(Get-Date -Format o)",
	"ZIP=$zipPath",
	"ZIP_SIZE=$((Get-Item $zipPath).Length)",
	'',
	'=== OK ==='
) + $ok + @('', '=== WARNINGS ===') + $(if ($warnings.Count) { $warnings } else { @('none') }) + @('', '=== ERRORS ===') + $(if ($errors.Count) { $errors } else { @('none') })

$reportPath = Join-Path $reports 'verify-latest.txt'
[System.IO.File]::WriteAllText($reportPath, ($lines -join "`r`n"), $utf8)
Write-Output ($lines -join "`n")
Write-Output ''
Write-Output "Report: $reportPath"

if ($errors.Count -gt 0) { exit 1 }
