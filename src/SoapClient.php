<?php
/**
 * Asynchronous/Synchronous Soap Class
 *
 * Implements soap with multi server-to-server calls using curl module.
 *
 * @author Mohamed Meabed <mo.meabed@gmail.com>
 * @link https://github.com/Meabed/asynchronous-soap
 * @note Check the Example files and read the documentation carefully
 */
class SoapClientAsync extends SoapClient
{
    /**  array of all responses in the client */
    static $soapResponses = array();

    /**  array of all requests in the client */
    static $soapRequests = array();

    /**  array of all requests actions in the client */
    static $actions = array();

    /**  string the xml returned from soap call */
    static $xmlResponse;

    /**  string current method call  */
    static $action;

    /**  array of all requestIds  */
    static $requestIds;

    /**  string last request id  */
    static $lastRequestId;

    /**  bool soap asynchronous flag  */
    static $async = false;

    /**  bool soap verbose flag  */
    static $debug = false;

    /**  getResult action constant used for parsing the xml with from parent::__doRequest */
    const GET_RESULT = 'getResult';

    /**
     * Soap __doRequest() Method with CURL Implementation
     * @param string $request
     * @param string $location
     * @param string $action
     * @param int    $version
     * @param int    $one_way
     *
     * @return string
     * @throws SoapFault
     */
    public function __doRequest($request, $location, $action, $version, $one_way = 0)
    {
        $action = static::$action;

        /** return the xml response as its coming from normal soap call */
        if ($action == static::GET_RESULT && static::$xmlResponse) {
            return static::$xmlResponse;
        }

        $soapResponses = & static::$soapResponses;
        $soapRequests = & static::$soapRequests;

        /** @var $id string represent hashId of each request based on the request body to avoid multiple calls for the same request if exists */
        $id = sha1($location . $request);
        /** if curl is not enabled use parent::__doRequest */
        if (!in_array('curl', get_loaded_extensions())) {
            if (isset($soapResponses[$id])) {
                unset($soapResponses[$id]);
                return parent::__doRequest($request, $location, $action, $version, $one_way = 0);
            }
            $soapRequests[$id] = true;
            return "";
        }
        /** return response if soap method called for second time with same parameters */
        if (isset($soapResponses[$id])) {
            $data = $soapResponses[$id];
            unset($soapResponses[$id]);
            if ($data instanceof SoapFault) {
                throw $data;
            }
            return $data;
        }
        /** @var $headers array of headers to be sent with request */
        $headers = array(
            'Content-type: text/xml; charset=utf-8',
            'SOAPAction: "' . $action . '"',
        );

        $ch = curl_init();
        /** CURL_OPTIONS  */
        curl_setopt($ch, CURLOPT_URL, $location);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_ENCODING, "");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
        curl_setopt($ch, CURLOPT_TIMEOUT, 50);
        curl_setopt($ch, CURLOPT_TIMECONDITION, 50);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_VERBOSE, static::$debug);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $soapRequests[$id] = $ch;


        static::$requestIds[$id] = $id;
        static::$actions[$id] = $action;
        static::$lastRequestId = $id;

        return "";
    }

    /**
     * __call Magic method to allow synchronous and asynchronous Soap calls with exception handling
     *
     * @param string $method
     * @param string $args
     *
     * @return mixed|stdClass
     * @throws Exception
     * @throws SoapFault
     * @throws Exception
     */
    public function __call($method, $args)
    {
        /** set current action to the current method call */
        static::$action = $method;

        if (!static::$async) {
            try {
                parent::__call($method, $args);
                /** parse the xml response or throw an exception */
                static::$xmlResponse = $this->run(array(static::$lastRequestId));
                $result = $this->getResponseResult($method, $args);
            } catch (SoapFault $ex) {
                throw $ex;
            } catch (Exception $e) {
                throw $e;
            }
        } else {
            /** generate curl session and add the soap requests to execute it later  */
            try{
                parent::__call($method, $args);
                /**
                 * Return the Request ID to the calling method
                 * This next 2 lines should be custom implementation based on your solution.
                 *
                 * @var $result string ,On multiple calls Simulate the response from Soap API to return the request Id of each call
                 * to be able to get the response with it
                 * @note check the example file to understand what to write here
                 */
                //$result = new stdClass();
                //$result->{$method . 'Return'} = static::$lastRequestId;
                $result = static::$lastRequestId;
            } catch (SoapFault $ex) {
                /** catch any SoapFault [is not a valid method for this service] and return null */
                $result = $method.' - '.$ex->getCode().' - '.$ex->getMessage().' - '.rand();
            }
        }
        return $result;
    }

    /**
     * Execute all or some items from @var static::$soapRequests
     *
     * @param array $requestIds
     * @param bool  $partial
     */
    public function doRequests($requestIds = array(), $partial = false)
    {
        $allSoapRequests = & static::$soapRequests;
        $soapResponses = & static::$soapResponses;

        /** Determine if its partial call to execute some requests or execute all the request in $soapRequests array otherwise */
        if ($partial) {
            $soapRequests = array_intersect_key($allSoapRequests, array_flip($requestIds));
        } else {
            $soapRequests = & static::$soapRequests;
        }

        /** return the response from __doRequest() if curl is not enabled */
        if (!in_array('curl', get_loaded_extensions())) {
            foreach ($soapRequests as $id => $ch) {
                $soapResponses[$id] = true;
            }
            $soapRequests = array();
            return;
        }
        /** Initialise curl multi handler and execute the requests  */
        $mh = curl_multi_init();
        foreach ($soapRequests as $ch) {
            curl_multi_add_handle($mh, $ch);
        }
        $active = null;
        do {
            $mrc = curl_multi_exec($mh, $active);
        } while ($mrc == CURLM_CALL_MULTI_PERFORM);

        while ($active && $mrc == CURLM_OK) {
            if (curl_multi_select($mh) != -1) {
                do {
                    $mrc = curl_multi_exec($mh, $active);
                } while ($mrc == CURLM_CALL_MULTI_PERFORM);
            }
        }
        /** assign the responses for all requests has been performed */
        foreach ($soapRequests as $id => $ch) {
            try {
                $soapResponses[$id] = curl_multi_getcontent($ch);
                if ($soapResponses[$id] === null) {
                    throw new SoapFault("HTTP", curl_error($ch));
                }
            } catch (SoapFault $e) {
                $soapResponses[$id] = $e;
            }
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }
        curl_multi_close($mh);

        /** unset the request performed from the class instance variable $soapRequests so we don't request them again */
        if (!$partial) {
            $soapRequests = array();
        }
        foreach ($soapRequests as $id => $ch) {
            unset($allSoapRequests[$id]);
        }
    }

    /**
     * Main method to perform all or some Soap requests
     *
     * @param array $requestIds
     * @return string $result
     */
    public function run($requestIds = array())
    {
        $partial = false;

        if (is_array($requestIds) && count($requestIds)) {
            $partial = true;
        }
        $allSoapResponses = & static::$soapResponses;

        /** perform all the request */
        $this->doRequests($requestIds, $partial);

        /** reset the class to synchronous mode */
        static::$async = false;

        /** parse return response of the performed requests  */
        if ($partial) {
            $soapResponses = array_intersect_key($allSoapResponses, array_flip($requestIds));
        } else {
            $soapResponses = & static::$soapResponses;
        }
        /** if its one request return the first element in the array */
        if ($partial && count($requestIds) == 1) {
            $result = $soapResponses[$requestIds[0]];
            unset($allSoapResponses[$requestIds[0]]);

        } else {
            $result = $this->getMultiResponses($soapResponses);
        }
        return $result;
    }

    /**
     * Parse Response of Soap Requests with parent::__doRequest()
     *
     * @param array $responses
     * @return string $result
     */
    public function getMultiResponses($responses = array())
    {
        $result = array();
        static::$action = static::GET_RESULT;
        foreach ($responses as $id => $ch) {
            try {
                static::$xmlResponse = $ch;
                if($ch instanceof SoapFault)
                {
                    throw $ch;
                }
                $resultObj = parent::__call(static::$actions[$id], array());
                /**
                 * Return the Request ID to the calling method
                 * This next lines should be custom implementation based on your solution.
                 *
                 * @var $result string ,On multiple calls Simulate the response from Soap API to return the request Id of each call
                 * to be able to get the response with it
                 * @note check the example file to understand what to write here
                 */
                //$result[$id] = $resultObj->{static::$actions[$id] . 'Return'};
                $result[$id] = $resultObj;
            } catch (SoapFault $ex) {
                $result[$id] = $ex;
            }
            unset(static::$soapResponses[$id]);
        }
        static::$xmlResponse = '';
        static::$action = '';
        return $result;
    }
    /**
     * Parse Response of Soap Requests with parent::__doRequest()
     *
     * @param string $method
     * @param array $args
     *
     * @throws SoapFault $ex
     * @return string $result
     */
    public function getResponseResult($method, $args)
    {
        static::$action = static::GET_RESULT;
        try {
            $result = parent::__call($method, $args);
        } catch (SoapFault $ex) {
            throw $ex;
        }
        static::$action = '';
        return $result;
    }
}