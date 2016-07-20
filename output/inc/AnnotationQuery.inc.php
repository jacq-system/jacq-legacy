<?php

//------------------------------------------------------------------------------
// annoquery
// ---------
// description:
//   This library provides an easy way to fetch annotations from an AnnoSys
//   service.
// author: Felix Hilgerdenaar
// last modification: 2014-09-15
//------------------------------------------------------------------------------

// connection settings
$serviceUri = "https://annosys.bgbm.fu-berlin.de/AnnoSys";

class TripleID
{
	protected $institutionID;
	protected $sourceID;
	protected $objectID;
	
	public function __construct($inst, $src, $obj) {
		$this->institutionID = $inst;
		$this->sourceID = $src;
		$this->objectID = $obj;
	}
	
	public function getInst() {
		return $this->institutionID;
	}
	
	public function getSrc() {
		return $this->sourceID;
	}
	
	public function getObj() {
		return $this->objectID;
	}
	
	public function toString() {
		return urlencode($this->institutionID)
			. "/" . urlencode($this->sourceID)
			. "/" . urlencode($this->objectID);
	}
} // class TrippleID


class AnnotationQuery
{
	protected $serviceUri;
	
	protected function checkId($id) {
		if(!($id instanceof TripleID))
			throw new Exception("id has to be an instance of class TrippleID");
	}
	
	protected function buildViewUri($repositoryUri) {
		$uri = $this->serviceUri
		       . "/AnnoSys?repositoryURI=" . urlencode($repositoryUri);
		return $uri;
	}
	
	protected function addViewUrisToMetadata($metadata) {
		$new = array();
		foreach($metadata as $key => $value) {
			$value['viewURI'] = $this->buildViewUri($value['repositoryURI']);
			$new[$key] = $value;
		}
		return $new;
	}
	
	public function __construct($serviceUri) {
		$this->serviceUri = $serviceUri;
	}
	
	public function getAnnotationCount($tripeId) {
		$count = count($this->getAnnotationMetadata($tripeId));
		
		return $count;
	}
	
	// fetches metadata about annotations of unit with $tripleId
	// returns associative array
	public function getAnnotationMetadata($tripleId) {
		$this->checkId($tripleId);
		$annotations = false;
		$statusCode=0;
		$uri = $this->serviceUri."/services/records/"
		       . $tripleId->toString() . "/annotations";
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
                curl_setopt($curl, CURLOPT_URL, $uri);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // return body as string
		$response = curl_exec($curl);
		if(!curl_errno($curl)) {
			$statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
			curl_close($curl);
		}
		else
			throw new Exception("Connection failed: " . curl_error($curl));
		
		if($statusCode == 404)
			$annotations = array();
		else if($statusCode == 200) {
			$parsedResponseBody = json_decode($response, true);
			if(array_key_exists('hasAnnotation', $parsedResponseBody)
			   && $parsedResponseBody['hasAnnotation']) {
				if(!array_key_exists('annotations', $parsedResponseBody))
					throw new Exception("'annotations' field is missing in response from server");
				else
					$annotations = $parsedResponseBody['annotations'];
					$annotations = $this->addViewUrisToMetadata($annotations);
			}
			else
				$annotations = array();
		}
		else {
			// unknown response
			throw new Exception("unknown response (responseCode=".$response->responseCode.")");
		}
		
		return $annotations;
	}
	
	// builds URI to create a new annotation
	public function newAnnotationUri($providerURL, $tripleId) {
		$protocolURI = "http://www.biocase.org/schemas/protocol/1.3";
		$formatURI = "http://www.tdwg.org/schemas/abcd/2.06";
		$uri = $this->serviceUri
		       . "/AnnoSys?providerURL=" . urlencode($providerURL)
		       . "&protocolURI=" . urlencode($protocolURI)
		       . "&formatURI=" . urlencode($formatURI)
		       . "&institution=" . urlencode($tripleId->getInst())
		       . "&source=" . urlencode($tripleId->getSrc())
		       . "&unitID=" . urlencode($tripleId->getObj());
		return $uri;
	}
} // class AnnotationQuery

?>