<?php
global $global, $config;
$global['isIframe'] = 1;
// is online
// recorder
// live users

$global['ignoreUserMustBeLoggedIn'] = 1;
if (!isset($global['systemRootPath'])) {
    $configFile = '../../videos/configuration.php';
    require_once $configFile;
}


$bodyClass = '';
$key = '';
$live_servers_id = '';
$live_index = '';
$users_id = User::getId();

if (!empty($_REQUEST['logoff'])) {
    User::logoff();
}
$html = '';
if (!empty($_REQUEST['user']) && !empty($_REQUEST['pass'])) {
    User::loginFromRequest();
} 
if (User::isLogged()) {
    if (!empty($_REQUEST['key'])) {
        $key = $_REQUEST['key'];
        $live_servers_id = @$_REQUEST['live_servers_id'];
        $live_index = @$_REQUEST['live_index'];
    } else if (User::isLogged()) {
        $lth = LiveTransmitionHistory::getLatestFromUser($users_id);
        $key = $lth['key'];
        $live_servers_id = $lth['live_servers_id'];
        $live_index = $lth['live_index'];
    }

    if (!empty($key)) {
        $isLive = 1;
        setLiveKey($key, $live_servers_id, $live_index);
        if (!empty(LiveTransmitionHistory::isLive($key, $live_servers_id))) {
            $bodyClass = 'isLiveOnline';
        }
    }
    if (isLive()) {
        //var_dump($livet, $getLiveKey, isLive());exit;
        if (AVideoPlugin::isEnabledByName('Chat2')) {

            $chat = new ChatIframeOptions();
            $chat->set_room_users_id($users_id);
            $chat->set_live_transmitions_history_id($latest['id']);
            $chat->set_iframe(1);
            $chat->set_noFade(1);
            $chat->set_bubblesOnly(1);
            $chat->set_addChatTextBox(1);
            $chat->set_doNotAllowUsersSendMessagesToEachOther(1);
            $chat->set_hideBubbleButtons(1);
            $iframeURL = $chat->getURL(true);

            $html = '<iframe 
        id="yptchat2Iframe"
        src="' . $iframeURL . '" 
        frameborder="0" scrolling="no" title="chat widget" 
        allowtransparency="true" 
        sandbox="allow-popups allow-popups-to-escape-sandbox allow-same-origin allow-scripts allow-forms allow-modals allow-orientation-lock allow-pointer-lock allow-presentation allow-top-navigation"
        style="
        outline: none; 
        visibility: visible; 
        resize: none; 
        box-shadow: none; 
        overflow: visible; 
        background: none transparent; 
        opacity: 1; 
        padding: 0px; 
        margin: 0px; 
        transition-property: none; 
        transform: none; 
        width: 100%; 
        z-index: 999999; 
        cursor: auto; 
        float: none; 
        border-radius: unset; 
        pointer-events: auto; 
        display: block; 
        height: 100vh;"></iframe>';

            //include "{$global['systemRootPath']}plugin/Chat2/index.php";
            //return false;
        }
        if (AVideoPlugin::isEnabledByName('LiveUsers')) {
            $html .= getLiveUsersLabelHTML();
            //$html .= '<div id="LiveUsersLabelLive">'.getLiveUsersLabelLive($livet['key'], $livet['live_servers_id']).'</div>';
            //$html .= '<div id="LiveUsersLabelLive">'.getLiveUsersLabelLive($lt['key'], $lt['live_servers_id']).'</div>';
            //$html .= getIncludeFileContent($global['systemRootPath'] . 'plugin/Live/view/menuRight.php');
            //var_dump($lt);exit;
        }
        
        if (AVideoPlugin::isEnabledByName('SendRecordedToEncoder')) {
            $html .= '<!-- SendRecordedToEncoder start -->';
            $html .= getIncludeFileContent($global['systemRootPath'] . 'plugin/SendRecordedToEncoder/actionButtonLive.php');
            $html .= '<!-- SendRecordedToEncoder end -->';
        }
    }
} else {
    header("Location: {$global['webSiteRootURL']}plugin/MobileManager/loginPage.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="">
    <head>
        <?php
        include $global['systemRootPath'] . 'view/include/head.php';
        ?>
        <style>
            #accessibility-toolbar, footer, #socket_info_container{
                display: none !important;
            }
            body {
                padding: 0;
            }

            .liveUsersLabel{
                position: fixed;
                top: 10px !important;
            }
            .liveUsersLabel{
                left: 20px !important;
            }
            #recorderToEncoderActionButtons{
                position: absolute;
                top: 40px;
                left: 0;
                width: 100%;
            }
            .showWhenClosed, #closeRecorderButtons{
                display: none;
            }
            #recorderToEncoderActionButtons.closed .recordLiveControlsDiv,
            #recorderToEncoderActionButtons.closed .hideWhenClosed{
                display: none !important;
            }
            #recorderToEncoderActionButtons.closed .showWhenClosed,
            .isLiveOnline #closeRecorderButtons{
                display: inline-block !important;
            }
        </style>
    </head>

    <body style="background-color: transparent; <?php echo $bodyClass; ?>">
        <?php
        echo $html;
        ?>
        <?php
        include $global['systemRootPath'] . 'view/include/footer.php';
        ?>
        <script>
            function socketLiveONCallback(json) {
                console.log('socketLiveONCallback MobileManager', json);
                if ((json.users_id == '<?php echo User::getId(); ?>' && json.live_transmitions_history_id) || (!empty(json.key) && json.key == '<?php echo @$_REQUEST['key']; ?>')) {
                    modal.showPleaseWait();
                    var url = addGetParam(window.location.href, 'live_transmitions_history_id', json.live_transmitions_history_id);
                    url = addGetParam(url, 'key', json.key);
                    url = addGetParam(url, 'live_servers_id', json.live_servers_id);
                    url = addGetParam(url, 'live_schedule', json.live_schedule);
                    url = addGetParam(url, 'live_index', json.live_index);
                    document.location = url;
                }
            }
        </script>
    </body>
</html>