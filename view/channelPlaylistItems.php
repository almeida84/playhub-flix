<?php
global $global, $config, $isChannel;
$isChannel = 1; // still workaround, for gallery-functions, please let it there.
if (!isset($global['systemRootPath'])) {
    require_once '../videos/configuration.php';
}
require_once $global['systemRootPath'] . 'objects/user.php';
require_once $global['systemRootPath'] . 'objects/video.php';
require_once $global['systemRootPath'] . 'objects/playlist.php';

session_write_close();

if (empty($_GET['channelName'])) {
    if (User::isLogged()) {
        $_GET['user_id'] = User::getId();
    } else {
        return false;
    }
} else {
    $user = User::getChannelOwner($_GET['channelName']);
    if (!empty($user)) {
        $_GET['user_id'] = $user['id'];
    } else {
        $_GET['user_id'] = $_GET['channelName'];
    }
}
$user_id = $_GET['user_id'];

$timeLog2 = __FILE__ . " - channelPlayList: {$_GET['channelName']}";
TimeLogStart($timeLog2);

$publicOnly = true;
$isMyChannel = false;
if (User::isLogged() && $user_id == User::getId()) {
    $publicOnly = false;
    $isMyChannel = true;
}
if (empty($_GET['current'])) {
    $_POST['current'] = 1;
} else {
    $_POST['current'] = intval($_GET['current']);
}
$_REQUEST['rowCount'] = 4;
$sort = @$_POST['sort'];
$_POST['sort'] = array();
$_POST['sort']['created'] = 'DESC';
$playlists = PlayList::getAllFromUser($user_id, $publicOnly, false, 0, 0, true);
$_POST['sort'] = $sort;
$current = $_POST['current'];
unset($_POST['current']);
?>
<div class="programsContainerItem">
    <?php
    if (empty($playlists)) {
        if ($current == 1) {
            echo "<div class='alert alert-warning'><i class=\"fas fa-exclamation-triangle\"></i> " . __('Sorry you do not have anything available') . "</div>";
        }
        die("</div>");
    }
    $playListsObj = AVideoPlugin::getObjectData("PlayLists");
    TimeLogEnd($timeLog2, __LINE__);
    $channelName = @$_GET['channelName'];
    unset($_GET['channelName']);
    $startC = microtime(true);
    TimeLogEnd($timeLog2, __LINE__);

    $countSuccess = 0;
    $get = [];
    if (!empty($_GET['channelName'])) {
        $get = ['channelName' => $_GET['channelName']];
    }
    $program = AVideoPlugin::loadPluginIfEnabled('PlayLists');
    foreach ($playlists as $key => $playlist) {
        @$timesC[__LINE__] += microtime(true) - $startC;
        $startC = microtime(true);
        $_REQUEST['current'] = 1;
        $_REQUEST['rowCount'] = 6;
        $videosArrayId = PlayList::getVideosIdFromPlaylist($playlist['id']);
        $_REQUEST['current'] = 1;
        $_REQUEST['rowCount'] = 6;
        @$timesC[__LINE__] += microtime(true) - $startC;
        $startC = microtime(true);
        $rowCount = $_POST['rowCount'];
        $_REQUEST['rowCount'] = 6;

        //getAllVideos($status = "viewable", $showOnlyLoggedUserVideos = false, $ignoreGroup = false, $videosArrayId = array(), $getStatistcs = false, $showUnlisted = false, $activeUsersOnly = true)
        if (empty($videosArrayId) && ($playlist['status'] == "favorite" || $playlist['status'] == "watch_later")) {
            unset($playlists[$key]);
            continue;
        } elseif (empty($videosArrayId)) {
            $videosP = [];
        } else {
            $videosP = Video::getAllVideos("viewable", false, true, $videosArrayId, false, true);
        }//var_dump($videosArrayId, $videosP); exit;

        $totalDuration = 0;
        foreach ($videosP as $value) {
            $totalDuration += durationToSeconds($value['duration']);
        }

        $_REQUEST['rowCount'] = $rowCount;
        @$timesC[__LINE__] += microtime(true) - $startC;
        $startC = microtime(true);
        //_error_log("channelPlaylist videosP: ".json_encode($videosP));
        $videosP = PlayList::sortVideos($videosP, $videosArrayId);
        @$timesC[__LINE__] += microtime(true) - $startC;
        $startC = microtime(true);
        //_error_log("channelPlaylist videosP2: ".json_encode($videosP));
        //_error_log("channelPlaylist videosArrayId: ".json_encode($videosArrayId));
        @$timesC[__LINE__] += microtime(true) - $startC;
        $startC = microtime(true);
        $countSuccess++;
        ?>

        <div class="panel panel-default" playListId="<?php echo $playlist['id']; ?>">
            <div class="panel-heading">

                <strong style="font-size: 1.1em;" class="playlistName">
                    <?php echo __($playlist['name']); ?> (<?php echo secondsToDuration($totalDuration); ?>)
                </strong>
                <?php
                PlayLists::getPLButtons($playlist['id']);
                ?>
            </div>

            <?php
            if (!empty($videosArrayId)) {
                ?>

                <div class="panel-body">
                    <?php
                    $serie = PlayLists::isPlayListASerie($playlist['id']);
                    if (!empty($serie)) {
                        $images = Video::getImageFromFilename($serie['filename'], $serie['type'], true);
                        $imgGif = $images->thumbsGif;
                        $poster = $images->thumbsJpg;
                        $category = new Category($serie['categories_id']);
                        ?>
                        <div style="overflow: hidden;">
                            <div style="display: flex; margin-bottom: 10px;">
                                <div style="margin-right: 5px; min-width: 30%;" >
                                    <img src="<?php echo $poster; ?>" alt="<?php echo $serie['title']; ?>" class="img img-responsive" style="max-height: 200px;" />
                                </div>
                                <div>
                                    <a class="h6 galleryLink hrefLink" href="<?php echo Video::getLink($serie['id'], $serie['clean_title']); ?>" title="<?php echo getSEOTitle($serie['title']); ?>">
                                        <strong class="title"><?php echo getSEOTitle($serie['title']); ?></strong>
                                    </a>
                                    <small class="galeryDetails">
                                        <div class="galleryTags">
                                            <a class="label label-default" href="<?php echo Video::getLink($serie['id'], $category->getClean_name(), false, $get); ?>">
                                                <?php
                                                if (!empty($category->getIconClass())) {
                                                    ?>
                                                    <i class="<?php echo $category->getIconClass(); ?>"></i>
                                                <?php }
                                                ?>
                                                <?php echo $category->getName(); ?>
                                            </a>
                                            <?php
                                            $serie['tags'] = Video::getTags($serie['id']);
                                            foreach ($serie['tags'] as $value2) {
                                                if ($value2->label === __("Group")) {
                                                    ?>
                                                    <span class="label label-<?php echo $value2->type; ?>"><?php echo $value2->text; ?></span>
                                                    <?php
                                                }
                                            }
                                            ?>
                                        </div>
                                        <i class="far fa-clock"></i>
                                        <?php echo humanTiming(strtotime($serie['created']), 0, true,true); ?>

                                        <?php
                                        if (!empty($serie['trailer1'])) {
                                            ?>
                                            <button class="btn btn-xs btn-warning" onclick="avideoModalIframe('<?php echo parseVideos($serie['trailer1'], 1, 0, 0, 0, 1, 0, 'fill'); ?>');">
                                                <span class="fa fa-film"></span>
                                                <span class="hidden-xs"><?php echo __("Trailer"); ?></span>
                                            </button>
                                        <?php }
                                        ?>
                                    </small>
                                    <p>
                                        <?php echo $serie['description']; ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php }
                    ?>
                    <div class="clearfix"></div>
                    <?php
                    $count = 0;
                    $_REQUEST['site'] = get_domain($global['webSiteRootURL']);
                    foreach ($videosP as $value) {
                        // make sure the video exists
                        if (empty($value['created'])) {
                            $count++;
                            continue;
                        }
                        $episodeLink = "{$global['webSiteRootURL']}program/{$playlist['id']}/{$count}/{$channelName}/" . urlencode(cleanURLName($playlist['name'])) . "/" . cleanURLName($value['clean_title']);
                        $count++;
                        $name = User::getNameIdentificationById($value['users_id']);
                        $class = '';
                        ?>
                        <div class="col-lg-2 col-md-4 col-sm-4 col-xs-6 galleryVideo <?php echo $class; ?> " id="<?php echo $value['id']; ?>" style="padding: 1px;">
                            <?php                            
                                echo Video::getVideoImagewithHoverAnimationFromVideosId($value);
                            ?>
                            <a class="h6 galleryLink hrefLink" href="<?php echo $episodeLink; ?>" title="<?php echo getSEOTitle($value['title']); ?>">
                                <strong class="title"><?php echo getSEOTitle($value['title']); ?></strong>
                            </a>
                            <div class="galeryDetails" style="min-height: 60px;">
                                <div>
                                    <?php
                                    $value['tags'] = Video::getTags($value['id']);
                                    foreach ($value['tags'] as $value2) {
                                        if ($value2->label === __("Group")) {
                                            ?>
                                            <span class="label label-<?php echo $value2->type; ?>"><?php echo $value2->text; ?></span>
                                            <?php
                                        }
                                    }
                                    ?>
                                </div>
                                <?php
                                if (empty($advancedCustom->doNotDisplayViews)) {
                                    ?>
                                    <div>
                                        <i class="fa fa-eye"></i>
                                        <span itemprop="interactionCount">
                                            <?php echo number_format($value['views_count'], 0); ?> <?php echo __("Views"); ?>
                                        </span>
                                    </div>
                                <?php }
                                ?>

                                <div>
                                    <i class="far fa-clock"></i>
                                    <?php echo humanTiming(strtotime($value['videoCreation']), 0, true,true); ?>
                                </div>
                                <div>
                                    <i class="fa fa-user"></i>
                                    <?php echo $name; ?>
                                </div>
                                <?php
                                if (Video::canEdit($value['id'])) {
                                    ?>
                                    <div>
                                        <a href="<?php echo $global['webSiteRootURL']; ?>mvideos?video_id=<?php echo $value['id']; ?>"><i class="fa fa-edit"></i> <?php echo __("Edit Video"); ?></a>


                                    </div>
                                <?php }
                                ?>
                                <?php
                                if ($isMyChannel) {
                                    ?>
                                    <div>
                                        <span style=" cursor: pointer;" class="btn-link text-primary removeVideo" playlist_id="<?php echo $playlist['id']; ?>" video_id="<?php echo $value['id']; ?>">
                                            <i class="fa fa-trash"></i> <?php echo __("Remove"); ?>
                                        </span>
                                    </div>
                                <?php }
                                ?>
                            </div>
                        </div>
                    <?php }
                    ?>
                </div>

                <?php
            }
            if (PlayLists::showTVFeatures()) {
                ?>
                <div class="panel-footer">
                    <?php
                    $_REQUEST['user_id'] = $user_id;
                    $_REQUEST['playlists_id'] = $playlist['id'];
                    include $global['systemRootPath'] . 'plugin/PlayLists/epg.html.php';
                    ?>
                </div>
            <?php }
            ?>
        </div>
        <?php
    }
    if (!empty($videosP) && empty($countSuccess)) {
        header("Location: {$global['webSiteRootURL']}view/channelPlaylistItems.php?current=" . (count($playlists) ? $_POST['current'] + 1 : $_POST['current']) . "&channelName={$_GET['channelName']}");
        exit;
    }
    TimeLogEnd($timeLog2, __LINE__);
    $_GET['channelName'] = $channelName;
    ?>
    <script>

        $(function () {
            $('.removeVideo').click(function () {
                currentObject = this;
                swal({
                    title: "<?php echo __("Are you sure?"); ?>",
                    text: "<?php echo __("You will not be able to recover this action!"); ?>",
                    icon: "warning",
                    buttons: true,
                    dangerMode: true,
                })
                        .then(function (willDelete) {
                            if (willDelete) {

                                modal.showPleaseWait();
                                var playlist_id = $(currentObject).attr('playlist_id');
                                var video_id = $(currentObject).attr('video_id');
                                $.ajax({
                                    url: '<?php echo $global['webSiteRootURL']; ?>objects/playlistRemoveVideo.php',
                                    data: {
                                        "playlist_id": playlist_id,
                                        "video_id": video_id
                                    },
                                    type: 'post',
                                    success: function (response) {
                                        reloadPlayLists();
                                        $(".playListsIds" + video_id).prop("checked", false);
                                        $(currentObject).closest('.galleryVideo').fadeOut();
                                        modal.hidePleaseWait();
                                    }
                                });
                            }
                        });
            });
            $('.deletePlaylist').click(function () {
                currentObject = this;
                swal({
                    title: "<?php echo __("Are you sure?"); ?>",
                    text: "<?php echo __("You will not be able to recover this action!"); ?>",
                    icon: "warning",
                    buttons: true,
                    dangerMode: true,
                })
                        .then(function (willDelete) {
                            if (willDelete) {

                                modal.showPleaseWait();
                                var playlist_id = $(currentObject).attr('playlist_id');
                                console.log(playlist_id);
                                $.ajax({
                                    url: '<?php echo $global['webSiteRootURL']; ?>objects/playlistRemove.php',
                                    data: {
                                        "playlist_id": playlist_id
                                    },
                                    type: 'post',
                                    success: function (response) {
                                        $(currentObject).closest('.panel').slideUp();
                                        modal.hidePleaseWait();
                                    }
                                });
                            }
                        });
            });
            $('.statusPlaylist').click(function () {
                var playlist_id = $(this).attr('playlist_id');
                var status = "public";
                if ($('#statusPrivate' + playlist_id).is(":visible")) {
                    status = "public";
                    $('.statusPlaylist' + playlist_id + ' span').hide();
                    $('#statusPublic' + playlist_id).fadeIn();
                } else if ($('#statusPublic' + playlist_id).is(":visible")) {
                    status = "unlisted";
                    $('.statusPlaylist' + playlist_id + ' span').hide();
                    $('#statusUnlisted' + playlist_id).fadeIn();
                } else if ($('#statusUnlisted' + playlist_id).is(":visible")) {
                    status = "private";
                    $('.statusPlaylist' + playlist_id + ' span').hide();
                    $('#statusPrivate' + playlist_id).fadeIn();
                }
                modal.showPleaseWait();
                console.log(playlist_id);
                $.ajax({
                    url: '<?php echo $global['webSiteRootURL']; ?>objects/playlistStatus.php',
                    data: {
                        "playlist_id": playlist_id,
                        "status": status
                    },
                    type: 'post',
                    success: function (response) {

                        modal.hidePleaseWait();
                    }
                });
            });
            $('.renamePlaylist').click(function () {
                currentObject = this;
                swal({
                    text: "<?php echo __("Change Playlist Name"); ?>!",
                    content: "input",
                    button: {
                        text: "<?php echo __("Confirm Playlist name"); ?>",
                        closeModal: false,
                    },
                }).then(function (name) {
                    if (!name)
                        throw null;
                    modal.showPleaseWait();
                    var playlist_id = $(currentObject).attr('playlist_id');
                    console.log(playlist_id);
                    return fetch('<?php echo $global['webSiteRootURL']; ?>objects/playlistRename.php?playlist_id=' + playlist_id + '&name=' + encodeURI(name));
                }).then(function (results) {
                    return results.json();
                }).then(function (response) {
                    if (response.error) {
                        avideoAlert("<?php echo __("Sorry!"); ?>", response.msg, "error");
                        modal.hidePleaseWait();
                    } else {
                        $(currentObject).closest('.panel').find('.playlistName').text(response.name);
                        swal.stopLoading();
                        swal.close();
                        modal.hidePleaseWait();
                    }
                }).catch(function (err) {
                    if (err) {
                        swal("Oh noes!", "The AJAX request failed!", "error");
                    } else {
                        swal.stopLoading();
                        swal.close();
                    }
                    modal.hidePleaseWait();
                });
            });
            $('.sortNow').click(function () {
                var $val = $(this).siblings("input").val();
                sortNow(this, $val);
            });
            $('.video_order').keypress(function (e) {
                if (e.which == 13) {
                    sortNow(this, $(this).val());
                }
            });
        });
    </script>
    <!--
    channelPlaylist
    <?php
    $timesC[__LINE__] = microtime(true) - $startC;
    $startC = microtime(true);
    foreach ($timesC as $key => $value) {
        echo "Line: {$key} -> {$value}\n";
    }
    $_POST['current'] = $current;
    ?>
    -->
</div>
<p class="pagination">
    <a class="pagination__next" href="<?php echo $global['webSiteRootURL']; ?>view/channelPlaylistItems.php?current=<?php echo count($playlists) ? $_POST['current'] + 1 : $_POST['current']; ?>&channelName=<?php echo $_GET['channelName']; ?>"></a>
</p>