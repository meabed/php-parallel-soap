<?php
/**
 * Asynchronous/Synchronous Soap Class
 *
 * Implements soap with multi server-to-server calls using curl module.
 *
 * @author Mohamed Meabed <mo.meabed@gmail.com>
 *
 */
class SoapClientAsync extends SoapClient
{
    /**  array of all responses in the client */
    static $_soapResponses = array();

    /**  array of all requests in the client */
    static $_soapRequests = array();

    /**  array of all requests actions in the client */
    static $_actions = array();

    /**  string the xml returned from soap call */
    static $_xmlResponse;

    /**  string current method call  */
    static $_action;

    /**  array of all requestIds  */
    static $_requestIds;

    /**  string last request id  */
    static $_lastRequestId;

    /**  bool soap asynchronous flag  */
    static $_async = false;

    /**  bool soap verbose flag  */
    static $_debug = false;

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
        $action = static::$_action;

        /** return the xml response as its coming from normal soap call */
        if ($action == static::GET_RESULT && static::$_xmlResponse) {
            return static::$_xmlResponse;
        }

        $_soapResponses = & static::$_soapResponses;
        $_soapRequests = & static::$_soapRequests;

        /** @var $id string represent hashId of each request based on the request body to avoid multiple calls for the same request if exists */
        $id = sha1($location . $request);
        /** if curl is not enabled use parent::__doRequest */
        if (!in_array('curl', get_loaded_extensions())) {
            if (isset($_soapResponses[$id])) {
                unset($_soapResponses[$id]);
                return parent::__doRequest($request, $location, $action, $version, $one_way = 0);
            }
            $_soapRequests[$id] = true;
            return "";
        }
        /** return response if soap method called for second time with same parameters */
        if (isset($_soapResponses[$id])) {
            $data = $_soapResponses[$id];
            unset($_soapResponses[$id]);
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
        curl_setopt($ch, CURLOPT_VERBOSE, static::$_debug);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $_soapRequests[$id] = $ch;


        static::$_requestIds[$id] = $id;
        static::$_actions[$id] = $action;
        static::$_lastRequestId = $id;

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
        static::$_action = $method;

        if (!static::$_async) {
            try {
                parent::__call($method, $args);
                /** parse the xml response or throw an exception */
                static::$_xmlResponse = $this->run(array(static::$_lastRequestId));
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
                $result = static::$_lastRequestId;
            } catch (SoapFault $ex) {
                /** catch SoapFault [is not a valid method for this service] and return null */
                $result = null;
            }
            /**
             * Return the Request ID to the calling method
             * This next 2 lines should be custom implementation based on your solution.
             * @var $result string ,On multiple calls Simulate the response from Soap API to return the request Id of each call to be able to get the response with it
             */
            //$result = new stdClass();
            //$result->{$method . 'Return'} = static::$_lastRequestId;
        }
        return $result;
    }

    /**
     * Execute all or some items from @var static::$_soapRequests
     *
     * @param array $requestIds
     * @param bool  $partial
     */
    public function doRequests($requestIds = array(), $partial = false)
    {
        $_allSoapRequests = & static::$_soapRequests;
        $_soapResponses = & static::$_soapResponses;

        /** Determine if its partial call to execute some requests or execute all the request in $_soapRequests array otherwise */
        if ($partial) {
            $_soapRequests = array_intersect_key($_allSoapRequests, array_flip($requestIds));
        } else {
            $_soapRequests = & static::$_soapRequests;
        }

        /** return the response from __doRequest() if curl is not enabled */
        if (!in_array('curl', get_loaded_extensions())) {
            foreach ($_soapRequests as $id => $ch) {
                $_soapResponses[$id] = true;
            }
            $_soapRequests = array();
            return;
        }
        /** Initialise curl multi handler and execute the requests  */
        $mh = curl_multi_init();
        foreach ($_soapRequests as $ch) {
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
        foreach ($_soapRequests as $id => $ch) {
            try {
                $_soapResponses[$id] = curl_multi_getcontent($ch);
                if ($_soapResponses[$id] === null) {
                    throw new SoapFault("HTTP", curl_error($ch));
                }
            } catch (SoapFault $e) {
                $_soapResponses[$id] = $e;
            }
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }
        curl_multi_close($mh);

        /** unset the request performed from the class instance variable $_soapRequests so we don't request them again */
        if (!$partial) {
            $_soapRequests = array();
        }
        foreach ($_soapRequests as $id => $ch) {
            unset($_allSoapRequests[$id]);
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
        $_allSoapResponses = & static::$_soapResponses;

        /** perform all the request */
        $this->doRequests($requestIds, $partial);

        /** reset the class to synchronous mode */
        static::$_async = false;

        /** parse return response of the performed requests  */
        if ($partial) {
            $_soapResponses = array_intersect_key($_allSoapResponses, array_flip($requestIds));
        } else {
            $_soapResponses = & static::$_soapResponses;
        }
        /** if its one request return the first element in the array */
        if ($partial && count($requestIds) == 1) {
            $result = $_soapResponses[$requestIds[0]];
            unset($_allSoapResponses[$requestIds[0]]);

        } else {
            $result = $this->getMultiResponses($_soapResponses);
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
        static::$_action = static::GET_RESULT;
        foreach ($responses as $id => $ch) {
            try {
                static::$_xmlResponse = $ch;
                if($ch instanceof SoapFault)
                {
                    throw $ch;
                }
                $resultObj = parent::__call(static::$_actions[$id], array());
                /**
                 * Return the Request ID to the calling method
                 * This next lines should be custom implementation based on your solution.
                 * @var $result string ,On multiple calls Simulate the response from Soap API to return the request Id of each call to be able to get the response with it
                 */
                //$result[$id] = $resultObj->{static::$_actions[$id] . 'Return'};
                $result[$id] = $resultObj;
            } catch (SoapFault $ex) {
                $result[$id] = $ex;
            }
            unset(static::$_soapResponses[$id]);
        }
        static::$_xmlResponse = '';
        static::$_action = '';
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
        static::$_action = static::GET_RESULT;
        try {
            $result = parent::__call($method, $args);
        } catch (SoapFault $ex) {
            throw $ex;
        }
        static::$_action = '';
        return $result;
    }
}