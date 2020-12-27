<?php

//$rest = new RestClient("http://localhost/develop.jacq/services/rest/");
//var_dump($rest->get("classification/children", array("citation", 31070), array("taxonID" => 233647)));

class RestClient
{
/**
 *
 * @var string url of the service with the protocol and a trailing slash (e.g. http://localhost/service/)
 */
private $service;

/**
 *
 * @param string $service url of the service with the protocol and a trailing slash (e.g. http://localhost/service/)
 */
public function __construct($service)
{
    $this->service = trim($service);
    if (substr($this->service, -1) != "/") {
        $this->service .= '/';
    }
}

/**
 * Performs a GET operation for the given resource with the given arguments. Returns the received response.
 *
 * @param string $resource resource-part of the url
 * @param array $arguments mandatory arguments
 * @param array $optArguments optional arguments ("name" => "value")
 * @return mixed answer of the service
 */
public function get($resource, $arguments, $optArguments = array())
{
    $service_url = $this->service
                 . trim($resource, '/') . ((trim($resource, '/')) ? '/' : '')
                 . implode('/', $arguments)
                 . (($optArguments) ? '?' . http_build_query($optArguments) : '');
    $curl = curl_init($service_url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    $curl_response = curl_exec($curl);
    if ($curl_response === false) {
//        $info = curl_getinfo($curl);
        curl_close($curl);
        return null;
    }
    curl_close($curl);
    return $curl_response;
}

/**
 * Performs a GET operation for the given resource with the given arguments. Returns the response as a json-decoded array.
 *
 * @param string $resource resource-part of the url
 * @param array $arguments mandatory arguments
 * @param array $optArguments optional arguments ("name" => "value")
 * @return mixed json-decoded answer of the service
 */
public function jsonGet($resource, $arguments, $optArguments = array())
{
    $response = $this->get($resource, $arguments, $optArguments);
    $decoded = json_decode($response, true);
    return $decoded;
}

/**
 * Performs a POST operation for the given resource with the given arguments. Returns the received response.
 *
 * @param string $resource resource-part of the url
 * @param array $arguments arguments (if any)
 * @param array $data data to post ("name" => "value")
 * @return mixed answer of the service
 */
public function post($resource, $arguments, $data)
{
    $service_url = $this->service
             . trim($resource, '/') . ((trim($resource, '/')) ? '/' : '')
             . implode('/', $arguments);
    $curl = curl_init($service_url);
//    $curl_post_data = array(
//            'message' => 'test message',
//            'useridentifier' => 'agent@example.com',
//            'department' => 'departmentId001',
//            'subject' => 'My first conversation',
//            'recipient' => 'recipient@example.com',
//            'apikey' => 'key001'
//    );
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
    $curl_response = curl_exec($curl);
    if ($curl_response === false) {
//        $info = curl_getinfo($curl);
        curl_close($curl);
        return null;
    }
    curl_close($curl);
    return $curl_response;
}

/**
 * Performs a PUT operation for the given resource with the given arguments. Returns the received response.
 *
 * @param string $resource resource-part of the url
 * @param array $arguments arguments (if any)
 * @param array $data data to put ("name" => "value")
 * @return mixed answer of the service
 */
public function put($resource, $arguments, $data)
{
    $service_url = $this->service
             . trim($resource, '/') . ((trim($resource, '/')) ? '/' : '')
             . implode('/', $arguments);
    $curl = curl_init($service_url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
    $curl_response = curl_exec($curl);
    if ($curl_response === false) {
//        $info = curl_getinfo($curl);
        curl_close($curl);
        return null;
    }
    curl_close($curl);
    return $curl_response;
}

/**
 * Performs a POST operation for the given resource with the given arguments. Returns the received response.
 *
 * @param string $resource resource-part of the url
 * @param array $arguments arguments (if any)
 * @param array $data optional data to post ("name" => "value")
 * @return mixed answer of the service
 */
public function delete($resource, $arguments, $data = array())
{
    $service_url = $this->service
             . trim($resource, '/') . ((trim($resource, '/')) ? '/' : '')
             . implode('/', $arguments);
    $curl = curl_init($service_url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
//    $curl_post_data = array(
//            'note' => 'this is spam!',
//            'useridentifier' => 'agent@example.com',
//            'apikey' => 'key001'
//    );
    if (!empty($data)) {
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
    }
    $curl_response = curl_exec($curl);
    if ($curl_response === false) {
//        $info = curl_getinfo($curl);
        curl_close($curl);
        return null;
    }
    curl_close($curl);
    return $curl_response;
}


}