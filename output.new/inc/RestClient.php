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
    $this->service = $service;
}

/**
 * Performs a GET operation for the given resource with the given arguments. Returns the received response.
 *
 * @param string $resource resource-part of the url
 * @param array $arguments mandatory arguments
 * @param array $optArguments optional arguments ("name" => "value")
 * @return mixed json-decoded answer of the service
 */
public function get($resource, $arguments, $optArguments = array())
{
    $service_url = $this->service
                 . trim($resource, '/') . ((trim($resource, '/')) ? '/' : '')
                 . implode('/', $arguments);
    if ($optArguments) {
        $parts = array();
        foreach ($optArguments as $key=>$val) {
            $parts[] = urlencode($key) . '=' . urlencode($val);
        }
        $service_url .= '?' . implode('&', $parts);
    }
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

private function post()
{
    return;  // not yet finished

    //next example will insert new conversation
    $service_url = 'https://example.com/api/conversations';
    $curl = curl_init($service_url);
    $curl_post_data = array(
            'message' => 'test message',
            'useridentifier' => 'agent@example.com',
            'department' => 'departmentId001',
            'subject' => 'My first conversation',
            'recipient' => 'recipient@example.com',
            'apikey' => 'key001'
    );
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $curl_post_data);
    $curl_response = curl_exec($curl);
    if ($curl_response === false) {
        $info = curl_getinfo($curl);
        curl_close($curl);
        die('error occured during curl exec. Additioanl info: ' . var_export($info));
    }
    curl_close($curl);
    $decoded = json_decode($curl_response);
    if (isset($decoded->response->status) && $decoded->response->status == 'ERROR') {
        die('error occured: ' . $decoded->response->errormessage);
    }
    echo 'response ok!';
    var_export($decoded->response);
}

private function put()
{
    return;  // not yet finished

    //next eample will change status of specific conversation to resolve
    $service_url = 'https://example.com/api/conversations/cid123/status';
    $ch = curl_init($service_url);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    $data = array("status" => 'R');
    curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($data));
    $response = curl_exec($ch);
    if ($response === false) {
        $info = curl_getinfo($ch);
        curl_close($ch);
        die('error occured during curl exec. Additioanl info: ' . var_export($info));
    }
    curl_close($ch);
    $decoded = json_decode($response);
    if (isset($decoded->response->status) && $decoded->response->status == 'ERROR') {
        die('error occured: ' . $decoded->response->errormessage);
    }
    echo 'response ok!';
    var_export($decoded->response);
}

private function delete()
{
    return;  // not yet finished

    $service_url = 'https://example.com/api/conversations/[CONVERSATION_ID]';
    $ch = curl_init($service_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
    $curl_post_data = array(
            'note' => 'this is spam!',
            'useridentifier' => 'agent@example.com',
            'apikey' => 'key001'
    );
    curl_setopt($curl, CURLOPT_POSTFIELDS, $curl_post_data);
    $response = curl_exec($ch);
    if ($curl_response === false) {
        $info = curl_getinfo($curl);
        curl_close($curl);
        die('error occured during curl exec. Additioanl info: ' . var_export($info));
    }
    curl_close($curl);
    $decoded = json_decode($curl_response);
    if (isset($decoded->response->status) && $decoded->response->status == 'ERROR') {
        die('error occured: ' . $decoded->response->errormessage);
    }
    echo 'response ok!';
    var_export($decoded->response);
}


}