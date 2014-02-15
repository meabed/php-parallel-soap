# Asynchronous Soap Client

The purpose of this project is to have an Asynchronous SoapClient that allow us to perform multiple requests to SoapServer.

Almost all the companies that provide services using SoapServer and alot of them provide asynchronous Soap Api but Usually it very poor and it lack the real-time response and its poor comparing to the synchronous Soap Api.

I personally have faced this issue many times dealing with 3rd party service providers specially in eCommerce field ( Email marketing, Data mining, etc.. most of other third party tools ) So This class is great help and great tool to use !!

This class will allow you to be able to use different asynchronous Soap Implementation! that work with Synchronous Soap Api !

See the [**Examples**](https://github.com/Meabed/asynchronous-soap/tree/master/example) to see how to use it.

Example [**WSDL**](http://meabed.net/soap/test.php?WSDL&readable)

### Features
- Client can work in Asynchronous (multi) and Synchronous (single) mode.
- Multiple calls using curl_multi_exec, Doesn't wait for soap consecutive calls ! This client will save alot of time and resources doing multiple requests at same time !
- Each Request has **hash id** which is unique to each request ( If you execute the same request 100 times more, it will have the same hash ) so no duplicate requests
- Very Easy to debug every single point during the request! also ability to use CURL_VERBOSE to debug the connections to the Soap Host
- Very easy exception handling in **async** mode

## Need Help ?

I'm Always glad ot help and assist. so if you have an idea that could make this project better

Submit git issue or contact me [www.meabed.net](http://meabed.net)


### How to contribute

Make a fork, commit to develop branch and make a pull request

Licence
-------
[GNU General Public License, version 3 (GPLv3)](http://opensource.org/licenses/gpl-3.0)
