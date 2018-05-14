<?php

namespace Soap;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Single/Parallel Soap Class
 *
 * Implements soap with multi server-to-server calls using curl module.
 *
 * @author Mohamed Meabed <mo.meabed@gmail.com>
 * @link   https://github.com/Meabed/php-parallel-soap
 * @note   Check the Example files and read the documentation carefully
 */
class ParallelSoapClient extends \SoapClient
{
    /**  array of all responses in the client */
    public $soapResponses = [];

    /**  array of all requests in the client */
    public $soapRequests = [];

    /**  array of all requests xml in the client */
    public $requestXmlArr = [];

    /**  string the xml returned from soap call */
    public $xmlResponse;

    /**  string current method call  */
    public $soapMethod;

    /**  array of all requests soap methods in the client */
    public $soapMethodArr = [];

    /**  array of all requestIds  */
    public $requestIds;

    /**  string last request id  */
    public $lastRequestId;

    /**  bool soap parallel flag  */
    public $multi = false;

    /** @var bool log soap request */
    public $logSoapRequest = false;

    /** @var bool log pretty xml */
    public $logPrettyXml = false;

    /**  array of all curl_info in the client */
    public $curlInfo = [];

    /** @var array curl options */
    public $curlOptions = [];

    /** @var \Closure */
    public $debugFn;

    /** @var \Closure */
    public $resFn;

    /** @var \Closure */
    public $soapActionFn;

    /** @var LoggerInterface */
    public $logger;

    /**
     * @var array
     */
    public $sharedCurlData = [];

    /**  getRequestResponse action constant used for parsing the xml with from parent::__doRequest */
    const GET_RESPONSE_CONST = 'getRequestResponseMethod';
    const ERROR_STR = '*ERROR*';


    /**
     * @return mixed
     */
    public function getMulti()
    {
        return $this->multi;
    }

    /**
     * @param mixed $multi
     * @return ParallelSoapClient
     */
    public function setMulti($multi)
    {
        $this->multi = $multi;
        return $this;
    }

    /**
     * @return array
     */
    public function getCurlOptions()
    {
        return $this->curlOptions;
    }

    /**
     * @param array $curlOptions
     * @return ParallelSoapClient
     */
    public function setCurlOptions(array $curlOptions)
    {
        $this->curlOptions = $curlOptions;
        return $this;
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

    /**
     * @param LoggerInterface $logger
     * @return ParallelSoapClient
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
        return $this;
    }

    /**
     * @return bool
     */
    public function isLogSoapRequest()
    {
        return $this->logSoapRequest;
    }

    /**
     * @param bool $logSoapRequest
     * @return ParallelSoapClient
     */
    public function setLogSoapRequest(bool $logSoapRequest)
    {
        $this->logSoapRequest = $logSoapRequest;
        return $this;
    }

    /**
     * @return bool
     */
    public function isLogPrettyXml()
    {
        return $this->logPrettyXml;
    }

    /**
     * @param bool $logPrettyXml
     * @return ParallelSoapClient
     */
    public function setLogPrettyXml(bool $logPrettyXml)
    {
        $this->logPrettyXml = $logPrettyXml;
        return $this;
    }

    /**
     * @return \Closure
     */
    public function getDebugFn()
    {
        return $this->debugFn;
    }

    /**
     * @param \Closure $debugFn
     * @return ParallelSoapClient
     */
    public function setDebugFn(\Closure $debugFn)
    {
        $this->debugFn = $debugFn;
        return $this;
    }


    public function __construct($wsdl, array $options = null)
    {
        parent::__construct($wsdl, $options);
        $this->logger = $options['logger'] ?? new NullLogger();
        $this->debugFn = $options['debugFn'] ?? function ($res, $id) {
            };
        $this->resFn = $options['resFn'] ?? function ($method, $res) {
                return $res;
            };
        $this->soapActionFn = $options['soapActionFn'] ?? function ($action, $headers) {
                $headers[] = 'SOAPAction: "' . $action . '"';
                // 'SOAPAction: "' . $soapAction . '"', pass the soap action in every request from the WSDL if required
                return $headers;
            };

        // cleanup
        unset($options['logger']);
        unset($options['debugFn']);
        unset($options['resFn']);
        unset($options['soapActionFn']);
    }

    /**
     * Soap __doRequest() Method with CURL Implementation
     *
     * @param string $request The XML SOAP request
     * @param string $location The URL to request
     * @param string $action The SOAP action
     * @param int $version The SOAP version
     * @param int $one_way If one_way is set to 1, this method returns nothing. Use this where a response is not expected
     *
     * @return string
     * @throws \SoapFault
     */
    public function __doRequest($request, $location, $action, $version, $one_way = 0)
    {
        $shouldGetResponse = ($this->soapMethod == static::GET_RESPONSE_CONST);

        // print xml for debugging testing
        if ($this->logSoapRequest) {
            // debug the request here
            if ($this->isLogPrettyXml()) {
                $this->logger->debug($this->prettyXml($request));
            } else {
                $this->logger->debug($request);
            }
        }
        // some .NET Servers only accept action method with ns url!! uncomment it if you get error wrong command
        /** return the xml response as its coming from normal soap call */
        if ($shouldGetResponse && $this->xmlResponse) {
            return $this->xmlResponse;
        }

        $soapResponses = &$this->soapResponses;
        $soapRequests = &$this->soapRequests;

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
            if ($data instanceof \SoapFault) {
                throw $data;
            }

            return $data;
        }

        /** @var $headers array of headers to be sent with request */
        $defaultHeaders = [
            'Content-type: text/xml',
            'charset=utf-8',
            "Accept: text/xml",
            "Content-length: " . strlen($request),
        ];

        // pass the soap action in every request from the WSDL if required
        $soapActionFn = $this->soapActionFn;
        $headers = $soapActionFn($action, $defaultHeaders);

        // ssl connection sharing
        if (empty($this->sharedCurlData[$location])) {
            $shOpt = curl_share_init();
            curl_share_setopt($shOpt, CURLSHOPT_SHARE, CURL_LOCK_DATA_SSL_SESSION);
            curl_share_setopt($shOpt, CURLSHOPT_SHARE, CURL_LOCK_DATA_DNS);
            curl_share_setopt($shOpt, CURLSHOPT_SHARE, CURL_LOCK_DATA_COOKIE);
            $this->sharedCurlData[$location] = $shOpt;
        }

        $sh = $this->sharedCurlData[$location];

        $ch = curl_init();
        /** CURL_OPTIONS  */
        curl_setopt($ch, CURLOPT_URL, $location);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $request);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SHARE, $sh);

        // assign curl options
        foreach ($this->curlOptions as $key => $value) {
            curl_setopt($ch, $key, $value);
        }

        $soapRequests[$id] = $ch;

        $this->requestIds[$id] = $id;
        $this->soapMethodArr[$id] = $this->soapMethod;
        $this->requestXmlArr[$id] = $request;
        $this->lastRequestId = $id;

        return "";
    }

    /**
     * Call Sync method, act like normal soap method with extra implementation if needed
     * @param string $method
     * @param string $args
     * @throws \SoapFault
     * @throws \Exception
     * @return string|mixed||stdClass
     */
    public function callOne($method, $args)
    {
        try {
            parent::__call($method, $args);
            /** parse the xml response or throw an exception */
            $this->xmlResponse = $this->run([$this->lastRequestId]);
            $res = $this->getResponseResult($method, $args);
        } catch (\SoapFault $ex) {
            throw $ex;
        } catch (\Exception $e) {
            throw $e;
        }

        return $res;
    }

    /**
     * Call Parallel method, suppress exception and convert to string
     * @param string $method
     * @param string $args
     * @return string|mixed||stdClass
     */
    public function callParallel($method, $args)
    {
        /** generate curl session and add the soap requests to execute it later  */
        try {
            parent::__call($method, $args);
            /**
             * Return the Request ID to the calling method
             * This next 2 lines should be custom implementation based on your solution.
             *
             * @var $res string ,On multiple calls Simulate the response from Soap API to return the request Id of each call
             * to be able to get the response with it
             * @note check the example file to understand what to write here
             */
            $res = $this->lastRequestId;
        } catch (\Exception $ex) {
            /** catch any SoapFault [is not a valid method for this service] and return null */
            $res = self::ERROR_STR . ':' . $method . ' - ' . $ex->getCode() . ' - ' . $ex->getMessage() . ' - rand::' . rand();
        }

        return $res;
    }

    /**
     * __call Magic method to allow one and Parallel Soap calls with exception handling
     *
     * @param string $method
     * @param string $args
     *
     * @return string|mixed||stdClass
     * @throws \Exception
     * @throws \SoapFault
     */
    public function __call($method, $args)
    {
        /** set current action to the current method call */
        $this->soapMethod = $method;

        if (!$this->multi) {
            return $this->callOne($method, $args);
        } else {
            return $this->callParallel($method, $args);
        }
    }

    /**
     * Execute all or some items from $this->soapRequests
     *
     * @param mixed $requestIds
     * @param bool $partial
     */
    public function doRequests($requestIds = [], $partial = false)
    {
        $allSoapRequests = &$this->soapRequests;
        $soapResponses = &$this->soapResponses;

        /** Determine if its partial call to execute some requests or execute all the request in $soapRequests array otherwise */
        if ($partial) {
            $soapRequests = array_intersect_key($allSoapRequests, array_flip($requestIds));
        } else {
            $soapRequests = &$this->soapRequests;
        }

        /** return the response from __doRequest() if curl is not enabled */
        if (!in_array('curl', get_loaded_extensions())) {
            foreach ($soapRequests as $id => $ch) {
                $soapResponses[$id] = true;
            }
            $soapRequests = [];

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
            if ($mrc > 0) {
                //echo "ERREUR !\n " . curl_multi_strerror($mrc);
            }
        } while ($mrc === CURLM_CALL_MULTI_PERFORM || $active);

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
                // todo if config
                $curlInfo = curl_getinfo($ch);
                if ($curlInfo) {
                    $this->curlInfo[$id] = (object)$curlInfo;
                }

                // Source: http://stackoverflow.com/questions/14319696/soap-issue-soapfault-exception-client-looks-like-we-got-no-xml-document
                // $xml                = explode("\r\n", $soapResponses[$id]);
                // $soapResponses[$id] = preg_replace('/^(\x00\x00\xFE\xFF|\xFF\xFE\x00\x00|\xFE\xFF|\xFF\xFE|\xEF\xBB\xBF)/', "", $xml[0]);

                if ($soapResponses[$id] === null) {
                    throw new \SoapFault("HTTP", curl_error($ch));
                }
            } catch (\SoapFault $e) {
                $soapResponses[$id] = $e;
            }
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
        }
        curl_multi_close($mh);

        /** unset the request performed from the class instance variable $soapRequests so we don't request them again */
        if (!$partial) {
            $soapRequests = [];
        }
        foreach ($soapRequests as $id => $ch) {
            unset($allSoapRequests[$id]);
        }
    }

    /**
     * Main method to perform all or some Soap requests
     *
     * @param array $requestIds
     *
     * @return string $res
     */
    public function run($requestIds = [])
    {
        $partial = false;

        if (is_array($requestIds) && count($requestIds)) {
            $partial = true;
        }
        $allSoapResponses = &$this->soapResponses;

        /** perform all the request */
        $this->doRequests($requestIds, $partial);

        /** reset the class to synchronous mode */
        $this->setMulti(false);

        /** parse return response of the performed requests  */
        if ($partial) {
            $soapResponses = array_intersect_key($allSoapResponses, array_flip($requestIds));
        } else {
            $soapResponses = &$this->soapResponses;
        }
        /** if its one request return the first element in the array */
        if ($partial && count($requestIds) == 1) {
            $res = $soapResponses[$requestIds[0]];
            unset($allSoapResponses[$requestIds[0]]);
        } else {
            $res = $this->getMultiResponses($soapResponses);
        }

        return $res;
    }

    /**
     * Parse Response of Soap Requests with parent::__doRequest()
     *
     * @param array $responses
     *
     * @return mixed $resArr
     */
    public function getMultiResponses($responses = [])
    {
        $resArr = [];
        $this->soapMethod = static::GET_RESPONSE_CONST;

        foreach ($responses as $id => $ch) {
            try {
                $this->xmlResponse = $ch;
                if ($ch instanceof \SoapFault) {
                    throw $ch;
                }
                $res = parent::__call($this->soapMethodArr[$id], []);
                /**
                 * Return the Request ID to the calling method
                 * This next lines should be custom implementation based on your solution.
                 *
                 * @var $resArr string ,On multiple calls Simulate the response from Soap API to return the request Id of each call
                 * to be able to get the response with it
                 * @note check the example file to understand what to write here
                 */
                $resFn = $this->resFn;
                $resArr[$id] = $resFn($this->soapMethodArr[$id], $res);
                $this->addDebugData($res, $id);
            } catch (\SoapFault $ex) {
                $this->addDebugData($ex, $this->lastRequestId);
                $resArr[$id] = $ex;
            }
            unset($this->soapResponses[$id]);
        }
        $this->xmlResponse = '';
        $this->soapMethod = '';

        return $resArr;
    }

    /**
     * Parse Response of Soap Requests with parent::__doRequest()
     *
     * @param string $method
     * @param string|array $args
     *
     * @throws \SoapFault
     * @throws \Exception
     * @return string $res
     */
    public function getResponseResult($method, $args)
    {
        $this->soapMethod = static::GET_RESPONSE_CONST;

        try {
            $res = parent::__call($method, $args);

            $id = $this->lastRequestId;
            $this->addDebugData($res, $id);
        } catch (\SoapFault $ex) {
            $this->addDebugData($ex, $this->lastRequestId);
            throw $ex;
        } catch (\Exception $ex) {
            $this->addDebugData($ex, $this->lastRequestId);
            throw $ex;
        }
        $this->soapMethod = '';

        $resFn = $this->resFn;
        return $resFn($method, $res);
    }


    /**
     * Add curl info to response object
     *
     * @param $res
     * @param $id
     *
     * @author Mohamed Meabed <mohamed.meabed@tajawal.com>
     *
     */
    public function addDebugData($res, $id)
    {
        $fn = $this->debugFn;
        $fn($res, $id);
    }

    /**
     * Print pretty xml
     *
     * @param $request
     *
     * @return string
     *
     * @author Mohamed Meabed <mohamed.meabed@tajawal.com>
     *
     */
    public function prettyXml($request)
    {
        $dom = new \DOMDocument();
        $dom->preserveWhiteSpace = false;
        $dom->loadXML($request);
        $dom->formatOutput = true;

        return $dom->saveXml();
    }
}
