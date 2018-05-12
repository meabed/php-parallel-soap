# Asynchronous|Parallel Multi-Curl Soap Client

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