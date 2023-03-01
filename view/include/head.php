<?php
$timeLogHead = TimeLogStart("include/head.php");
require_once $global['systemRootPath'] . 'plugin/AVideoPlugin.php';
TimeLogEnd($timeLogHead, __LINE__);
$head = AVideoPlugin::getHeadCode();
TimeLogEnd($timeLogHead, __LINE__);
$custom = "The Best YouTube Clone Ever - AVideo";
$extraPluginFile = $global['systemRootPath'] . 'plugin/Customize/Objects/ExtraConfig.php';
if (empty($advancedCustom)) {
    $advancedCustom = AVideoPlugin::getObjectData("CustomizeAdvanced");
}
TimeLogEnd($timeLogHead, __LINE__);
if (is_object($video)) {
    $video = Video::getVideoLight($video->getId());
}
TimeLogEnd($timeLogHead, __LINE__);
$custom = [];

$customizePluginDescription = '';
if (file_exists($extraPluginFile) && AVideoPlugin::isEnabledByName("Customize")) {
    require_once $extraPluginFile;
    $ec = new ExtraConfig();
    $customizePluginDescription = $ec->getDescription();
    $custom[] = $customizePluginDescription;
}

TimeLogEnd($timeLogHead, __LINE__);
if (!empty($poster)) {
    $subTitle = str_replace(['"', "\n", "\r"], ["", "", ""], strip_tags($video['description']));
    $custom = [];
    $custom[] = $subTitle;
    if (!empty($video["category"])) {
        $custom[] = $video["category"];
    }
}

TimeLogEnd($timeLogHead, __LINE__);
if (!empty($_GET['catName'])) {
    $category = Category::getCategoryByName($_GET['catName']);
    $description = str_replace(['"', "\n", "\r"], ["", "", ""], strip_tags($category['description']));
    $custom = [];
    $custom[] = $description;
    $custom[] = $category['name'];
}

TimeLogEnd($timeLogHead, __LINE__);
foreach ($custom as $key => $value) {
    if (empty($value)) {
        unset($custom[$key]);
    }
}

$theme = getCurrentTheme();

if (empty($config)) {
    $config = new Configuration();
}
TimeLogEnd($timeLogHead, __LINE__);
//$content = _ob_get_clean();
_ob_start();
echo $content;

$keywords = strip_tags($advancedCustom->keywords);
$head_videos_id = getVideos_id();
if (!empty($head_videos_id)) {
    $tags = Video::getSeoTags($head_videos_id);
    echo $tags['head'];
}
?>
<script class="doNotSepareteTag" src="<?php echo getURL('view/js/swRegister.js'); ?>" type="text/javascript"></script>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="device_id" content="<?php echo getDeviceID(); ?>">
<meta name="keywords" content=<?php printJSString($keywords); ?>>
<link rel="manifest" href="<?php echo $global['webSiteRootURL']; ?>manifest.json">
<link rel="apple-touch-icon" sizes="180x180" href="<?php echo $config->getFavicon(true); ?>">
<link rel="icon" type="image/png" href="<?php echo $config->getFavicon(true); ?>">
<link rel="shortcut icon" href="<?php echo $config->getFavicon(); ?>" sizes="16x16,24x24,32x32,48x48,144x144">
<meta name="msapplication-TileImage" content="<?php echo $config->getFavicon(true); ?>">

<link href="<?php echo getURL('node_modules/fontawesome-free/css/all.min.css'); ?>" rel="stylesheet" type="text/css"/>
<?php
if (!isBot()) {
    ?>
    <link href="<?php echo getURL('view/css/font-awesome-animation.min.css'); ?>" rel="stylesheet" type="text/css" onload="this.media = 'all'"/>
    <link href="<?php echo getURL('node_modules/jquery-toast-plugin/dist/jquery.toast.min.css'); ?>" rel="stylesheet" type="text/css" onload="this.media = 'all'"/>
    <link href="<?php echo getURL('view/bootstrap/jquery-bootstrap-scrolling-tabs/jquery.scrolling-tabs.min.css'); ?>" rel="stylesheet" type="text/css" onload="this.media = 'all'"/>
    <link href="<?php echo getURL('view/js/webui-popover/jquery.webui-popover.min.css'); ?>" rel="stylesheet" type="text/css" onload="this.media = 'all'"/>
    <link href="<?php echo getURL('view/js/bootgrid/jquery.bootgrid.css'); ?>" rel="stylesheet" type="text/css" onload="this.media = 'all'"/>
    <link href="<?php echo getURL('node_modules/jquery-ui-dist/jquery-ui.min.css'); ?>" rel="stylesheet" type="text/css" media="print" onload="this.media = 'all'"/>
    <link href="<?php echo getURL('view/css/flagstrap/css/flags.css'); ?>" rel="stylesheet" type="text/css" media="print" onload="this.media = 'all'"/>
    <?php
}
if (!isVideo()) {
    if (!empty($metaDescription)) {
        $metaDescription = implode(" - ", $custom) . " - {$metaDescription}";
    } else {
        $metaDescription = implode(" - ", $custom);
    }
    // for SEO to not rise an error of duplicated title or description of same pages with and without last slash
    $metaDescription .= getSEOComplement(["addAutoPrefix" => false]);
    $metaDescription = getSEODescription($metaDescription);
    echo '<meta name="description" content="' . $metaDescription . '">';
}
if (empty($advancedCustom->disableAnimations)) {
    ?>
    <link href="<?php echo getURL('node_modules/animate.css/animate.min.css'); ?>" rel="stylesheet"  type="text/css"  media="print" onload="this.media = 'all'"/>
    <?php
}
include $global['systemRootPath'] . 'view/include/bootstrap.css.php';
?>
<?php
TimeLogEnd($timeLogHead, __LINE__);
?>
<link href="<?php echo getURL('view/css/custom/' . $theme . '.css'); ?>" rel="stylesheet" type="text/css" id="customCSS"/>
<?php
if (empty($global['userBootstrapLatest'])) {
    $filename = Video::getStoragePath() . "cache/custom.css";
}
if ($theme === "default" && !empty($customizePlugin->showCustomCSS) && file_exists($filename)) {
    echo '<link href="' . getURL('videos/cache/custom.css') . '" rel="stylesheet" type="text/css" id="pluginCustomCss" />';
} else {
    if ($theme !== "default") {
        echo "<!-- theme is not default -->";
    }
    if (empty($customizePlugin->showCustomCSS)) {
        echo "<!-- showCustomCSS is empty -->";
    }
    if (!file_exists($filename)) {
        echo "<!-- css file does not exist -->";
    }
    if (!empty($global['userBootstrapLatest'])) {
        echo "<!-- Using Bootstrap latest -->";
    }
    echo '<link href="" rel="stylesheet" type="text/css" id="pluginCustomCss" />';
}
?>
<link href="<?php echo getURL('view/css/main.css'); ?>" rel="stylesheet" type="text/css"/>    
<?php
TimeLogEnd($timeLogHead, __LINE__);
if (isRTL()) {
    ?>

    <style>
        .principalContainer, #mainContainer, #bigVideo, .mainArea, .galleryVideo, #sidebar, .navbar-header li,
        .panel-heading, th, td, a, .panel-footer, .label {
            direction:rtl;
            unicode-bidi:embed;
        }
        #sidebar .nav{
            padding-right: 0;
        }
        .dropdown-menu, .navbar-header li a, #sideBarContainer .btn {
            text-align: right !important;
        }
        .dropdown-submenu a{
            width: 100%;
        }
        .galeryDetails div{
            float: right !important;
        }
        #saveCommentBtn{
            border-width: 1px;
            border-right-width: 0;
        }
        .pull-left {
            float: right!important;
        }
        .pull-right {
            float: left!important;
        }
        .material-switch{
            direction: initial;
        }
    </style>    
    <?php
}
?>
<script src="<?php echo getURL('node_modules/jquery/dist/jquery.min.js'); ?>"></script>
<script class="doNotSepareteTag">
    var useIframe = <?php echo json_encode(useIframe()); ?>;
    var webSiteRootURL = '<?php echo $global['webSiteRootURL']; ?>';
    var my_users_id = <?php echo intval(User::getId()); ?>;
    var my_identification = <?php echo json_encode(User::getNameIdentification()); ?>;
    var mediaId = <?php echo json_encode(getVideos_id()); ?>;
    var player;
</script>

<script id="infoForNonCachedPages">
    var _serverTime = "<?php echo time(); ?>";
    var _serverDBTime = "<?php echo getDatabaseTime(); ?>";
    var _serverTimeString = "<?php echo date('Y-m-d H:i:s'); ?>";
    var _serverDBTimeString = "<?php echo date('Y-m-d H:i:s', getDatabaseTime()); ?>";
    var _serverTimezone = "<?php echo (date_default_timezone_get()); ?>";
    var _serverSystemTimezone = "<?php echo (getSystemTimezone()); ?>";
    var avideoModalIframeFullScreenCloseButton = <?php echo json_encode(getHamburgerButton('avideoModalIframeFullScreenCloseButton', 2, 'class="btn btn-default pull-left hamburger " onclick="avideoModalIframeFullScreenClose();"', true)); ?>;
    var avideoModalIframeFullScreenCloseButtonSmall = <?php echo json_encode(getHamburgerButton('avideoModalIframeFullScreenCloseButton', 4, 'class="btn btn-default btn-sm pull-left hamburger " onclick="avideoModalIframeFullScreenClose();"', true)); ?>;
    var PHPSESSID = "<?php echo session_id(); ?>";
</script>
    <?php
    if (!isOffline() && !$config->getDisable_analytics()) {
        ?>
    <script>
        // AVideo Analytics
        (function (i, s, o, g, r, a, m) {
            i['GoogleAnalyticsObject'] = r;
            i[r] = i[r] || function () {
                (i[r].q = i[r].q || []).push(arguments)
            }, i[r].l = 1 * new Date();
            a = s.createElement(o),
                    m = s.getElementsByTagName(o)[0];
            a.async = 1;
            a.src = g;
            m.parentNode.insertBefore(a, m)
        })(window, document, 'script', 'https://www.google-analytics.com/analytics.js', 'ga');

        ga('create', 'UA-96597943-1', 'auto', 'aVideo');
        ga('aVideo.send', 'pageview');
    </script>
    <?php
}
TimeLogEnd($timeLogHead, __LINE__);
if (!isBot()) {
    echo $config->getHead();
}
TimeLogEnd($timeLogHead, __LINE__);
echo $head;
if (!empty($video)) {
    if (!empty($video['users_id'])) {
        $userAnalytics = new User($video['users_id']);
        echo $userAnalytics->getAnalytics();
        unset($userAnalytics);
    }
}
TimeLogEnd($timeLogHead, __LINE__);
ogSite();
TimeLogEnd($timeLogHead, __LINE__);
?>