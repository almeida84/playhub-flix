<?php
require_once '../../videos/configuration.php';
require_once $global['systemRootPath'] . 'objects/captcha.php';

$users_id = intval(@$_REQUEST['users_id']);

if (empty($users_id) || !Permissions::canAdminUsers()) {
    $users_id = User::getId();
}

if (empty($users_id)) {
    forbiddenPage('Empty users_id');
}

$user = new User($users_id);

$videos = Video::getAllVideosLight('', $users_id);
?>
<!DOCTYPE html>
<html lang="<?php echo getLanguage(); ?>">
    <head>
        <title><?php echo __("Delete User"); ?> <?php echo $user->getUser(); ?></title>
        <?php
        include $global['systemRootPath'] . 'view/include/head.php';
        ?>
    </head>
    <body class="<?php echo $global['bodyClass']; ?>">
        <?php
        include $global['systemRootPath'] . 'view/include/navbar.php';
        ?>
        <div class="container-fluid">
            <div class="panel panel-default">
                <div class="panel-heading">
                    <?php
                            echo Video::getCreatorHTML($users_id);
                    ?>
                </div>
                <div class="panel-body">
                    <div class="alert alert-danger">
                        <?php echo __('Delete User'); ?>: <strong><?php echo $user->getUser(); ?></strong><br>
                        <?php
                        echo __('This action can not be recovered!');
                        ?>
                    </div>

                    <?php
                    if (!empty($videos)) {
                        ?>
                        <div class="alert alert-danger">
                            <?php
                            echo __('Are you sure you want to delete the user and all videos?');
                            ?>
                            <span class="badge"><?php echo __('Total'); ?>: <?php echo count($videos); ?></span>
                        </div>    
                        <ul class="list-group">
                            <?php
                            foreach ($videos as $value) {
                                ?>
                                <a href="<?php echo Video::getLinkToVideo($value['id'], $value['clean_title']); ?>" 
                                   class="list-group-item list-group-item-action" target="_blank">
                                    [<?php echo $value['id'] ?>] <?php echo $value['title'] ?>
                                </a>
                                <?php
                            }
                            ?>
                        </ul>
                        <?php
                    }
                    ?>
                </div>
                <div class="panel-footer">
                    <?php
                    $uid = uniqid();
                    $captcha = User::getCaptchaForm($uid, true);
                    ?>
                    <div class="form-group" id="captchaDeleteUser">
                        <?php echo $captcha['content']; ?>
                    </div>
                </div>
                <div class="panel-footer">
                    <button class="btn btn-danger btn-block" onclick="deleteUser();">
                        <i class="fas fa-trash"></i>
                        <?php echo __('Delete'); ?>
                    </button>
                </div>
            </div>
        </div>
        <?php
        include $global['systemRootPath'] . 'view/include/footer.php';
        ?>
        <script type="text/javascript">
            
            function deleteUser(){
                var url = webSiteRootURL + 'plugin/CustomizeUser/confirmDeleteUser.json.php';
                var data = {captcha: $('#captchaDeleteUser input').val(), users_id: <?php echo $users_id; ?>};
                avideoAjax(url, data);
            }
            
        </script>
    </body>
</html>
