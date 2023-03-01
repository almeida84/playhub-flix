<?php
/*
error_log("avideoencoder REQUEST 1: " . json_encode($_REQUEST));
error_log("avideoencoder POST 1: " . json_encode($_POST));
error_log("avideoencoder GET 1: " . json_encode($_GET));
*/
$obj = new stdClass();
$obj->error = true;

global $global, $config;
if (!isset($global['systemRootPath'])) {
    require_once '../videos/configuration.php';
}
/*
_error_log("REQUEST: " . json_encode($_REQUEST));
_error_log("POST: " . json_encode($_POST));
_error_log("GET: " . json_encode($_GET));
*/
header('Content-Type: application/json');
allowOrigin();

$global['bypassSameDomainCheck'] = 1;
if (empty($_POST)) {
    $obj->msg = __("Your POST data is empty, maybe your video file is too big for the host");
    _error_log($obj->msg);
    die(json_encode($obj));
}
//_error_log("aVideoEncoder.json: start");
_error_log("aVideoEncoder.json: start");
if (empty($_REQUEST['format']) || !in_array($_REQUEST['format'], $global['allowedExtension'])) {
    $obj->msg = "aVideoEncoder.json: ERROR Extension not allowed File {$_REQUEST['format']}";
    _error_log($obj->msg. ": " . json_encode($_REQUEST));
    die(json_encode($obj));
}

if (!isset($_REQUEST['encodedPass'])) {
    $_REQUEST['encodedPass'] = 1;
}
useVideoHashOrLogin();
if (!User::canUpload()) {
    _error_log("aVideoEncoder.json: Permission denied to receive a file: " . json_encode($_REQUEST));
    $obj->msg = __("Permission denied to receive a file: ") . json_encode($_POST);
    _error_log($obj->msg);
    die(json_encode($obj));
}

if (!empty($_POST['videos_id']) && !Video::canEdit($_POST['videos_id'])) {
    _error_log("aVideoEncoder.json: Permission denied to edit a video: " . json_encode($_POST));
    $obj->msg = __("Permission denied to edit a video: ") . json_encode($_POST);
    _error_log($obj->msg);
    die(json_encode($obj));
}

_error_log("aVideoEncoder.json: start to receive: " . json_encode($_POST));

// check if there is en video id if yes update if is not create a new one
$video = new Video("", "", @$_POST['videos_id']);

if (!empty($video->getId()) && !empty($_REQUEST['first_request'])) {
    _error_log("aVideoEncoder.json: There is a new video to replace the existing one, we will delete the current files videos_id = ".$video->getId());
    $video->removeVideoFiles();
}

$obj->video_id = @$_POST['videos_id'];
$title = $video->getTitle();
$description = $video->getDescription();
if (empty($title) && !empty($_POST['title'])) {
    $title = $video->setTitle($_POST['title']);
} elseif (empty($title)) {
    $video->setTitle("Automatic Title");
}

if (empty($description)) {
    $video->setDescription($_POST['description']);
}


if (!empty($_REQUEST['duration'])) {
    $duration = $video->getDuration();
    if (empty($duration) || $duration === 'EE:EE:EE') {
        $video->setDuration($_REQUEST['duration']);
    }
}

$status = $video->setAutoStatus();

$video->setVideoDownloadedLink($_POST['videoDownloadedLink']);
_error_log("aVideoEncoder.json: Encoder receiving post " . json_encode($_POST));
//_error_log(print_r($_POST, true));
if (preg_match("/(mp3|wav|ogg)$/i", $_POST['format'])) {
    $type = 'audio';
    $video->setType($type);
} elseif (preg_match("/(mp4|webm|zip)$/i", $_POST['format'])) {
    $type = 'video';
    $video->setType($type);
}

$videoFileName = $video->getFilename();
if (empty($videoFileName)) {
    $paths = Video::getNewVideoFilename();
    $filename = $paths['filename'];
    $videoFileName = $video->setFilename($videoFileName);
}

$paths = Video::getPaths($videoFileName, true);
$destination_local = "{$paths['path']}{$videoFileName}";

if (!empty($_FILES)) {
    _error_log("aVideoEncoder.json: Files " . json_encode($_FILES));
} else {
    _error_log("aVideoEncoder.json: Files EMPTY");
    if (!empty($_REQUEST['downloadURL'])) {
        $_FILES['video']['tmp_name'] = downloadVideoFromDownloadURL($_REQUEST['downloadURL']);
        if (empty($_FILES['video']['tmp_name'])) {
            _error_log("aVideoEncoder.json: ********  Download ERROR " . $_REQUEST['downloadURL']);
        }
    }
}

if (!empty($_FILES['video']['error'])) {
    $phpFileUploadErrors = [
        0 => 'There is no error, the file uploaded with success',
        1 => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
        2 => 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form',
        3 => 'The uploaded file was only partially uploaded',
        4 => 'No file was uploaded',
        6 => 'Missing a temporary folder',
        7 => 'Failed to write file to disk.',
        8 => 'A PHP extension stopped the file upload.',
    ];
    _error_log("aVideoEncoder.json: ********  Files ERROR " . $phpFileUploadErrors[$_FILES['video']['error']]);
    if (!empty($_POST['downloadURL'])) {
        $_FILES['video']['tmp_name'] = downloadVideoFromDownloadURL($_POST['downloadURL']);
    }
}

if (empty($_FILES['video']['tmp_name']) && isValidURLOrPath($_POST['chunkFile'])) {
    $_FILES['video']['tmp_name'] = $_POST['chunkFile'];
}

// get video file from encoder
if (!empty($_FILES['video']['tmp_name'])) {
    $resolution = '';
    if (!empty($_POST['resolution'])) {
        $resolution = "_{$_POST['resolution']}";
    }
    $filename = "{$videoFileName}{$resolution}.{$_POST['format']}";

    $fsize = filesize($_FILES['video']['tmp_name']);

    _error_log("aVideoEncoder.json: receiving video upload to {$filename} filesize=" . ($fsize) . " (" . humanFileSize($fsize) . ")" . json_encode($_FILES));
    $destinationFile = decideMoveUploadedToVideos($_FILES['video']['tmp_name'], $filename);
} else {
    // set encoding
    $video->setStatus(Video::$statusEncoding);
}
if (!empty($_FILES['image']['tmp_name']) && !file_exists("{$destination_local}.jpg")) {
    if (!move_uploaded_file($_FILES['image']['tmp_name'], "{$destination_local}.jpg")) {
        $obj->msg = print_r(sprintf(__("Could not move image file [%s.jpg]"), $destination_local), true);
        _error_log("aVideoEncoder.json: " . $obj->msg);
        die(json_encode($obj));
    }
}
if (!empty($_FILES['gifimage']['tmp_name']) && !file_exists("{$destination_local}.gif")) {
    if (!move_uploaded_file($_FILES['gifimage']['tmp_name'], "{$destination_local}.gif")) {
        $obj->msg = print_r(sprintf(__("Could not move gif image file [%s.gif]"), $destination_local), true);
        _error_log("aVideoEncoder.json: " . $obj->msg);
        die(json_encode($obj));
    }
}

if (!empty($_POST['encoderURL'])) {
    $video->setEncoderURL($_POST['encoderURL']);
}

if (!empty($_POST['categories_id'])) {
    $video->setCategories_id($_POST['categories_id']);
}

$video_id = $video->save();
$video->updateDurationIfNeed();
$video->updateHLSDurationIfNeed();

if (!empty($_POST['usergroups_id'])) {
    if (!is_array($_POST['usergroups_id'])) {
        $_POST['usergroups_id'] = [$_POST['usergroups_id']];
    }
    UserGroups::updateVideoGroups($video_id, $_POST['usergroups_id']);
}

$obj->error = false;
$obj->video_id = $video_id;

$v = new Video('', '', $video_id);
$obj->video_id_hash = $v->getVideoIdHash();

_error_log("aVideoEncoder.json: Files Received for video {$video_id}: " . $video->getTitle());
if (!empty($destinationFile)) {
    if (file_exists($destinationFile)) {
        _error_log("aVideoEncoder.json: Success $destinationFile ");
    } else {
        _error_log("aVideoEncoder.json: ERROR $destinationFile ");
    }
}
die(json_encode($obj));

/*
  _error_log(print_r($_POST, true));
  _error_log(print_r($_FILES, true));
  var_dump($_POST, $_FILES);
 */

function downloadVideoFromDownloadURL($downloadURL){
    global $global;
    $downloadURL = trim($downloadURL);
    _error_log("aVideoEncoder.json: Try to download " . $downloadURL);
    $file = url_get_contents($downloadURL);
    $strlen = strlen($file);
    if ($strlen<20000) {
        //it is not a video
        return false;
    }
    _error_log("aVideoEncoder.json: Got the download " . $downloadURL . ' '. humanFileSize($strlen));
    if ($file) {
        $_FILES['video']['name'] = basename($downloadURL);

        $temp = Video::getStoragePath()."cache/tmpFile/" . $_FILES['video']['name'];
        make_path($temp);
        $bytesSaved = file_put_contents($temp, $file);
        if ($bytesSaved) {
            _error_log("aVideoEncoder.json: saved " . $temp  . ' '. humanFileSize($bytesSaved));
            return $temp;
        } else {
            _error_log("aVideoEncoder.json: ERROR on save file " . $temp);
        }
    }
    return false;
}
