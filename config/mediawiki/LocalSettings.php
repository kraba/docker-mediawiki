<?php

// @see https://www.mediawiki.org/wiki/Manual:Configuration_settings

# Protect against web entry
if ( !defined( 'MEDIAWIKI' ) ) {
    exit;
}

if (getenv('MEDIAWIKI_SITENAME') != '') {
    $wgSitename = getenv('MEDIAWIKI_SITENAME');
}

if (getenv('MEDIAWIKI_META_NAMESPACE') != '') {
    $wgMetaNamespace = getenv('MEDIAWIKI_META_NAMESPACE');
}

# Short URLs
$wgScriptPath = "";
$wgArticlePath = "/$1";
$wgUsePathInfo = true;
$wgScriptExtension = ".php";

if (getenv('MEDIAWIKI_SERVER') == '') {
    throw new Exception('Missing environment variable MEDIAWIKI_SERVER');
} else {
    $wgServer = getenv('MEDIAWIKI_SERVER');
}

$wgResourceBasePath = $wgScriptPath;

$wgLogo = "$wgResourceBasePath/resources/assets/wiki.png";

if (getenv('MEDIAWIKI_EMERGENCY_CONTACT') != '') {
    $wgEmergencyContact = getenv('MEDIAWIKI_EMERGENCY_CONTACT');
}

if (getenv('MEDIAWIKI_PASSWORD_SENDER') != '') {
    $wgPasswordSender = getenv('MEDIAWIKI_PASSWORD_SENDER');
}

if (getenv('MEDIAWIKI_DB_TYPE') != '') {
    $wgDBtype = getenv('MEDIAWIKI_DB_TYPE');
}

if (getenv('MEDIAWIKI_DB_HOST') != '' || getenv('MEDIAWIKI_DB_PORT') != '') {
    $hostname = ((getenv('MEDIAWIKI_DB_HOST') != '') ? getenv('MEDIAWIKI_DB_HOST') : '127.0.0.1');
    $port = ((getenv('MEDIAWIKI_DB_PORT') != '') ? getenv('MEDIAWIKI_DB_PORT') : '3306');
    $wgDBserver = $hostname.':'.$port;
}

unset($hostname, $port);

if (getenv('MEDIAWIKI_DB_NAME') != '') {
    $wgDBname = getenv('MEDIAWIKI_DB_NAME');
}

if (getenv('MEDIAWIKI_DB_USER') != '') {
    $wgDBuser = getenv('MEDIAWIKI_DB_USER');
}

if (getenv('MEDIAWIKI_DB_PASSWORD') != '') {
    $wgDBpassword = getenv('MEDIAWIKI_DB_PASSWORD');
}

# MySQL specific settings
if (getenv('MEDIAWIKI_DB_TYPE') == 'mysql') {
    // Cache sessions in database
    $wgSessionCacheType = CACHE_DB;

    if (getenv('MEDIAWIKI_DB_PREFIX') != '') {
        $wgDBprefix = getenv('MEDIAWIKI_DB_PREFIX');
    }

    if (getenv('MEDIAWIKI_DB_TABLE_OPTIONS') != '') {
        $wgDBTableOptions = getenv('MEDIAWIKI_DB_TABLE_OPTIONS');
    }
}

$wgDBmysql5 = false;

# SQLite specific settings
$wgSQLiteDataDir = '/data';

if (getenv('MEDIAWIKI_DB_TYPE') == 'sqlite') {
    $wgObjectCaches[CACHE_DB] = [
        'class' => 'SqlBagOStuff',
        'loggroup' => 'SQLBagOStuff',
        'server' => [
            'type' => 'sqlite',
            'dbname' => 'wikicache',
            'tablePrefix' => '',
            'flags' => 0
        ]
    ];
}

$wgMainCacheType = CACHE_ACCEL;
$wgMemCachedServers = [];

$wgUploadPath = '/images';
$wgUploadDirectory = '/images';
$wgUploadSizeWarning = false;

if (getenv('MEDIAWIKI_MAX_UPLOAD_SIZE') != '') {
    // Since MediaWiki's config takes upload size in bytes and PHP in 100M format, lets use PHPs format and convert that here.
    $maxUploadSize = getenv('MEDIAWIKI_MAX_UPLOAD_SIZE');
    if (strlen($maxUploadSize) >= 2) {
        $maxUploadSizeUnit = substr($maxUploadSize, -1, 1);
        $maxUploadSizeValue = (integer)substr($maxUploadSize, 0, -1);
        switch (strtoupper($maxUploadSizeUnit)) {
            case 'G':
                $maxUploadSizeFactor = 1024 * 1024 * 1024;
                break;
            case 'M':
                $maxUploadSizeFactor = 1024 * 1024;
                break;
            case 'K':
                $maxUploadSizeFactor = 1024;
                break;
            case 'B':
            default:
                $maxUploadSizeFactor = 0;
                break;
        }
        $wgMaxUploadSize = $maxUploadSizeValue * $maxUploadSizeFactor;
        unset($maxUploadSizeUnit, $maxUploadSizeValue, $maxUploadSizeFactor);
    }
}

$wgEnableUploads = false;
if (getenv('MEDIAWIKI_ENABLE_UPLOADS') == '1') {
    $wgEnableUploads = true;
}

if (getenv('MEDIAWIKI_FILE_EXTENSIONS') != '') {
    foreach (explode(', ', getenv('MEDIAWIKI_FILE_EXTENSIONS')) as $extension) {
        $wgFileExtensions[] = trim($extension);
    }
}

$wgUseImageMagick = true;
$wgImageMagickConvertCommand = "/usr/bin/convert";
$wgShellLocale = "C.UTF-8";

if (getenv('MEDIAWIKI_LANGUAGE_CODE') != '') {
    $wgLanguageCode = getenv('MEDIAWIKI_LANGUAGE_CODE');
}

if (getenv('MEDIAWIKI_SECRET_KEY') != '') {
    $wgSecretKey = getenv('MEDIAWIKI_SECRET_KEY');
}

if (getenv('MEDIAWIKI_UPGRADE_KEY') != '') {
    $wgUpgradeKey = getenv('MEDIAWIKI_UPGRADE_KEY');
}

$wgDiff3 = "/usr/bin/diff3";

$wgDefaultSkin = "vector";
if (getenv('MEDIAWIKI_DEFAULT_SKIN') != '') {
    $wgDefaultSkin = getenv('MEDIAWIKI_DEFAULT_SKIN');
}

# Enabled skins
wfLoadSkin( 'CologneBlue' );
wfLoadSkin( 'Modern' );
wfLoadSkin( 'MonoBook' );
wfLoadSkin( 'Vector' );

# Debug
if (getenv('MEDIAWIKI_DEBUG') == '1') {
    $wgShowExceptionDetails = true;
    $wgShowSQLErrors = true;
    $wgDebugDumpSql = true;
    $wgDebugLogFile = "/tmp/wiki-debug.log";
}

# SMTP E-Mail
if (getenv('MEDIAWIKI_SMTP') == '1') {
    $wgEnableEmail = true;
    $wgEnableUserEmail = true;
    $wgSMTP = array(
        'host'     => getenv('MEDIAWIKI_SMTP_HOST'), // could also be an IP address. Where the SMTP server is located
        'IDHost'   => getenv('MEDIAWIKI_SMTP_IDHOST'), // Generally this will be the domain name of your website (aka mywiki.org)
        'port'     => getenv('MEDIAWIKI_SMTP_PORT'), // Port to use when connecting to the SMTP server
        'auth'     => (getenv('MEDIAWIKI_SMTP_AUTH') == '1'), // Should we use SMTP authentication (true or false)
        'username' => getenv('MEDIAWIKI_SMTP_USERNAME'), // Username to use for SMTP authentication (if being used)
        'password' => getenv('MEDIAWIKI_SMTP_PASSWORD') // Password to use for SMTP authentication (if being used)
    );
}

###########################################
############ Extension Section ############
###########################################
#VisualEditor
if (getenv('MEDIAWIKI_ENABLE_VISUAL_EDITOR') == '' // Deprecated
|| getenv('MEDIAWIKI_ENABLE_VISUAL_EDITOR') == '1' // Deprecated
|| getenv('MEDIAWIKI_EXTENSION_VISUAL_EDITOR_ENABLED') == ''
|| getenv('MEDIAWIKI_EXTENSION_VISUAL_EDITOR_ENABLED') == '1') {
    wfLoadExtension('VisualEditor');
    $wgDefaultUserOptions['visualeditor-enable'] = 1;
    $wgVirtualRestConfig['modules']['parsoid'] = array(
        'url' => 'http://localhost:8142',
        'domain' => 'localhost',
        'prefix' => ''
    );
    $wgSessionsInObjectCache = true;
    $wgVirtualRestConfig['modules']['parsoid']['forwardCookies'] = true;
}

#User Merge
if (getenv('MEDIAWIKI_EXTENSION_USER_MERGE_ENABLED') == ''
|| getenv('MEDIAWIKI_EXTENSION_USER_MERGE_ENABLED') == '1') {
    wfLoadExtension('UserMerge');
    $wgGroupPermissions['bureaucrat']['usermerge'] = true;
    $wgGroupPermissions['sysop']['usermerge'] = true;
    $wgUserMergeProtectedGroups = array();
}

#Input Box
wfLoadExtension( 'InputBox' );
#BoilerPlate (template)
wfLoadExtension( 'BoilerPlate' );
#Newest Pages
wfLoadExtension( 'NewestPages' );
#Parser functions
wfLoadExtension( 'ParserFunctions' );
$wgPFEnableStringFunctions = true;
#Admin Links
wfLoadExtension( 'AdminLinks' );
#MSLinks
#Usage:
#{{#l:Testfile.zip}}
#{{#l:Testfile.zip|Description}}
#{{#l:Testfile.zip|Description|right}}
wfLoadExtension( 'MsLinks' );
$wgMSL_FileTypes = array(
                          "no" => "no_icon.png",
                          "jpg" => "image_icon.png",
                          "bmp" => "image_icon.png",
                          "png" => "image_icon.png",
                          "tiff" => "image_icon.png",
                          "tif" => "image_icon.png",
                          "psd" => "image_ps_icon.png",
                          "pdf" => "pdf_icon.png",
                          "pps" => "pps_icon.png",
                          "ppt" => "pps_icon.png",
                          "pptx" => "pps_icon.png",
                          "xls" => "xls_icon.png",
                          "xlsx" => "xls_icon.png",
                          "doc" => "doc_icon.png",
                          "docx" => "doc_icon.png",
                          "dot" => "doc_icon.png",
                          "dotx" => "doc_icon.png",
                          "rtf" => "doc_icon.png",
                          "txt" => "txt_icon.png",
                          "html" => "code_icon.png",
                          "php" => "code_icon.png",
                          "exe" => "exe_icon.gif",
                          "asc" => "txt_icon.png",
                          "dwg" => "dwg_icon.gif",
                          "zip" => "zip_icon.png",
                          "mp3"  => "music_icon.png",
                   );



# Load extra settings
require 'ExtraLocalSettings.php';
