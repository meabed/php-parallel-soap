<?php
require_once __DIR__ . '/../vendor/autoload.php';

// example soap action
// @link https://github.com/amabnl/amadeus-ws-client/blob/master/tests/Amadeus/Client/Session/Handler/testfiles/testwsdl.wsdl#L48
// @link http://www.crcind.com/csp/samples/SOAP.Demo.cls?WSDL

/**
 * The test soap Server is Located @ https://soap-server-hello.herokuapp.com/wsdl.php
 */

/** the main soap class */
require_once(__DIR__ . '/../src/ParallelSoapClient.php');

/** @var string $wsdl , This is the test server i have generated to test the class */
$wsdl = "https://soap-server-hello.herokuapp.com/wsdl.php";
// parse response function
$parseResultFn = function ($method, $res) {
    switch ($method) {
        case 'Login':
            $ret = $res->SessionId;
            break;
        case 'SayHello':
            $ret = $res->Text;
            break;
        case 'GetFullName':
            $ret = $res->FullName;
            break;
        default:
            $ret = $res;
    }
    return $ret;
};
/** @var array $options , array of options for the soap client */
$options = [
    'connection_timeout' => 40,
    'trace' => true,
    'exceptions' => true,
    'soap_version' => SOAP_1_1,
    'cache_wsdl' => WSDL_CACHE_BOTH,
    'encoding' => 'UTF-8',
    'resFn' => $parseResultFn,
];

/** @var \Soap\ParallelSoapClient $client New Soap client instance */
$client = new \Soap\ParallelSoapClient($wsdl, $options);

/**
 * You can set debug mode to true to see curl verbose response if you run the script from command line
 *
 * @default false
 */
// $client->setDebug(false);

/** @var string $session , SessionId required in SoapOperations */
$session = null;
/** Normal ONE SOAP CALL Using CURL same exact as soap synchronous api calls of any web service */
try {
    $loginSoapCall = $client->Login(['demo', '123456']);
    $session = $loginSoapCall;
} /** catch SoapFault exception if it happens */
catch (SoapFault $ex) {
    print 'SoapFault: ' . $ex->faultcode . ' - ' . $ex->getMessage() . "\n";
} /** catch Exception if it happens */
catch (Exception $e) {
    print 'Exception: ' . $e->faultcode . ' - ' . $e->getMessage() . "\n";
}

/**
 * set SoapClient Mode to asynchronous mode.
 * This will allow opening as many as connections to the host and perform all
 * request at once so you don't need to wait for consecutive calls to performed after each other
 *
 * @default is false AND it get reset back to FALSE after each $client->run();
 *
 */
$client->setMulti(true);

/** @var array $requestIds */
$requestIds = [];

/** in the next for loop i will make 5 soap request */
for ($i = 0; $i < 5; $i++) {
    $requestIds[] = $client->GetFullName($session, 'Mo', 'Meabed ' . $i);
}

/** SoapCall without sessionId that return exception to test the exception handling */
$requestIds[] = $client->GetFullName('wrongParam', 'Dummy');

/**
 * This call will throw SoapFault and it will return
 * string as  ".. Function ("getUnkownMethod") is not a valid method for this service...."
 * So it will be easy to debug it.
 *
 * @note The call will not be executed when $client->run()
 */
$requestIds[] = $client->UnkownMethod('wrongParam', 'Dummy');

/**
 * This call will throw SoapFault and it will return
 * string as  ".. Function ("getAnotherUnkownMethod") is not a valid method for this service...."
 * So it will be easy to debug it.
 *
 * @note The call will not be executed when $client->run()
 */
$requestIds[] = $client->AnotherUnkownMethod(['dummy' => 'test']);

/**
 * This call is valid method but it has wrong parameters so it will return normal request id but in the execution
 * it will return result instance of SoapFault contains the exception
 * So you can handle it
 */
$requestIds[] = $client->GetFullName('wrongParams', 'Xyz');


/**
 * Adding another 5 SoapCalls to test different method call
 * in the next for loop i will make 5 soap request
 */
for ($i = 0; $i < 5; $i++) {
    /** @var $params , method parameters will be used in the test */
    $requestIds[] = $client->SayHello($session, 'Name ' . $i);
}

/** You can see the request ids in the variable that will be executed with $client->run() method */
print_r($requestIds);

/**
 * You can execute certain requests if you pass array of requestIds to $client->run() method as in the example in the
 * comment
 * $client->run(array(0,2,3,6,7)); , This will execute this requests only from the 10 requests we did before
 */
/** @var $responses array that hold the response array as array( requestId => responseObject ); */
$responses = $client->run();


/** Loop through the responses and get the results */
foreach ($responses as $id => $response) {
    /**
     * Handle exception when you using multi request is different than normal requests
     * The Client in asynchronous mode already handle the exception and assign the exception object to the result in-case exception occurred
     * So to handle the exception we don't use try{}catch(){} here, but we use instanceof to handle the exceptions as the example below
     */
    if ($response instanceof SoapFault) {
        /** handle the exception here  */
        print 'SoapFault: ' . $response->faultcode . ' - ' . $response->getMessage() . "\n";
    } else {
        /** SoapResponse is Okay */
        /**
         * I have made the SoapServer always return the Response in class attribute public $Return
         * Usually the soap server has pattern to return response for all method calls
         *
         * @example
         * if i call method getUser the return object will be $response->getUserResponse
         * getName => $response->getNameResponse
         * logout => $response->logoutResponse
         *
         * @Important please check ParallelSoapClient NOTES in @line 153 and @line 295 For auto implementation of the soap response pattern
         *
         */
        if (!is_string($response)) {
            $response = json_encode($response);
        }
        print 'Response is : ' . $response . "\n";
    }
}
