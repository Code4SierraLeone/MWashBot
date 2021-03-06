<?php

define('BOT_TOKEN', ''); //!IMPORTANT add Bot Token Here
define('API_URL', 'https://api.telegram.org/bot'.BOT_TOKEN.'/');

function apiRequestWebhook($method, $parameters) {

    if (!is_string($method)) {
        error_log("Method name must be a string\n");
        return false;

    }

    if (!$parameters) {

        $parameters = array();
    } else if (!is_array($parameters)) {
        error_log("Parameters must be an array\n");
        return false;

    }

    $parameters["method"] = $method;

    header("Content-Type: application/json");
    echo json_encode($parameters);
    return true;

}

function exec_curl_request($handle) {
    $response = curl_exec($handle);

    if ($response === false) {
        $errno = curl_errno($handle);
        $error = curl_error($handle);
        error_log("Curl returned error $errno: $error\n");
        curl_close($handle);
        return false;

    }

    $http_code = intval(curl_getinfo($handle, CURLINFO_HTTP_CODE));
    curl_close($handle);

    if ($http_code >= 500) {
        // do not wat to DDOS server if something goes wrong
        sleep(10);
        return false;
    } else if ($http_code != 200) {
        $response = json_decode($response, true);
        error_log("Request has failed with error {$response['error_code']}: {$response['description']}\n");
        if ($http_code == 401) {
            throw new Exception('Invalid access token provided');
        }
        return false;
    } else {
        $response = json_decode($response, true);
        if (isset($response['description'])) {
            error_log("Request was successfull: {$response['description']}\n");
        }
        $response = $response['result'];
    }

    return $response;
}

function apiRequest($method, $parameters) {

    if (!is_string($method)) {
        error_log("Method name must be a string\n");
        return false;
    }

    if (!$parameters) {
        $parameters = array();
    } else if (!is_array($parameters)) {
        error_log("Parameters must be an array\n");
        return false;
    }

    foreach ($parameters as $key => &$val) {
        // encoding to JSON array parameters, for example reply_markup
        if (!is_numeric($val) && !is_string($val)) {
            $val = json_encode($val);
        }
    }
    $url = API_URL . $method . '?' . http_build_query($parameters);

    $handle = curl_init($url);
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($handle, CURLOPT_TIMEOUT, 60);

    return exec_curl_request($handle);

}

function apiRequestJson($method, $parameters) {

    if (!is_string($method)) {
        error_log("Method name must be a string\n");
        return false;
    }

    if (!$parameters) {
        $parameters = array();
    } else if (!is_array($parameters)) {
        error_log("Parameters must be an array\n");
        return false;

    }

    $parameters["method"] = $method;

    $handle = curl_init(API_URL);
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($handle, CURLOPT_TIMEOUT, 60);
    curl_setopt($handle, CURLOPT_POSTFIELDS, json_encode($parameters));
    curl_setopt($handle, CURLOPT_HTTPHEADER, array("Content-Type: application/json"));

    return exec_curl_request($handle);
}

function dbcon(){

    $hostname = ''; $dbname = ''; $username = ''; $password = ''; //!IMPORTANT: add your MySQL hostname, database, username and password

    $dbh = new PDO("mysql:host=$hostname;dbname=$dbname", $username, $password);

    $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    return $dbh;

}

function get_session_id($chat_id){

    $date = new DateTime(null, new DateTimeZone('Africa/Nairobi'));
    $datetime = $date->format('YmdHms');

    $sess = $chat_id.'_'.$datetime;

    return $sess;
}


function processMessage($message) {

    // process incoming message
    $message_id = $message['message_id'];
    $chat_id = $message['chat']['id'];
    $text = $message['text'];

    //show action (MwashBot is typing...)
    apiRequest("sendChatAction", array('chat_id' => $chat_id, "action" => 'typing'));

    if (isset($text)) {

        if ($text === "/start") {

            apiRequestJson("sendMessage", array('chat_id' => $chat_id, "text" => 'Hi, Welcome to MWash Bot. Please select what you would like to do by selecting one of the options below.', 'reply_markup' => array(
                'keyboard' => array(array('Search Water Point'),array('Update Water Point'),array('Subscribe')),
                'one_time_keyboard' => true,
                'resize_keyboard' => true)));

        } else if ($text === "Search Water Point") {

            apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => 'Please provide the Water Point ID by typing the word "WP" followed by the ID i.e WP50'));

        } else if ($text === "Update Water Point") {

            apiRequestJson("sendMessage", array('chat_id' => $chat_id, "text" => 'You will only be able to update only some few attributes. Select which attribute you want to update from the options below.', 'reply_markup' => array(
                'keyboard' => array(array('Water Source Mechanic'),array('Manager','Chlorinated Water'),array('Water Source Quality')),
                'one_time_keyboard' => true,
                'resize_keyboard' => true)));

        } else if ($text === "Water Source Mechanic" || $text === "Manager" || $text === "Chlorinated Water" || $text === "Water Source Quality") {

            apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => 'Please provide the Water Point ID by typing the word "UP" followed by the ID i.e UP50'));

        } else if (strpos($text, "UP") !== false || strpos($text, "up") !== false || strpos($text, "Up") !== false || strpos($text, "uP") !== false) {

            $text_lc = strtolower($text); //to lower case

            $wpid = ltrim($text_lc,'up');

            include 'fusion_client.php';

        } else if ($text === "Subscribe") {

            apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => 'MWash Bot will update you about the condition of the water points around your area.'));
            apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => 'Please provide the Water Point ID by typing the word "SP" followed by the ID i.e SP50'));

        } else if (strpos($text, "WP") !== false || strpos($text, "wp") !== false || strpos($text, "Wp") !== false || strpos($text, "wP") !== false) {

            $text_lc = strtolower($text); //to lower case

            $wpid = ltrim($text_lc,'wp');

            include 'fusion_client.php';

            $selectQuery = "SELECT district,province,mechanic,manager,chlorine,qual,lon,lat FROM 1aHLU3Qqsl9X_W_BEvZaPn_dkNV8UtXtJPnKedgKB where cartodb_id = ".$wpid;

            $result = $service->query->sql($selectQuery);

            $district = json_encode($result->getRows()[0][0]);
            $province = json_encode($result->getRows()[0][1]);
            $mechanic = json_encode($result->getRows()[0][2]);
            $manager = json_encode($result->getRows()[0][3]);
            $chlorine = json_encode($result->getRows()[0][4]);
            $qual = json_encode($result->getRows()[0][5]);
            $lon = json_encode($result->getRows()[0][6]);
            $lat = json_encode($result->getRows()[0][7]);

            if (trim($mechanic, '"') == 'Yes'){
                $mech_ans = 'The mechanic is available near the water point to attend to any issue';
            } else {
                $mech_ans = 'Unfortunately there is no mechanic to attend to any issue during failure';
            }

            if (trim($chlorine, '"') == 'Yes') {
                $chlo_ans = 'and the water is chlorinated.';
            } else if (trim($chlorine, '"') == 'No') {
                $chlo_ans = 'and the water is not chlorinated.';
            } else if (trim($chlorine, '"') == 'Unknown') {
                $chlo_ans = 'and no available information is provided about water chlorination.';
            } else {
                $chlo_ans = 'and no available information is provided about water chlorination.';
            }

            if (trim($qual, '"') == 'Clean (good smell- taste and color)') {
                $qual_ans = 'The water is clean by the standards of good smell, good taste and color.';
            } else if (trim($qual, '"') == 'Not clean') {
                $qual_ans = 'The water is not clean by any standards.';
            } else {
                $qual_ans = 'Apparently the information about the water quality is not available.';
            }

            apiRequest("sendLocation", array('chat_id' => $chat_id, "latitude" => trim($lat, '"'), "longitude" => trim($lon, '"')));
            apiRequest("sendMessage", array(
                'chat_id' => $chat_id,
                "text" => 'The water point is located in ' .trim($district, '"'). ' district in ' .trim($province, '"'). ' province and its managed by ' .trim($manager, '"'). '. ' .$mech_ans . ' ' . $chlo_ans. ' ' .$qual_ans. ''
            ));


        } else if (strpos($text, "SP") !== false || strpos($text, "sp") !== false) {

            $wpid = ltrim($text,'SP');

            $sess = get_session_id($chat_id);

            dbcon()->exec("INSERT INTO `subscribers` (`water_point_id`, `session_chat_id`, `telegram_chat_id`,`province`,`district`,`chiefdom`,`phone_number`,`created_at`,`updated_at`) VALUES ('$wpid','$sess','$chat_id','0','0','0','0',now(),now())");

            apiRequest("sendMessage", array('chat_id' => $chat_id, "text" => 'Awesome, we just require your phone number..'));

        } else if (is_numeric($text)){

            dbcon()->exec("UPDATE `subscribers` SET `phone_number` = '$text' WHERE `telegram_chat_id` = '$chat_id' AND `phone_number` = '0' ORDER BY `id` DESC LIMIT 1");

            apiRequestJson("sendMessage", array('chat_id' => $chat_id, "text" => 'Brilliant, you will now be receiving updates. Incase you want to engage me again just click start below..', 'reply_markup' => array('keyboard' => array(array('/start')),  'one_time_keyboard' => true, 'resize_keyboard' => true)));

        }

    } else {

        apiRequestJson("sendMessage", array('chat_id' => $chat_id, "text" => 'I understand only text messages', 'reply_markup' => array(
            'keyboard' => array(array('/start')),
            'one_time_keyboard' => true,
            'resize_keyboard' => true)));

    }


}

define('WEBHOOK_URL', ''); //!IMPORTANT add URL

if (php_sapi_name() == 'cli') {
    // if run from console, set or delete webhook
    apiRequest('setWebhook', array('url' => isset($argv[1]) && $argv[1] == 'delete' ? '' : WEBHOOK_URL));
    exit;
}


$content = file_get_contents("php://input");
$update = json_decode($content, true);

if (!$update) {
    // receive wrong update, must not happen
    exit;
}

if (isset($update["message"])) {
    processMessage($update["message"]);
}
