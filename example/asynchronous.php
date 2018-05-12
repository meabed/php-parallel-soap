<?php
require_once __DIR__ . '/../vendor/autoload.php';

/**
 * The test soap Server is Located @ https://whispering-meadow-99755.herokuapp.com/wsdl.php
 */

/** the main soap class */
require_once(__DIR__ . '/../src/SoapClientAsync.php');

/** @var string $wsdl , This is the test server i have generated to test the class */
$wsdl = "https://whispering-meadow-99755.herokuapp.com/wsdl.php";
/** @var array $options , array of options for the soap client */
$options = [
    'connection_timeout' => 40,
    'trace' => true,
];

/** @var \Soap\SoapClientAsync $client New Soap client instance */
$client = new \Soap\SoapClientAsync($wsdl, $options);

/**
 * You can set debug mode to true to see curl verbose response if you run the script from command line
 *
 * @default false
 */
$client->setDebug(false);

/** @var string $session , SessionId required in SoapOperations */
$session = null;
/** Normal ONE SOAP CALL Using CURL same exact as soap synchronous api calls of any web service */
try {
    $loginSoapCall = $client->login(['demo', '123456']);
    $session = $loginSoapCall;
    var_dump($session);exit;
} /** catch SoapFault exception if it happens */
catch (SoapFault $ex) {
    print 'SoapFault: ' . $ex->faultcode . ' - ' . $ex->getMessage() . "\n";
} /** catch Exception if it happens */
catch (Exception $e) {
    print 'Exception: ' . $ex->faultcode . ' - ' . $ex->getMessage() . "\n";
}

/**
 * set SoapClient Mode to asynchronous mode.
 * This will allow opening as many as connections to the host and perform all
 * request at once so you don't need to wait for consecutive calls to performed after each other
 *
 * @default is false AND it get reset back to FALSE after each $client->run();
 *
 */
$client->setAsync(true);

/** @var array $requestIds */
$requestIds = [];

/** in the next for loop i will make 5 soap request */
for ($i = 0; $i < 5; $i++) {
    $requestIds[] = $client->getFullname($session, 'Mo', 'Meabed ' . $i);
}

/** SoapCall without sessionId that return exception to test the exception handling */
$requestIds[] = $client->getFullname('wrongParam', 'Dummy');

/**
 * This call will throw SoapFault and it will return
 * string as  ".. Function ("getUnkownMethod") is not a valid method for this service...."
 * So it will be easy to debug it.
 *
 * @note The call will not be executed when $client->run()
 */
$requestIds[] = $client->getUnkownMethod('wrongParam', 'Dummy');

/**
 * This call will throw SoapFault and it will return
 * string as  ".. Function ("getAnotherUnkownMethod") is not a valid method for this service...."
 * So it will be easy to debug it.
 *
 * @note The call will not be executed when $client->run()
 */
$requestIds[] = $client->getAnotherUnkownMethod(['dummy' => 'test']);

/**
 * This call is valid method but it has wrong parameters so it will return normal request id but in the execution
 * it will return result instance of SoapFault contains the exception
 * So you can handle it
 */
$requestIds[] = $client->getFullname('wrongParams', 'Xyz');


/**
 * Adding another 5 SoapCalls to test different method call
 * in the next for loop i will make 5 soap request
 */
for ($i = 0; $i < 5; $i++) {
    /** @var $params , method parameters will be used in the test */
    $requestIds[] = $client->sayHello($session, 'Name ' . $i);
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
         * @Important please check SoapClientAsync NOTES in @line 153 and @line 295 For auto implementation of the soap response pattern
         *
         */
        print 'Response is : ' . $response . "\n";
    }
}
