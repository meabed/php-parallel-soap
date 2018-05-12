<p align="center">
  <h3 align="center">Asynchronous, Parallel, Multi-Curl PHP SoapClient </h3>
  <p align="center">
    <a href="https://travis-ci.org/Meabed/asynchronous-soap">
      <img src="https://img.shields.io/travis/Meabed/asynchronous-soap.svg?branch=master&style=flat-square" alt="Build Status">
    </a>
    <a href="LICENSE.md">
      <img src="https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square" alt="Software License">
    </a>
    <a class="badge-align" href="https://www.codacy.com/app/Meabed/asynchronous-soap">
      <img src="https://img.shields.io/codacy/grade/266923eec70e41418be8f981a5b4cefe.svg?style=flat-square"/>
    </a>        
    <a href="https://scrutinizer-ci.com/g/meabed/asynchronous-soap/?branch=master">
      <img src="https://img.shields.io/scrutinizer/g/meabed/asynchronous-soap/master.svg?style=flat-square" alt="Scrutinizer Code Quality">
    </a>
    <a href="https://codecov.io/gh/meabed/asynchronous-soap">
      <img src="https://img.shields.io/codecov/c/github/meabed/asynchronous-soap/master.svg?style=flat-square" alt="codecov">
    </a>
    <a href="https://packagist.org/packages/meabed/asynchronous-soap/">
      <img src="https://img.shields.io/packagist/dm/meabed/asynchronous-soap.svg?style=flat-square" alt="Packagist">
    </a>
    <a href="https://www.paypal.me/meabed">
      <img src="https://img.shields.io/badge/paypal-donate-179BD7.svg?style=flat-squares" alt="Donate">
    </a>
  </p>
</p>

Parallel Multi-Curl SoapClient that allow us to perform Parallel multiple requests to SoapServer with CURL.

Almost all the companies that provide services using SoapServer and alot of them provide asynchronous Soap Api but Usually it very poor and it lack the real-time response and its poor comparing to the synchronous Soap Api.

I personally have faced this issue many times dealing with 3rd party service providers specially in eCommerce field ( Email marketing, Data mining, etc.. most of other third party tools ) So This class is great help and great tool to use !!

This class will allow you to be able to use different asynchronous Soap Implementation! that work with Synchronous Soap Api!

See the [**Examples**](https://github.com/Meabed/asynchronous-soap/tree/master/example) to see how to use it.

Read the Comment in the Example files carefully they are all written to help you understand how the client works and what you can do with it and how to customize it to fit your purpose!

Example [**WSDL**](https://whispering-meadow-99755.herokuapp.com/wsdl.php)

### Features
- Client can work in Asynchronous (multi) and Synchronous (single) mode.
- Multiple calls using **curl_multi_exec**, Does not wait for soap consecutive calls ! This client will save a lot of time and resources doing multiple requests at same time!
- **SSL / Session Sharing.**
- __curl_info meta data in response object.
- Each Request has **hash id** which is unique to each request ( If you execute the same request 100 times more, it will have the same hash ) so no duplicate requests
- Very Easy to debug every single point during the request! also ability to use **CURL_VERBOSE** to debug the connections to the Soap Host
- Very easy exception handling in **async** mode

## Need Help ?

I'm Always glad ot help and assist. so if you have an idea that could make this project better

Submit git issue or contact me [www.meabed.com](http://meabed.com)


## Contributing

Anyone is welcome to [contribute](CONTRIBUTING.md), however, if you decide to get involved, please take a moment to review the guidelines:

* [Only one feature or change per pull request](CONTRIBUTING.md#only-one-feature-or-change-per-pull-request)
* [Write meaningful commit messages](CONTRIBUTING.md#write-meaningful-commit-messages)
* [Follow the existing coding standards](CONTRIBUTING.md#follow-the-existing-coding-standards)

## License

The code is available under the [MIT license](LICENSE.md).