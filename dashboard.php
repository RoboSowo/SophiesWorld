<?php
/**
 * ETWorker A class to handle API calls for various tasks
 */
class ETWorker
{
    /**
     * Static constant SUCCESS
     * Describes the response from API call as 'OK'.
     */
    const SUCCESS = 'OK';
    /**
     * Static constant ERROR
     * Describes the response from API call as 'Error'
     */
    const ERROR = 'Error';
    /**
     * Static constant FILTER_TIME_CURRENT
     * The closed time string from NOW to fetch at least one record from DE
     * which adds a record every 15 mins.
     */
    const FILTER_TIME_CURRENT = '-60 minutes';
    /**
     * Statci constant FILTER_TIME_WEEK_AGO
     * The closes time string to a week from NOW
     */
    const FILTER_TIME_WEEK_AGO = '-1 week';
    /**
     * ExactTargetSoapClient object
     *
     * @var ExactTargetSoapClient
     */
    protected $client;
    /**
     * Constructor
     *
     * @param array $config an array of configuration settings for SOAP Api
     */
    public function __construct($config)
    {
        $settings = $config['api_settings'];
        $client = new ExactTargetSoapClient($settings['wsdl'], array('trace'=>1) );
        $client->username = $settings['username'];
        $client->password = $settings['password'];
        $this->client = $client;
    }
    /**
     * Get total email permissions from a data extension
     *
     * @return string The number of email permissions
     */
    public function getEmailPermissions($time = null)
    {
        $properties = array(
            'email_permissions',
            'created'
        );
        $response = $this->getDataExtension('dashboard_email_permissions', $properties, 'created', $time);
        return $this->getSinglePropertyResponse($response);
    }
    /**
     * Get customers with email permisions
     *
     * @param  string $time Time string for any altered time than current
     * @return string       The number of customers as string
     */
    public function getCustomerPermissions($time = null)
    {
        $properties = array(
            'customer_permission',
            'created'
        );
        $response = $this->getDataExtension('dashboard_customer_permission', $properties, 'created', $time);
        return $this->getSinglePropertyResponse($response);
    }
    /**
     * Get Push optins from data extension
     *
     * @param  string $time Time stirng for any altered time than current
     * @return string       The number of push optins as string
     */
    public function getPushOptIns($time = null)
    {
        $properties = array(
            'push_permission',
            'created'
        );
        $response =  $this->getDataExtension('dashboard_push_permission', $properties, 'created', $time);
        return $this->getSinglePropertyResponse($response);
    }
    /**
     * Get Mobile optins from data extension
     *
     * @param  string $time Time stirng for any altered time than current
     * @return string       The number of mobile optins as string
     */
    public function getMobileOptIns($time= null)
    {
        $properties = array(
            'sms_permission',
            'created'
        );
        $response = $this->getDataExtension('dashboard_sms_permission', $properties, 'created', $time);
        return $this->getSinglePropertyResponse($response);
    }
    /**
     * Get Last five sends from data extension
     *
     * @param  string $time Time stirng for any altered time than current
     * @return array An array of last five sends
     *
     * e.g.
     *
     * [
     *   [
     *      'email_name' => 'nordic-fashion_w_B-high_2017-05-30_w22_p',
     *      'subject_line' => 'Fantastic offers on FWSS, Whyred, Marimekko, Dagmar, Ganni and Vagabond',
     *      'job_id' => 27407,
     *      'sends' => 454561,
     *      'send_time' => '5/30/2017 1:20:00 AM',
     *      'clicks' => 2102,
     *      'bounces' => 273,
     *      'click_rate' => 0.46
     *      'delivery_rate' => 99.94
     *   ],
     *   [
     *      'email_name' => 'nordic-fashion_w_B-high_2017-05-30_w22_p',
     *      'subject_line' => 'Fantastic offers on FWSS, Whyred, Marimekko, Dagmar, Ganni and Vagabond',
     *      'job_id' => 27406,
     *      'sends' => 391998,
     *      'send_time' => '5/30/2017 1:18:00 AM',
     *      'clicks' => 5352,
     *      'bounces' => 179,
     *      'click_rate' => 1.37
     *      'delivery_rate' => 99.95
     *   ],
     *   ... 3 more like this
     *
     * ]
     */
    public function getLastFiveSends($time=null)
    {
        $properties = array(
            'Job_ID',
            'Email_Name',
            'Subject_Line',
            'Sends',
            'Send_Time',
            'Clicks',
            'Bounces'
        );
        $filterField = null;
        $response = $this->getDataExtension('dashboard_last_5_sends', $properties, $filterField, $time);
        $result = $this->getArrayResponse($response);
        $timezone = new DateTimeZone('Europe/Copenhagen');
        $return = array();
        foreach($result as $send) {
            $item['email_name'] = $send['Email_Name'];
            $item['subject_line'] = $send['Subject_Line'];
            $item['job_id'] = $send['Job_ID'];
            $item['sends'] = $send['Sends'];
            $date = new DateTime($send['Send_Time']);
            $date->setTimezone($timezone);
            $item['send_time'] = $date->format('m/d/Y h:i:s A');
            $item['clicks'] = $send['Clicks'];
            $item['bounces'] = $send['Bounces'];
            if($send['Sends']) {
                $item['click_rate'] = $this->formatPercentage(100 * ($send['Clicks'] / $send['Sends']));
                $item['delivery_rate'] = $this->formatPercentage(100 * (($send['Sends'] - $send['Bounces']) / $send['Sends']));
            } else {
                $item['click_rate'] = INF;
                $item['delivery_rate'] = INF;
            }
            array_push($return, $item);
        }
        return $return;
    }
    /**
     * Get promotional emails sent today from data extension
     *
     * @param  string $time Time stirng for any altered time than current
     * @return array An array of promotional emails sent today properties
     *
     * e.g.
     * [
     *  'today' => 8635458,
     *  'today_delivery_rate' => 99.95,
     *  'week_ago' => 8632982,
     *  'week_ago_delivery_rate' => 99.94
     * ]
     */
    public function getPromotionalEmailsSentToday($time=null)
    {
        $properties = array(
            'Prom_email_sent_today',
            'bounces',
            'Date'
        );
        $response = $this->getDataExtension('dashboard_prom_email_sent', $properties, 'Date', $time);
        return $this->getArrayElementResponse($response);
    }
    /**
     * Get journey builder emails sent today from data extension
     *
     * @param  string $time Time stirng for any altered time than current
     * @return array An array of journey builder emails sent today properties
     *
     * e.g.
     * [
     *  'today' => 8635458,
     *  'today_delivery_rate' => 99.95,
     *  'week_ago' => 8632982,
     *  'week_ago_delivery_rate' => 99.94
     * ]
     */
    public function getJbEmailsSentToday($time=null)
    {
        $properties = array(
            'JB_email_sent_today',
            'bounces',
            'Date'
        );
        $response = $this->getDataExtension('dashboard_JB_email_sent', $properties, 'Date', $time);
        return $this->getArrayElementResponse($response);
    }
    /**
     * Get promotional emails total from data extension
     *
     * @param  string $time Time stirng for any altered time than current
     * @return array An array of promotional emails total properties
     *
     * e.g.
     * [
     *  'sent' => 8635458,
     *  'delivery_rate' => 99.95,
     *  'open_rate' => 17.78,
     *  'click_rate' => 1.91
     * ]
     */
    public function getPromotionalEmailsTotal($time=null)
    {
        $properties = array(
            'Prom_email_send',
            'Prom_email_bounces',
            'Prom_email_open',
            'Prom_email_click',
            'date'
        );
        $response = $this->getDataExtension('dashboard_prom_email_last_7d', $properties, 'Date', $time);
        return $this->getArrayElementResponse($response);
    }
    /**
     * Get abandoned cart emails total from data extension
     *
     * @param  string $time Time stirng for any altered time than current
     * @return array An array of abandoned cart emails total properties
     *
     * e.g.
     * [
     *  'sent' => 8635458,
     *  'delivery_rate' => 99.95,
     *  'open_rate' => 17.78,
     *  'click_rate' => 1.91
     * ]
     */
    public function getACEmailsTotal($time=null)
    {
        $properties = array(
            'AC_email_send',
            'AC_email_bounces',
            'AC_email_open',
            'AC_email_click',
            'date'
        );
        $response = $this->getDataExtension('dashboard_abandoned_email_last_7d', $properties, 'Date', $time);
        return $this->getArrayElementResponse($response);
    }
    /**
     * Get Single Send push today current value or at an earlier time
     * @param  string $time Time string for an altered time than curl_share_init
     * @return string       The number of single send push today as string
     */
    public function getSingleSendPushToday($time = null)
    {
        $properties = array(
            'single_push_today',
            'date'
        );
        $response = $this->getDataExtension('dashboard_single_push_today', $properties, 'date', $time);
        return $this->getSinglePropertyResponse($response);
    }
    /**
     * Get Journey Builder Send push today current value or at an earlier time
     * @param  string $time Time string for an altered time than curl_share_init
     * @return string       The number of single send push today as string
     */
    public function getJbSendPushToday($time = null)
    {
        $properties = array(
            'journey_push_today',
            'date'
        );
        $response = $this->getDataExtension('dashboard_journey_push_today', $properties, 'date', $time);
        return $this->getSinglePropertyResponse($response);
    }
    /**
     * Get Single SMS Sent today current value or at an earlier time
     * @param  string $time Time string for an altered time than curl_share_init
     * @return string       The number of single send push today as string
     */
    public function getSingleSmsSentToday($time = null)
    {
        $properties = array(
            'single_sms_today',
            'date'
        );
        $response = $this->getDataExtension('dashboard_single_sms_today', $properties, 'date', $time);
        return $this->getSinglePropertyResponse($response);
    }
    /**
     * Get Journey Builder SMS Sent today current value or at an earlier time
     * @param  string $time Time string for an altered time than curl_share_init
     * @return string       The number of single send push today as string
     */
    public function getJbSmsSentToday($time = null)
    {
        $properties = array(
            'journey_sms_today',
            'date'
        );
        $response = $this->getDataExtension('dashboard_journey_sms_today', $properties, 'date', $time);
        return $this->getSinglePropertyResponse($response);
    }
    /**
     * Get App Push Promotions Total current value or at an earlier time
     * @param  string $time Time string for an altered time than curl_share_init
     * @return string       The number of single send push today as string
     */
    public function getAppPushPromotionsTotal($time = null)
    {
        $properties = array(
            'Prom_push_7d',
            'reactions',
            'date'
        );
        $response = $this->getDataExtension('dashboard_prom_push_last_7d', $properties, 'date', $time);
        return $this->getArrayElementResponse($response);
    }
    /**
     * Get SMS Promotions Total current value or at an earlier time
     * @param  string $time Time string for an altered time than curl_share_init
     * @return string       The number of single send push today as string
     */
    public function getSmsPromotionsTotal($time = null)
    {
        $properties = array(
            'prom_sms_7d',
            'date'
        );
        $response = $this->getDataExtension('dashboard_prom_sms_last_7d', $properties, 'date', $time);
        return $this->getSinglePropertyResponse($response);
    }
    /**
     * Get App Push Journeyes Total current value or at an earlier time
     * @param  string $time Time string for an altered time than curl_share_init
     * @return string       The number of single send push today as string
     */
    public function getAppPushJourneysTotal($time = null)
    {
        $properties = array(
            'journey_push_7d',
            'reactions',
            'date'
        );
        $response = $this->getDataExtension('dashboard_journey_push_last_7d', $properties, 'date', $time);
        return $this->getArrayElementResponse($response);
    }
    /**
     * Get SMS Journeyes Total current value or at an earlier time
     * @param  string $time Time string for an altered time than curl_share_init
     * @return string       The number of single send push today as string
     */
    public function getSmsJourneysTotal($time = null)
    {
        $properties = array(
            'journey_sms_7d',
            'date'
        );
        $response = $this->getDataExtension('dashboard_journey_sms_last_7d', $properties, 'date', $time);
        return $this->getSinglePropertyResponse($response);
    }
    public function getAutomations()
    {
        /* Create the Retrieve request */
        $request = new ExactTarget_RetrieveRequest();
        $request->ObjectType = 'Automation';
        $filterPart = new ExactTarget_SimpleFilterPart();
        $filterPart->Property = 'Status';
        $filterPart->SimpleOperator = ExactTarget_SimpleOperators::equals;
        $filterPart->Value = -1;
        $filterPart = new SoapVar($filterPart, SOAP_ENC_OBJECT,'SimpleFilterPart', "http://exacttarget.com/wsdl/partnerAPI");
        $request->Filter = $filterPart;
        $properties = array(
            'ProgramID',
            'Name',
            'Description',
            'RecurrenceID',
            'CustomerKey',
            'IsActive',
            'CreatedDate',
            'ModifiedDate',
            'Status'
        );
        $request->Properties = $properties;
        // retrieve the data
        $requestMsg = new ExactTarget_RetrieveRequestMsg();
        $requestMsg->RetrieveRequest = $request;
        $response = $this->client->Retrieve($requestMsg);
        $automations = array();
        if(!is_array($response->Results)) {
            return array();
        }
        foreach($response->Results as $item) {
            $automation = array(
                'partner_key' => $item->PartnerKey,
                'created' => date_format(date_create($item->CreatedDate), 'Y-m-d H:i:s'),
                'object_id' => $item->ObjectID,
                'name' => $item->Name,
                'description' => $item->Description,
                'customer_key' => $item->CustomerKey,
                'status' => $item->Status,
                'recurrence_id' => $item->RecurrenceID
            );
            array_push($automations, $automation);
        }
        return $automations;
    }
    /**
     * Get the data extension rows (one or more) using SOAP API
     *
     * @param  string $dataExtension The data extension Name
     * @param  array $properties    An array of properties to fetch
     * @param  string $filterField   The name of the field to filter on
     * @param  string $filterTime    The string for filter time e.g. ' -1 days'
     * @return muliple|array    The response from the the SOAP call
     */
    protected function getDataExtension($dataExtension, $properties, $filterField, $filterTime)
    {
        /* Create the Retrieve request */
        $request = new ExactTarget_RetrieveRequest();
        $request->ObjectType = 'DataExtensionObject['. $dataExtension . ']';
        $request->Properties = $properties;
        if($filterField) {
            $filterPart = new ExactTarget_SimpleFilterPart();
            $filterPart->Property = $filterField;
            if($filterTime) {
                $filterPart->SimpleOperator = ExactTarget_SimpleOperators::between;
                $filterPart->Value = array($this->getDateFormat($filterTime, ' -1 hour'), $this->getDateFormat($filterTime));
            } else {
                $filterPart->SimpleOperator = ExactTarget_SimpleOperators::greaterThan;
                $filterPart->Value = array($this->getDateFormat(static::FILTER_TIME_CURRENT));
            }
            $filterPart = new SoapVar($filterPart, SOAP_ENC_OBJECT,'SimpleFilterPart', "http://exacttarget.com/wsdl/partnerAPI");
            $request->Filter = $filterPart;
        }
        // retrieve the data
        $requestMsg = new ExactTarget_RetrieveRequestMsg();
        $requestMsg->RetrieveRequest = $request;
        $response = $this->client->Retrieve($requestMsg);
        return $response;
    }
    /**
     * Get the value of the single most property that is desired from the data extension.
     *
     * e.g. When the property returned form DE is like below and we need to get the value of email_permissions
     * which is 1679221
     *  [Property] => Array
     *                  (
     *                       [0] => stdClass Object
     *                           (
     *                                [Name] => email_permissions
     *                                [Value] => 1679221
     *                           )
     *                       [1] => stdClass Object
     *                           (
     *                                [Name] => created
     *                                [Value] => 5/31/2017 4:25:15 AM
     *                           )
     *                  )
     *  The above array can be accessed using $result->Properties->Property
     *
     * @param  array|multiple $response The SOAP response object
     * @return string           The value of the single propery
     */
    protected function getSinglePropertyResponse($response)
    {
        if($response->OverallStatus !== static::SUCCESS) {
            throw new Exception($response->OverallStatus);
        }
        if(!isset($response->Results) ||  empty($response->Results)) {
            return null;
        }
        if(!is_object($result  = $response->Results)) {
            $result = end($result);
        }
        $result = $result->Properties->Property[0];
        return $result->Value;
    }
    /**
     * Get response in an array format from the SOAP call resposne
     *
     * e.g. When the results field from the SOAP response is as shown below
     *
     * [Results] => Array
     *    (
     *        [0] => stdClass Object
     *            (
     *                [PartnerKey] =>
     *                [ObjectID] =>
     *                [Type] => DataExtensionObject
     *                [Properties] => stdClass Object
     *                    (
     *                        [Property] => Array
     *                            (
     *                                [0] => stdClass Object
     *                                    (
     *                                        [Name] => Job_ID
     *                                        [Value] => 27443
     *                                    )
     *
     *                                [1] => stdClass Object
     *                                    (
     *                                        [Name] => Email_Name
     *                                        [Value] => last-chance_w_C-high_2017-05-31_w22_p
     *                                    )
     *                            )
     *                    )
     *            )
     *
     *        [0] => stdClass Object
     *            (
     *                [PartnerKey] =>
     *                [ObjectID] =>
     *                [Type] => DataExtensionObject
     *                [Properties] => stdClass Object
     *                    (
     *                        [Property] => Array
     *                            (
     *                                [0] => stdClass Object
     *                                    (
     *                                        [Name] => Job_ID
     *                                        [Value] => 27443
     *                                    )
     *
     *                                [1] => stdClass Object
     *                                    (
     *                                        [Name] => Email_Name
     *                                        [Value] => last-chance_w_C-high_2017-05-31_w22_p
     *                                    )
     *                            )
     *                    )
     *            )
     *      )
     * @param  array|multiple $response The SOAP response object
     * @return array           the formatted response in array formatted
     *
     * e.g.
     * [
     *      [
     *          'job_id' => '27443'
     *          'email_name' => 'last-chance_w_C-high_2017-05-31_w22_p'
     *      ],
     *      [
     *          'job_id' => 27443
     *          'email_name' => 'last-chance_w_C-high_2017-05-31_w22_p'
     *      ]
     * ]
     *
     */
    protected function getArrayResponse($response)
    {
        if($response->OverallStatus !== static::SUCCESS) {
            throw new Exception($response->OverallStatus);
        }
        if(!isset($response->Results) ||  empty($response->Results)) {
            return null;
        }
        $results = $response->Results;
        $return = array();
        foreach($results as $result) {
            $item = array();
            foreach($result->Properties->Property as $object) {
                $item[$object->Name] = $object->Value;
            }
            array_push($return, $item);
        }
        return $return;
    }
    protected function getArrayElementResponse($response)
    {
        if($response->OverallStatus !== static::SUCCESS) {
            throw new Exception($response->OverallStatus);
        }
        if(!isset($response->Results) ||  empty($response->Results)) {
            return null;
        }
        $results = $response->Results;
        if(is_array($results)) {
            $results = end($response->Results);
        }
        $item = array();
        foreach($results->Properties->Property as $object) {
            $item[$object->Name] = $object->Value;
        }
        return $item;
    }
    public function formatPercentage($number)
    {
        return floatval(number_format((float)$number, 2, '.', ''));
    }
    /**
     * getDateFormat
     * @param  string $diffString The string that is required to fetch the date e.g. ' -20 minutes'
     * @param  string $limit      The string that is needed if finding date in the period before/after
     * time of ($diffString) e.g. ' -1 hour'
     * @return Date              Date string in format as per the DE
     */
    protected function getDateFormat($diffString, $limit=null)
    {
        if($limit) {
            $date = date('m/d/Y h:i:s A', strtotime($diffString));
            return date('m/d/Y h:i:s A', strtotime($date . $limit ));
        } else {
            return date('m/d/Y h:i:s A', strtotime($diffString));
        }
    }
}
