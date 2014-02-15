<?php
/**
 * The test soap Server is Located @ http://meabed.net/soap/test.php
 */

/** the main soap class */
require_once('../src/SoapClient.php');

/** @var string $wsdl, This is the test server i have generated to test the class */
$wsdl = "http://meabed.net/soap/test.php?WSDL";
/** @var array $options , array of options for the soap request */
$options = array(
    'connection_timeout' => 40,
    'trace'              => true
);

/** @var SoapClientAsync $client New Soap client instance */
$client = new SoapClientAsync($wsdl, $options);

/**
 * You can set debug mode to true to see curl verbose response if you run the script from command line
 * @default false
 */
$client::$_debug = true;

/** @var string $session , SessionId required in SoapOperations */
$session = null;
/** Normal ONE SOAP CALL Using CURL same exact as soap synchronous api calls of any web service */
try {
    $loginSoapCall = $client->login(array('demo', '123456'));
    $session = $loginSoapCall->Return;
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
 * @default is false AND it get reset back to FALSE after each $client->run();
 *
 */
$client::$_async = true;

/** @var array $requestIds */
$requestIds = array();

/** in the next for loop i will make 5 soap request */
for ($i = 0; $i < 5; $i++) {
    /** @var $params , method parameters will be used in the test */
    $params = array(
        'session'   => $session,
        'firstName' => 'Mohamed',
        /** Change the request Body */
        'lastName'  => 'Meabed ' . $i
    );
    $requestIds[] = $client->getFullname($params);
}

/** SoapCall without sessionId that return exception to test the exception handling */
$requestIds[] = $client->getFullname(array('wrongParam' => 'Dummy'));

/**
 * This call will throw SoapFault and it will return
 * string as  ".. Function ("getUnkownMethod") is not a valid method for this service...."
 * So it will be easy to debug it.
 *
 * @note The call will not be executed when $client->run()
 */
$requestIds[] = $client->getUnkownMethod(array('wrongParam' => 'Dummy'));

/**
 * This call will throw SoapFault and it will return
 * string as  ".. Function ("getAnotherUnkownMethod") is not a valid method for this service...."
 * So it will be easy to debug it.
 *
 * @note The call will not be executed when $client->run()
 */
$requestIds[] = $client->getAnotherUnkownMethod(array('dummy' => 'test'));


/**
 * Adding another 5 SoapCalls to test different method call
 * in the next for loop i will make 5 soap request
 */
for ($i = 0; $i < 5; $i++) {
    /** @var $params , method parameters will be used in the test */
    $params = array(
        'session' => $session,
        /** Change the request Body */
        'name'    => 'Mohamed ' . $i
    );
    $requestIds[] = $client->sayHello($params);
}

/** You can see the request ids in the variable that will be executed with $client->run() method */
print_r($requestIds);

/**
 * You can execute certain requests if you pass array of requestIds to $client->run() method as in the example in the comment
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
        print 'Response is : ' . $response->Return . "\n";

    }
}