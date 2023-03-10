<?php
if (!empty($playNowVideo)) {
    $video = $playNowVideo;
}
if (empty($video['id'])) {
    return false;
}
$videoContext = $video;
if (empty($playerSkinsObj)) {
    $playerSkinsObj = AVideoPlugin::getObjectData("PlayerSkins");
}

if (isEmbed() && $playerSkinsObj->contextMenuDisableEmbedOnly) {
    ?>
    <script>
        $(document).ready(function () {
            $('#mainVideo').bind('contextmenu', function () {
                return false;
            });
        });
    </script>    
    <?php
    return false;
}

$canDownloadVideosFromVideo = CustomizeUser::canDownloadVideosFromVideo($videoContext['id']);

$contextMenu = array();

if ($playerSkinsObj->contextMenuLoop) {
    $contextMenu[] = "{name: '" . __("Loop") . "',
                        onClick: function () {
                            toogleImageLoop($(this));
                        }, iconClass: 'fas fa-sync loopButton'
                    }";
}
if ($playerSkinsObj->contextMenuCopyVideoURL) {
    $contextMenu[] = "{name: '" . __("Copy video URL") . "',
                        onClick: function () {
                            copyToClipboard($('#linkFriendly').val());
                        }, iconClass: 'fas fa-link'
                    }";
}
if ($playerSkinsObj->contextMenuCopyVideoURLCurrentTime) {
    $contextMenu[] = "{name: '" . __("Copy video URL at current time") . "',
                        onClick: function () {
                            copyToClipboard($('#linkCurrentTime').val());
                        }, iconClass: 'fas fa-link'
                    }";
}
if ($playerSkinsObj->contextMenuCopyEmbedCode) {
    $contextMenu[] = "{name: '" . __("Copy embed code") . "',
                        onClick: function () {
                            $('#textAreaEmbed').focus();
                            copyToClipboard($('#textAreaEmbed').val());
                        }, iconClass: 'fas fa-code'
                    }";
}
if ($canDownloadVideosFromVideo) {
    if ($videoContext['type'] == "video") {
        $files = getVideosURL($videoContext['filename']);
        foreach ($files as $key => $theLink) {
            $notAllowedKeys = array('m3u8');
            if (empty($advancedCustom->showImageDownloadOption)) {
                $notAllowedKeys = array_merge($notAllowedKeys, array('jpg', 'gif', 'webp', 'pjpg'));
            }
            $keyFound = false;
            foreach ($notAllowedKeys as $notAllowedKey) {
                if (preg_match("/{$notAllowedKey}/", $key)) {
                    $keyFound = true;
                    break;
                }
            }
            if ($keyFound) {
                continue;
            }
            $contextMenu[] = "{name: '" . __("Download video") . " ({$key})',
                        onClick: function () {
                        document.location = '{$theLink['url']}?download=1&title=" . urlencode($videoContext['title'] . "_{$key}_.mp4") . "';
                                    }, iconClass: 'fas fa-download'
                            }";
        }
    } else {
        $contextMenu[] = "{name: '" . __("Download video") . " ({$key})',
                        onClick: function () {
                        document.location = '{$videoContext['videoLink']}?download=1&title=" . urlencode($videoContext['title'] . "_{$key}_.mp4") . "';
                                    }, iconClass: 'fas fa-download'
                            }";
    }
}
if ($playerSkinsObj->showSocialShareOnEmbed && $playerSkinsObj->contextMenuShare && CustomizeUser::canShareVideosFromVideo($videoContext['id'])) {
    $contextMenu[] = "{name: '" . __("Share") . "',
                        onClick: function () {
                        showSharing();
                                    }, iconClass: 'fas fa-share'
                            }";
    ?>
    <div id="SharingModal" class="modal fade" role="dialog">
        <div class="modal-dialog">
            <!-- Modal content-->
            <div class="modal-content">
                <div class="modal-body">
                    <center>
                        <?php
                        include $global['systemRootPath'] . 'view/include/social.php';
                        ?>
                    </center>
                </div>
            </div>
        </div>
    </div>    
    <?php
}
?>
<script>
    function showSharing() {
        $('#SharingModal').modal("show");
        return false;
    }

    $(document).ready(function () {
        var menu = new BootstrapMenu('#mainVideo', {
            actions: [<?php echo implode(",", $contextMenu); ?>]
        });
        if (typeof setImageLoop === 'function') {
            setImageLoop();
        }
        $('#SharingModal').modal({show: false});
    });
</script>
<input type="hidden" value="<?php echo Video::getPermaLink($videoContext['id']); ?>" class="form-control" readonly="readonly"  id="linkPermanent"/>
<input type="hidden" value="<?php echo Video::getURLFriendly($videoContext['id']); ?>" class="form-control" readonly="readonly" id="linkFriendly"/>
<input type="hidden" value="<?php echo Video::getURLFriendly($videoContext['id']); ?>?t=0" class="form-control" readonly="readonly" id="linkCurrentTime"/>
<textarea class="form-control" style="display: none;" rows="5" id="textAreaEmbed" readonly="readonly"><?php
    $embedURL = Video::getLink($videoContext['id'], $videoContext['clean_title'], true);
    $videoContextLengthInSeconds = durationToSeconds($videoContext['duration']);
    $search = array('{embedURL}', '{videoLengthInSeconds}');
    $replace = array($embedURL, $videoContextLengthInSeconds);
    $code = str_replace($search, $replace, $advancedCustom->embedCodeTemplate);
    echo htmlentities($code);
    ?></textarea>