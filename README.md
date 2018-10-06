<p align="center">
  <h3 align="center"> Parallel, Multi-Curl PHP SoapClient </h3>
  <p align="center">
    <a href="https://travis-ci.org/meabed/php-parallel-soap">
      <img src="https://img.shields.io/travis/meabed/php-parallel-soap.svg?branch=master&style=flat-square" alt="Build Status">
    </a>
    <a href="LICENSE.md">
      <img src="https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square" alt="Software License">
    </a>
    <a class="badge-align" href="https://www.codacy.com/app/meabed/php-parallel-soap">
      <img src="https://img.shields.io/codacy/grade/266923eec70e41418be8f981a5b4cefe.svg?style=flat-square"/>
    </a>        
    <a href="https://scrutinizer-ci.com/g/meabed/php-parallel-soap/?branch=master">
      <img src="https://img.shields.io/scrutinizer/g/meabed/php-parallel-soap/master.svg?style=flat-square" alt="Scrutinizer Code Quality">
    </a>
    <a href="https://codecov.io/gh/meabed/php-parallel-soap">
      <img src="https://img.shields.io/codecov/c/github/meabed/php-parallel-soap/master.svg?style=flat-square" alt="codecov">
    </a>
    <a href="https://packagist.org/packages/meabed/php-parallel-soap/">
      <img src="https://img.shields.io/packagist/dm/meabed/php-parallel-soap.svg?style=flat-square" alt="Packagist">
    </a>
    <a href="https://www.paypal.me/meabed">
      <img src="https://img.shields.io/badge/paypal-donate-179BD7.svg?style=flat-squares" alt="Donate">
    </a>
    <a href="https://meabed.com">
      <img src="https://img.shields.io/badge/Author-blog-green.svg?style=flat-square" alt="Authoer Blog">
    </a>
  </p>
</p>

Parallel Multi-Curl SoapClient that allow us to perform Parallel multiple requests to SoapServer using CURL.

Working with soap is always frustrating for few reasons:
- SOAP Messages are complicated and obscure
- **Always slow Performance** as lack for connection pooling, ssl sharing, tcp tweaking options that comes with curl
- Sequential Execution in array of multiple requests there no other way except looping and synchronously send request after another 
- debugging with ability to understand how and what goes through the HTTP " Headers / Request Payload / Response Headers / Response Payload / Error structure etc..."  

This Client will allow you send request in parallel, while give you ability to hook in the clinet "Logger / Result Function / Customer curl options like tcp connections reusing and ssl session sharing"  

See the [**Examples**](https://github.com/Meabed/php-parallel-soap/tree/master/example) to see how to use it.

 Comment in the Example are written to help you understand how the client works and what you can do with it and how to customize it to fit your purpose!

Example [**WSDL**](https://whispering-meadow-99755.herokuapp.com/wsdl.php)

### Features
- Client can work in parallel (multi) and Synchronous (single) mode.
- Multiple calls using **curl_multi_exec**, Does not wait for soap consecutive calls ! This client will save a lot of time and resources doing multiple requests at same time!
- **SSL / Session Sharing.**
- __curl_info meta data in response object.
- Each Request has **hash id** which is unique to each request ( If you execute the same request 100 times more, it will have the same hash ) so no duplicate requests
- Very Easy to debug every single point during the request! also ability to use **CURL_VERBOSE** to debug the connections to the Soap Host
- Very easy exception handling in **parallel** mode

### SOAP Facts
- SOAP is HTTP Post with structured message in XML Envelope and SOAPAction Header.
- SOAPAction header is used in web services for various reason, most common
    - Route request to specific action
    - Serve Multi-Version of service, if Action Method is part of the XML Envelope
   

## Need Help?
If you ever hated SOAP for complexity or performance and you cannot take it anymore, I could help! drop me a line here [meabed.com](http://meabed.com)


## Contributing

Anyone is welcome to [contribute](CONTRIBUTING.md), however, if you decide to get involved, please take a moment to review the guidelines:

* [Only one feature or change per pull request](CONTRIBUTING.md#only-one-feature-or-change-per-pull-request)
* [Write meaningful commit messages](CONTRIBUTING.md#write-meaningful-commit-messages)
* [Follow the existing coding standards](CONTRIBUTING.md#follow-the-existing-coding-standards)

## License

The code is available under the [MIT license](LICENSE.md).
