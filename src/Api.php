<?php
namespace snider\NotifyMyAndroid;

/**
 * PHP library for NotifyMyAndroid.com which does not require cURL.
 *
 * @author  Ken Pepple <ken.pepple@rabbityard.com>
 * @license http://www.gnu.org/licenses/gpl-3.0-standalone.html GPL-3.0
 * @link    https://github.com/snider/php-notifyMyAndroid
 */
class Api
{
    /**
     * Error type "exception".
     *
     * This will cause the library to throw Exceptions instead of PHP errors.
     */
    const ERROR_TYPE_EXCEPTION = 'exception';

    /**
     * Error type "error".
     *
     * This will cause the library to throw PHP errors instead of Exceptions.
     */
    const ERROR_TYPE_ERROR = 'error';

    /**
     * API key verify URL.
     */
    const API_URL_VERIFY = 'https://www.notifymyandroid.com/publicapi/verify';

    /**
     * API notify URL.
     */
    const API_URL_NOTIFY = 'https://www.notifymyandroid.com/publicapi/notify';

    /**
     * Error type to be used.
     *
     * Supported values are:
     * - self::LIB_ERROR_TYPE_ERROR
     * - self::LIB_ERROR_TYPE_EXCEPTION
     */
    private $errorType = self::ERROR_TYPE_ERROR;

    /**
     * Toggles debugging.
     *
     * @var bool
     */
    public $debug = false;

    /**
     * Number of remaining available API calls.
     *
     * This number is related to NMA's API request rate limitations.
     * The counter resets after the time specified in $apiLimitReset.
     *
     * @see NmaApi::$apiLimitReset
     *
     * @var int
     */
    public $apiCallsRemaining = 0;

    /**
     * Time (in minutes) until the API rate limit counter resets.
     *
     * This number is related to NMA's API request rate limitations.
     * The number of remaining calls until the timer resets is specified
     * in $apiCallsRemaining.
     *
     * @see NmaApi::$apiCallsRemaining
     *
     * @var int
     */
    public $apiLimitReset = 0;

    /**
     * Status code response of the last API request.
     *
     * @var int
     */
    public $lastStatus = 0;

    /**
     * API key to be used.
     *
     * @var string
     */
    protected $apiKey = '';

    /**
     * Developer key to be used.
     *
     * @var string
     */
    protected $devKey = '';

    /**
     * @var array
     */
    protected $errorCodes = array(
        200 => 'Notification submitted.',
        400 => 'The data supplied is in the wrong format, invalid length or null.',
        401 => 'None of the API keys provided were valid.',
        402 => 'Maximum number of API calls per hour exceeded.',
        500 => 'Internal server error.'
    );

    /**
     * Constructor.
     *
     * @param array $options Options for the API call.
     */
    public function __construct($options = array())
    {
        if (!isset($options['apikey'])) {
            return $this->error('You must supply an API key');
        } else {
            $this->apiKey = $options['apikey'];
        }

        if (isset($options['developerkey'])) {
            $this->devKey = $options['developerkey'];
        }

        if (isset($options['debug'])) {
            $this->debug = true;
        }

        return true;
    }

    /**
     * Sets the error type to be used.
     *
     * Supported values are:
     * - self::LIB_ERROR_TYPE_ERROR
     * - self::LIB_ERROR_TYPE_EXCEPTION
     *
     * @param mixed $errorType
     *
     * @return $this
     */
    public function setErrorType($errorType)
    {
        $this->errorType = $errorType;

        return $this;
    }

    /**
     * Returns the error type to be used.
     *
     * Possible values are:
     * - self::LIB_ERROR_TYPE_ERROR
     * - self::LIB_ERROR_TYPE_EXCEPTION
     *
     * @return string
     */
    public function getErrorType()
    {
        return $this->errorType;
    }

    /**
     * Verifies the API key.
     *
     * @param bool $key [optional] If not set, the one used in __construct() is used.
     *
     * @return bool|mixed|\SimpleXMLElement|string
     */
    public function verify($key = false)
    {
        $options = array();

        if ($key !== false) {
            $options['apikey'] = $key;
        } else {
            $options['apikey'] = $this->apiKey;
        }

        if ($this->devKey) {
            $options['developerkey'] = $this->devKey;
        }

        // check multiple api-keys
        if (strpos($options['apikey'], ',')) {
            $keys = explode(',', $options['apikey']);
            foreach ($keys as $api) {
                $options['apikey'] = $api;
                if (!$this->makeApiCall(self::API_URL_VERIFY, $options)) {
                    return $this->makeApiCall(self::API_URL_VERIFY, $options);
                }
            }
            return true;
        } else {
            return $this->makeApiCall(self::API_URL_VERIFY, $options);
        }
    }

    /**
     * Sends a notification with the given parameters.
     *
     * @param string      $application Application name.
     * @param string      $event       Event name.
     * @param string      $description Event description.
     * @param int         $priority    Notification priority.
     * @param string|bool $apiKeys     Comma separated list of API keys.
     * @param array       $options     API options.
     *
     * @return bool|mixed|\SimpleXMLElement|string
     */
    public function notify(
        $application = '',
        $event = '',
        $description = '',
        $priority = 0,
        $apiKeys = '',
        $options = array()
    ) {
        if (empty($application) || empty($event) || empty($description)) {
            return $this->error(
                'You must supply an application name, event and description'
            );
        }

        // place here so other parameter settings can override this
        $post = array();

        // notify options present? This can be: url or content-type for now.
        if (count($options) > 0) {
            foreach ($options as $k => $v) {
                $post[$k] = $v;
            }
        }

        $post['application'] = substr($application, 0, 256);
        $post['event'] = substr($event, 0, 1000);
        $post['description'] = substr($description, 0, 10000);
        $post['priority'] = $priority;

        if (!empty($this->devKey)) {
            $post['developerkey'] = $this->devKey;
        }

        if (!empty($apiKeys)) {
            $post['apikey'] = $apiKeys;
        } else {
            $post['apikey'] = $this->apiKey;
        }

        return $this->makeApiCall(self::API_URL_NOTIFY, $post, 'POST');
    }

    /**
     * Returns the number of remaining available API calls.
     *
     * @return int
     */
    public function getApiCallsRemaining()
    {
        return $this->apiCallsRemaining;
    }

    /**
     * @return int
     */
    public function getApiLimitReset()
    {
        return $this->apiLimitReset;
    }

    /**
     * @return int
     */
    public function getLastStatus()
    {
        return $this->lastStatus;
    }

    /**
     * Calls the API with the given parameters.
     *
     * @param string     $url           URL for the API key.
     * @param array|null $params        API parameters.
     * @param string     $requestMethod HTTP Request Method.
     *
     * @return bool|mixed|\SimpleXMLElement|string
     * @throws \Exception
     */
    protected function makeApiCall(
        $url,
        $params = null,
        $requestMethod = 'GET'
    ) {
        $cParams = array(
            'http' => array(
                'method' => $requestMethod,
                'ignore_errors' => true
            )
        );
        if ($params !== null && !empty($params)) {
            $params = http_build_query($params, '', '&');
            if ($requestMethod == 'POST') {
                $cParams['http']['header']
                    = 'Content-Type: application/x-www-form-urlencoded';
                $cParams['http']['content'] = $params;
            } else {
                $url .= '?' . $params;
            }
        } else {
            return $this->error(
                'This API requires all calls to have params'
            );
        }

        $context = stream_context_create($cParams);
        $fp = fopen($url, 'rb', false, $context);
        if (!$fp) {
            $res = false;
        } else {
            $res = stream_get_contents($fp);
        }

        if ($res === false) {
            return $this->error("$requestMethod $url failed: $php_errormsg");
        }

        $r = simplexml_load_string($res);
        if ($r === null) {
            return $this->error("Failed to decode $res as xml");
        }
        return $this->processXmlReturn($r);
    }

    /**
     * Triggers a PHP error or throws an Exception.
     *
     * @param string $message Error message.
     * @param int    $type    Error type.
     *
     * @return bool
     * @throws \Exception
     */
    private function error($message, $type = E_USER_NOTICE)
    {
        if ($this->errorType == 'error') {
            trigger_error($message, $type);
            return false;
        } else {
            throw new \Exception($message, $type);
        }
    }

    /**
     * Processes the XML API response.
     *
     * @param \SimpleXMLElement $obj \SimpleXMLElement instance
     *
     * @return bool
     */
    private function processXmlReturn(\SimpleXMLElement $obj)
    {
        if (isset($obj->success)) {
            $this->lastStatus = $obj->success['@attributes']['code'];

            $this->apiCallsRemaining
                = $obj->success['@attributes']['remaining'];
            $this->apiLimitReset = $obj->success['@attributes']['resettimer'];
            return true;
        } elseif (isset($obj->error)) {
            if (isset($obj->error['@attributes'])) {
                $this->lastStatus = $obj->error['@attributes']['code'];

                if (isset($obj->error['@attributes']['resettimer'])) {
                    $this->apiLimitReset
                        = $obj->error['@attributes']['resettimer'];
                }

            }
            return $this->error($obj->error);
        } else {
            return $this->error('Unknown error');
        }
    }
}
