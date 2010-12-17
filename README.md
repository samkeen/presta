# Presta documentation

Presta is a simple library that applies a thin abstraction around the PHP Curl extension.  This abstraction allows you to make http requests (GET, POST, PUT, DELETE) easily and makes the code that is sending those requests very readable.

## Usage Examples

Available chained methods for the Presta class
They would typically be used in the order listed below.

* uri
* headers (optional)
    * @param optional array for formatted string of header(s)
* response_type (optional)
    * This is a hint, if excluded, we'll try to sniff the response type
    * @param optional string [json|html|xml|jsonp]
* one of
    * get
    * post
        * @param required array or string of entity body
    * put
        * @param required array or string of entity body
    * delete
    * head

### post (showing all method)

    $response = Presta->instance(array(CURLOPT_SSL_VERIFYHOST => 0))
     ->uri('http://example.com/customers')
     ->headers(
         array(
             'x-auth-myauth' => 'hcyek8rnflay'
         )
     )
     ->response_type('json')
     ->post(
         array(
             'name' => 'bob'
         )
     )

### post (simple example, only needing required methods)

    $response = Presta->instance(array())
     ->uri('http://example.com/customers')
     ->post(
         array(
             'name' => 'bob'
         )
     )

### short get 

(could of course be file_get_contents instead, but uniform API is good)
    $response = Presta->instance()->uri('http://example.com/customers')->get()

### delete

    $response = Presta->instance()
     ->uri('http://example.com/customers/1')
     ->delete()

### put

    $response = Presta->instance(array())
     ->uri('http://example.com/customers/1')
     ->response_type('json')
     ->put(
         array(
             'name' => 'bob'
         )
     )

## requirements


## change log

### v0.1.0 : implemented support for PUT
### v0.0.3 : minor bug fixes and docs
### v0.0.2 : added ->auth() to presta object
### v0.0.1 : initial usable version
