<?php
require_once __DIR__ . '/../vendor/autoload.php';
function post_curl($url, $post_data)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    $output = curl_exec($ch);
    curl_close($ch);
    return $output;
}

//
//if (false) {
//    $dataCurl = '';
//    echo post_curl('http://whispering-meadow-99755.herokuapp.com/server.php', $dataCurl);
//}
//
//

$data = [
    'parameters' => ['Arg1' => 4, 'Arg2' => 3],
];

$soapClient = new SoapClient('http://www.crcind.com/csp/samples/SOAP.Demo.cls?WSDL=1', ['trace' => 1]);

try {
    $rs = $soapClient->__soapCall('AddInteger', $data);
    var_dump($soapClient->__getLastRequestHeaders());
    var_dump($soapClient->__getLastRequest());
    var_dump($soapClient->__getLastResponse());
    var_dump($soapClient->__getLastResponseHeaders());
} catch (\Exception $e) {
    var_dump($soapClient->__getLastRequest());
    var_dump($soapClient->__getLastResponse());
    var_dump($soapClient);
}


//if (!is_object($resultObj)) {
//    $resultObj = new \stdClass();
//}
//if (!empty($this->soapInfo[$id])) {
//    $resultObj->__curl_info = $this->soapInfo[$id];
//}
//
//if (!empty($this->__last_request)) {
//    $resultObj->__last_request = $this->requestXmlArr[$id];
//    $resultObj->__last_request_gmt_date = gmdate('U');
//}
//if (!empty($this->__last_response)) {
//    $clean_xml = str_ireplace(['SOAP-ENV:', 'SOAP:', 'awss:'], '', $this->__last_response);
//    $xmlObject = simplexml_load_string($clean_xml);
//    $resultObj->__last_response_object = $xmlObject;
//    $resultObj->__last_response = $this->__last_response;
//}
