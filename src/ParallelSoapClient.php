<?php

namespace Soap;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
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
class ParallelSoapClient extends \SoapClient implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var array of all responses in the client */
    public $soapResponses = [];

    /** @var array of all requests in the client */
    public $soapRequests = [];

    /** @var array of all requests xml in the client */
    public $requestXmlArr = [];

    /** @var string the xml returned from soap call */
    public $xmlResponse;

    /** @var string current method call */
    public $soapMethod;

    /** @var array of all requests soap methods in the client */
    public $soapMethodArr = [];

    /** @var array of all requestIds */
    public $requestIds;

    /** @var string last request id */
    public $lastRequestId;

    /** @var bool soap parallel flag */
    public $multi = false;

    /** @var bool log soap request */
    public $logSoapRequest = false;

    /** @var array defaultHeaders used for curl request */
    public $defaultHeaders = [
        'Content-type' => 'Content-type: text/xml;charset=UTF-8',
        'Accept' => 'Accept: text/xml',
        // Empty Expect @https://gms.tf/when-curl-sends-100-continue.html
        // @https://stackoverflow.com/questions/7551332/do-we-need-the-expect-100-continue-header-in-the-xfire-request-header
        'Expect' => 'Expect:',
    ];

    /** @var array of all curl_info in the client */
    public $curlInfo = [];

    /** @var array curl options */
    public $curlOptions = [];

    /** @var \Closure */
    protected $debugFn;

    /** @var \Closure */
    protected $formatXmlFn;

    /** @var \Closure */
    protected $resFn;

    /** @var \Closure */
    protected $soapActionFn;

    /** @var array curl share ssl */
    public $sharedCurlData = [];

    /** @var string getRequestResponse action constant used for parsing the xml with from parent::__doRequest */
    public const GET_RESPONSE_CONST = 'getRequestResponseMethod';
    /** @var string text prefix, if error happen due to SOAP error before its executed ex:invalid method */
    public const ERROR_STR = '*ERROR*';

    /**
     * @return mixed
     */
    public function getMulti()
    {
        return $this->multi;
    }

    /**
     * @param mixed $multi
     *
     * @return ParallelSoapClient
     */
    public function setMulti($multi): ParallelSoapClient
    {
        $this->multi = $multi;
        return $this;
    }

    /**
     * @return array
     */
    public function getCurlOptions(): array
    {
        return $this->curlOptions;
    }

    /**
     * @param array $curlOptions
     *
     * @return ParallelSoapClient
     */
    public function setCurlOptions(array $curlOptions): ParallelSoapClient
    {
        $this->curlOptions = $curlOptions;
        return $this;
    }

    /**
     * @return bool
     */
    public function isLogSoapRequest(): bool
    {
        return $this->logSoapRequest;
    }

    /**
     * @param bool $logSoapRequest
     *
     * @return ParallelSoapClient
     */
    public function setLogSoapRequest(bool $logSoapRequest): ParallelSoapClient
    {
        $this->logSoapRequest = $logSoapRequest;
        return $this;
    }

    /**
     * @return \Closure
     */
    public function getDebugFn(): \Closure
    {
        return $this->debugFn;
    }

    /**
     * @param \Closure $debugFn
     *
     * @return ParallelSoapClient
     */
    public function setDebugFn(\Closure $debugFn): ParallelSoapClient
    {
        $this->debugFn = $debugFn;
        return $this;
    }

    /**
     * @return \Closure
     */
    public function getFormatXmlFn(): \Closure
    {
        return $this->formatXmlFn;
    }

    /**
     * @param \Closure $formatXmlFn
     *
     * @return ParallelSoapClient
     */
    public function setFormatXmlFn(\Closure $formatXmlFn): ParallelSoapClient
    {
        $this->formatXmlFn = $formatXmlFn;
        return $this;
    }

    /**
     * @return \Closure
     */
    public function getResFn(): \Closure
    {
        return $this->resFn;
    }

    /**
     * @param \Closure $resFn
     *
     * @return ParallelSoapClient
     */
    public function setResFn(\Closure $resFn): ParallelSoapClient
    {
        $this->resFn = $resFn;
        return $this;
    }

    /**
     * @return \Closure
     */
    public function getSoapActionFn(): \Closure
    {
        return $this->soapActionFn;
    }

    /**
     * @param \Closure $soapActionFn
     *
     * @return ParallelSoapClient
     */
    public function setSoapActionFn(\Closure $soapActionFn): ParallelSoapClient
    {
        $this->soapActionFn = $soapActionFn;
        return $this;
    }


    public function __construct($wsdl, array $options = null)
    {
        // logger
        $logger = $options['logger'] ?? new NullLogger();
        $this->setLogger($logger);

        // debug function to add headers / last request / response / etc...
        $debugFn = $options['debugFn'] ?? static function ($res, $id) {
        };
        $this->setDebugFn($debugFn);

        // format xml before logging
        $formatXmlFn = $options['formatXmlFn'] ?? static function ($xml) {
            return $xml;
        };
        $this->setFormatXmlFn($formatXmlFn);

        // result parsing function
        $resFn = $options['resFn'] ?? static function ($method, $res) {
            return $res;
        };
        $this->setResFn($resFn);

        // soapAction function to set in the header
        // Ex: SOAPAction: "http://tempuri.org/SOAP.Demo.AddInteger"
        // Ex: SOAPAction: "http://webservices.amadeus.com/PNRRET_11_3_1A"
        $soapActionFn = $options['soapActionFn'] ?? static function ($action, $headers) {
            $headers[] = 'SOAPAction: "' . $action . '"';
            // 'SOAPAction: "' . $soapAction . '"', pass the soap action in every request from the WSDL if required
            return $headers;
        };
        $this->setSoapActionFn($soapActionFn);

        // cleanup
        unset($options['logger'], $options['debugFn'], $options['resFn'], $options['soapActionFn']);

        parent::__construct($wsdl, $options);
    }

    /**
     * Soap __doRequest() Method with CURL Implementation
     *
     * @param string $request The XML SOAP request
     * @param string $location The URL to request
     * @param string $action The SOAP action
     * @param int $version The SOAP version
     * @param bool $oneWay If one_way is set to 1, this method returns nothing. Use this where a response is not expected
     *
     * @return string
     */
    public function __doRequest(string $request, string $location, string $action, int $version, bool $oneWay = false): ?string
    {
        $shouldGetResponse = ($this->soapMethod == static::GET_RESPONSE_CONST);

        // print xml for debugging testing
        if ($this->logSoapRequest) {
            // debug the request here
            $this->logger->debug($this->formatXml($request));
        }

        // some .NET Servers only accept action method with ns url!! uncomment it if you get error wrong command
        /** return the xml response as its coming from normal soap call */
        if ($shouldGetResponse && $this->xmlResponse) {
            return $this->xmlResponse;
        }

        $soapRequests = &$this->soapRequests;

        /** @var string $id represent hashId of each request based on the request body to avoid multiple calls for the same request if exists */
        $id = sha1($location . $request);

        /** @var $headers array of headers to be sent with request */
        $this->defaultHeaders['Content-length'] = "Content-length: " . strlen($request);

        // pass the soap action in every request from the WSDL if required
        $soapActionFn = $this->soapActionFn;
        $headers = array_values($soapActionFn($action, $this->defaultHeaders));

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
     *
     * @param string $method
     * @param array $args
     *
     * @return string|mixed
     * @throws \Exception|\SoapFault
     */
    public function callOne(string $method, array $args)
    {
        if (version_compare(PHP_VERSION, '8.0.0', '>=')) {
            parent::__soapCall($method, $args);
        } else {
            parent::__call($method, $args);
        }
        /** parse the xml response or throw an exception */
        $this->xmlResponse = $this->run([$this->lastRequestId]);
        return $this->getResponseResult($method, $args);
    }

    /**
     * Call Parallel method, suppress exception and convert to string
     *
     * @param string $method
     * @param array $args
     *
     * @return string|mixed
     */
    public function callMulti(string $method, array $args)
    {
        /** generate curl session and add the soap requests to execute it later  */
        try {
            if (version_compare(PHP_VERSION, '8.0.0', '>=')) {
                parent::__soapCall($method, $args);
            } else {
                parent::__call($method, $args);
            }
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
            $res = static::ERROR_STR . ':' . $method . ' - ' . $ex->getCode() . ' - ' . $ex->getMessage() . ' - rand::' . mt_rand();
        }

        return $res;
    }

    /**
     * __soapCall Magic method to allow one and Parallel Soap calls with exception handling
     *
     *
     * @param $name
     * @param array $args
     * @param null $options
     * @param null $inputHeaders
     * @param null &$outputHeaders
     * @return string|mixed
     * @throws \Exception
     * @throws \SoapFault
     */
    public function __soapCall($name, array $args, $options = null, $inputHeaders = null, &$outputHeaders = null): mixed
    {
        /** set current action to the current method call */
        $this->soapMethod = $name;

        if (!$this->multi) {
            return $this->callOne($name, $args);
        }

        return $this->callMulti($name, $args);
    }

    /**
     * __call Magic method to allow one and Parallel Soap calls with exception handling
     *
     * @param string $name
     * @param array $args
     *
     * @return string|mixed
     * @throws \Exception
     * @throws \SoapFault
     */
    public function __call($name, array $args): mixed
    {
        /** set current action to the current method call */
        $this->soapMethod = $name;

        if (!$this->multi) {
            return $this->callOne($name, $args);
        }

        return $this->callMulti($name, $args);
    }

    /**
     * Execute all or some items from $this->soapRequests
     *
     * @param mixed $requestIds
     * @param bool $partial
     */
    public function doRequests($requestIds = [], bool $partial = false): void
    {
        $allSoapRequests = &$this->soapRequests;
        $soapResponses = &$this->soapResponses;

        /** Determine if its partial call to execute some requests or execute all the request in $soapRequests array otherwise */
        if ($partial) {
            $soapRequests = array_intersect_key($allSoapRequests, array_flip($requestIds));
        } else {
            $soapRequests = &$this->soapRequests;
        }

        /** Initialise curl multi handler and execute the requests  */
        $mh = curl_multi_init();
        foreach ($soapRequests as $ch) {
            curl_multi_add_handle($mh, $ch);
        }

        $active = null;
        do {
            $mrc = curl_multi_exec($mh, $active);
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

                // @link http://stackoverflow.com/questions/14319696/soap-issue-soapfault-exception-client-looks-like-we-got-no-xml-document
                if ($soapResponses[$id] === null) {
                    throw new \SoapFault("HTTP", curl_error($ch));
                }
            } catch (\Exception $e) {
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
    public function run(array $requestIds = []): mixed
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
    public function getMultiResponses(array $responses = []): mixed
    {
        $resArr = [];
        $this->soapMethod = static::GET_RESPONSE_CONST;

        foreach ($responses as $id => $ch) {
            try {
                $this->xmlResponse = $ch;
                if ($ch instanceof \Exception) {
                    throw $ch;
                }
                if (version_compare(PHP_VERSION, '8.0.0', '>=')) {
                    $res = parent::__soapCall($this->soapMethodArr[$id], []);
                } else {
                    $res = parent::__call($this->soapMethodArr[$id], []);
                }
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
            } catch (\Exception $ex) {
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
     * @return string $res
     * @throws \Exception|\SoapFault|
     */
    public function getResponseResult(string $method, $args): string
    {
        $this->soapMethod = static::GET_RESPONSE_CONST;

        try {
            if (version_compare(PHP_VERSION, '8.0.0', '>=')) {
                $res = parent::__soapCall($method, $args);
            } else {
                $res = parent::__call($method, $args);
            }
            $id = $this->lastRequestId;
            $this->addDebugData($res, $id);
        } catch (\Exception $ex) {
            // todo find better pattern for handling non-xml document with error handling
            // if (strtolower($ex->getMessage()) == 'looks like we got no XML document') {}
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
     * @return mixed
     * @author Mohamed Meabed <mohamed.meabed@tajawal.com>
     */
    public function addDebugData($res, $id): mixed
    {
        $fn = $this->debugFn;
        return $fn($res, $id);
    }

    /**
     * format xml
     *
     * @param $request
     *
     * @return mixed
     * @author Mohamed Meabed <mohamed.meabed@tajawal.com>
     */
    public function formatXml($request): mixed
    {
        $fn = $this->formatXmlFn;
        return $fn($request);
    }
}
