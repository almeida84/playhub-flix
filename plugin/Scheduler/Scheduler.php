<?php

global $global;
require_once $global['systemRootPath'] . 'objects/ICS.php';
require_once $global['systemRootPath'] . 'plugin/Plugin.abstract.php';

require_once $global['systemRootPath'] . 'plugin/Scheduler/Objects/Scheduler_commands.php';

class Scheduler extends PluginAbstract {

    public function getDescription() {
        global $global;
        $desc = "Scheduler Plugin";
        if (!_isSchedulerPresentOnCrontab()) {
            $desc = "<strong onclick='tooglePluginDescription(this);'>";
            $desc .= "To use the Scheduler Plugin, you MUST add it on your crontab";
            $desc .= "</strong>";
            $desc .= "<br>Open a terminal and type <code>crontab -e</code> than add a crontab for every 1 minute<br><code>* * * * * php {$global['systemRootPath']}plugin/Scheduler/run.php</code>";
        }
        //$desc .= $this->isReadyLabel(array('YPTWallet'));
        return $desc;
    }

    public function getName() {
        return "Scheduler";
    }

    public function getUUID() {
        return "Scheduler-5ee8405eaaa16";
    }

    public function getPluginVersion() {
        return "4.0";
    }

    public function updateScript() {
        global $global;
        if (AVideoPlugin::compareVersion($this->getName(), "2.0") < 0) {
            $sqls = file_get_contents($global['systemRootPath'] . 'plugin/Scheduler/install/updateV2.0.sql');
            $sqlParts = explode(";", $sqls);
            foreach ($sqlParts as $value) {
                sqlDal::writeSql(trim($value));
            }
        }
        if (AVideoPlugin::compareVersion($this->getName(), "3.0") < 0) {
            $sqls = file_get_contents($global['systemRootPath'] . 'plugin/Scheduler/install/updateV3.0.sql');
            $sqlParts = explode(";", $sqls);
            foreach ($sqlParts as $value) {
                sqlDal::writeSql(trim($value));
            }
        }
        return true;
    }

    public function getEmptyDataObject() {
        $obj = new stdClass();
        /*
          $obj->textSample = "text";
          $obj->checkboxSample = true;
          $obj->numberSample = 5;

          $o = new stdClass();
          $o->type = array(0=>__("Default"))+array(1,2,3);
          $o->value = 0;
          $obj->selectBoxSample = $o;

          $o = new stdClass();
          $o->type = "textarea";
          $o->value = "";
          $obj->textareaSample = $o;
         */
        return $obj;
    }

    public function getPluginMenu() {
        global $global;
        $btn = '<button onclick="avideoModalIframeLarge(webSiteRootURL+\'plugin/Scheduler/View/editor.php\')" class="btn btn-primary btn-sm btn-xs btn-block"><i class="fa fa-edit"></i> Edit</button>';
        $btn .= '<button onclick="avideoModalIframeLarge(webSiteRootURL+\'plugin/Scheduler/run.php\')" class="btn btn-primary btn-sm btn-xs btn-block"><i class="fas fa-terminal"></i> Run now</button>';
        return $btn;
    }

    static public function run($scheduler_commands_id) {
        global $_executeSchelude, $global;
        if (!isset($_executeSchelude)) {
            $_executeSchelude = array();
        }
        $e = new Scheduler_commands($scheduler_commands_id);        
        
        $videos_id = $e->getCallbackURL();
        if(!empty($videos_id)){ // make it active
            $video = new Video('', '', $videos_id);
            $status = $video->setStatus(Video::$statusActive);
            AVideoPlugin::onNewVideo($videos_id);
            return $e->setExecuted($videos_id);
        }
        
        $callBackURL = $e->getCallbackURL();
        $callBackURL = str_replace('{webSiteRootURL}', $global['webSiteRootURL'], $callBackURL);
        if (!isValidURL($callBackURL)) {
            return false;
        }
        if (empty($_executeSchelude[$callBackURL])) {
            $callBackURL = addQueryStringParameter($callBackURL, 'token', getToken(60));
            $callBackURL = addQueryStringParameter($callBackURL, 'scheduler_commands_id', $scheduler_commands_id);
            _error_log("Scheduler::run getting callback URL {$callBackURL}");
            $_executeSchelude[$callBackURL] = url_get_contents($callBackURL, '', 30);
            _error_log("Scheduler::run got callback " . json_encode($_executeSchelude[$callBackURL]));
            $json = _json_decode($_executeSchelude[$callBackURL]);
            if (is_object($json) && !empty($json->error)) {
                _error_log("Scheduler::run callback ERROR " . json_encode($json));
                return false;
            }
        }
        if (!empty($_executeSchelude[$callBackURL])) {
            return $e->setExecuted($_executeSchelude[$callBackURL]);
        }
        return false;
    }

    static function isActiveFromVideosId($videos_id){
        return Scheduler_commands::isActiveFromVideosId($videos_id);;
    }
    
    static public function addVideoToRelease($date_to_execute, $videos_id) {
        _error_log("Scheduler::addVideoToRelease [$date_to_execute] [$videos_id]");
        if (empty($date_to_execute)) {
            _error_log("Scheduler::addVideoToRelease ERROR date_to_execute is empty");
            return false;
        }

        $date_to_execute_time = _strtotime($date_to_execute);

        if ($date_to_execute_time <= time()) {
            _error_log("Scheduler::addVideoToRelease ERROR date_to_execute must be greater than now [{$date_to_execute}] " . date('Y/m/d H:i:s', $date_to_execute_time) . ' ' . date('Y/m/d H:i:s'));
            return false;
        }

        if (empty($videos_id)) {
            _error_log("Scheduler::addVideoToRelease ERROR videos_id is empty");
            return false;
        }
        
        $id = 0;
        $row = Scheduler_commands::getFromVideosId($videos_id);
        if(!empty($row)){
            $id = $row['id'];
        }
        
        $e = new Scheduler_commands($id);
        $e->setDate_to_execute($date_to_execute);
        $e->setVideos_id($videos_id);
        
        return $e->save();
    }
    
    static public function add($date_to_execute, $callbackURL, $parameters = '', $type = '') {
        _error_log("Scheduler::add [$date_to_execute] [$callbackURL]");
        if (empty($date_to_execute)) {
            _error_log("Scheduler::add ERROR date_to_execute is empty");
            return false;
        }

        $date_to_execute_time = _strtotime($date_to_execute);

        if ($date_to_execute_time <= time()) {
            _error_log("Scheduler::add ERROR date_to_execute must be greater than now [{$date_to_execute}] " . date('Y/m/d H:i:s', $date_to_execute_time) . ' ' . date('Y/m/d H:i:s'));
            return false;
        }

        if (empty($callbackURL)) {
            _error_log("Scheduler::add ERROR callbackURL is empty");
            return false;
        }
        $e = new Scheduler_commands(0);
        $e->setDate_to_execute($date_to_execute);
        $e->setCallbackURL($callbackURL);
        if (!empty($parameters)) {
            $e->setParameters($parameters);
        }
        if (!empty($type)) {
            $e->setType($type);
        }
        return $e->save();
    }

    static public function addSendEmail($date_to_execute, $emailTo, $emailSubject, $emailEmailBody, $emailFrom = '', $emailFromName = '', $type = '') {
        global $global;
        $parameters = array(
            'emailSubject' => $emailSubject,
            'emailEmailBody' => $emailEmailBody,
            'emailTo' => $emailTo,
            'emailFrom' => $emailFrom,
            'emailFromName' => $emailFromName,
        );
        //var_dump($parameters);exit;
        $url = "{webSiteRootURL}plugin/Scheduler/sendEmail.json.php";

        if (empty($type)) {
            $type = 'SendEmail';
        }

        $scheduler_commands_id = Scheduler::add($date_to_execute, $url, $parameters, $type);
        return $scheduler_commands_id;
    }

    static public function getReminderOptions($destinationURL, $title, $date_start, $selectedEarlierOptions = array(), $date_end = '', $joinURL='', $description='',$earlierOptions = array(
                '10 minutes earlier' => 10,
                '30 minutes earlier' => 30,
                '1 hour earlier' => 60,
                '2 hours earlier' => 120,
                '1 day earlier' => 1440,
                '2 days earlier' => 2880,
                '1 week earlier' => 10080
            )
    ) {
        global $global;
        $varsArray = array(
            'destinationURL' => $destinationURL, 
            'title' => $title, 
            'date_start' => $date_start, 
            'selectedEarlierOptions' => $selectedEarlierOptions, 
            'date_end' => $date_end, 
            'joinURL' => $joinURL, 
            'description' => $description,
            'earlierOptions' => $earlierOptions);
        $filePath = "{$global['systemRootPath']}plugin/Scheduler/reminderOptions.php";
        return getIncludeFileContent($filePath, $varsArray);
    }

    /**
     * 
     * @global array $global
     * @param string $title
     * @param string $description
     * @param string $date_start
     * @param string $date_end
     * 
     *  description - string description of the event.
        dtend - date/time stamp designating the end of the event. You can use either a DateTime object or a PHP datetime format string (e.g. "now + 1 hour").
        dtstart - date/time stamp designating the start of the event. You can use either a DateTime object or a PHP datetime format string (e.g. "now + 1 hour").
        location - string address or description of the location of the event.
        summary - string short summary of the event - usually used as the title.
        url - string url to attach to the the event. Make sure to add the protocol (http:// or https://).
     */
    static public function downloadICS($title, $date_start, $date_end = '', $reminderInMinutes='', $joinURL='', $description='') {
        global $global,$config;        
        //var_dump(date_default_timezone_get());exit;
        header('Content-Type: text/calendar; charset=utf-8');
        if(empty($_REQUEST['open'])){
            $ContentDisposition = 'attachment';
        }else{
            $ContentDisposition = 'inline';
        }
        
        $filename = cleanURLName("{$title}-{$date_start}");
        
        header("Content-Disposition: {$ContentDisposition}; filename={$filename}.ics");
        $location = $config->getWebSiteTitle();
        if(!isValidURL($joinURL)){
            $joinURL = $global['webSiteRootURL'];
        }
        
        if(empty($description)){
            $description = $location;
        }
        
        $date_start = _strtotime($date_start);
        $date_end = _strtotime($date_end);
        
        if(empty($date_end) || $date_end <= $date_start){
            $date_end = strtotime(date('Y/m/d H:i:s', $date_start).' + 1 hour');
        }
        $dtstart = date('Y/m/d H:i:s', $date_start);
        $dtend = date('Y/m/d H:i:s', $date_end);
        $reminderInMinutes = intval($reminderInMinutes);
        if(!empty($reminderInMinutes)){
            $VALARM = "-P{$reminderInMinutes}M";
        }else{
            $VALARM = '';
        }
        
        $props = array(
            'location' => $location,
            'description' => $description,
            'dtstart' => $dtstart,
            'dtend' => $dtend,
            'summary' => $title,
            'url' => $joinURL,
            'valarm' => $VALARM,
            //'X-WR-TIMEZONE' => date_default_timezone_get()
        );
        $ics = new ICS($props);
        //var_dump($props);
        $icsString = $ics->to_string();
        
        header('content-length: '. strlen($icsString));
        echo $icsString;
    }

}
