<?php

//------------------------------------------------------------------------------
// ImagePreview
// ---------
// description:
//   This library provides an easy way to fetch image URIs from botany.az
//   
// author: Felix Hilgerdenaar
// last modification: 2015-01-14
//------------------------------------------------------------------------------

namespace Jacq;

use Exception;

class ImageQuery
{
    // extracts URIs from HTML code like <a href="http://...">image|</a>
    // returns array with URIs which were found
    protected function extractObjectUrisFromHtml($html)
    {
        preg_match_all("/<a[^>]+href=\"([^\"]+)\"[^>]*>[^<]*image[^<]*<\/a>/i", $html, $matches, PREG_PATTERN_ORDER);

        return $matches[1];
    }

    // extracts image and preview URI parts from HTML website
    protected function extracImageUriPartsFromHtml($html)
    {
        preg_match_all("/<div class=\"item\">[^<]*<a[^>]+href=\"([^\"]+)\"[^>]*>[^<]*<img[^>]+src=\"([^\"]+)\"[^>]*\/>[^<]*<\/a>([^<]|\n|\r)*<\/div>/ims", $html, $matches, PREG_PATTERN_ORDER);
        $result = array();
        foreach ($matches[1] as $key => $value) {
            $imageset = array("image" => $matches[1][$key], "preview" => $matches[2][$key]);
            array_push($result, $imageset);
        }
        return $result;
    }

    protected function generateUrisFromParts($objectUri, $uriParts)
    {
        $result = array();
        $parsed = parse_url($objectUri);
        $parsed["path"] = "";
        $parsed["query"] = "";
        $parsed["fragment"] = "";
        $baseUri = $parsed["scheme"] . "://" . $parsed["host"];

        foreach ($uriParts as $value) {
            $imageset = array(
                "html" => $objectUri,
                "image" => $baseUri . $value["image"],
                "filename" => $value["image"],
                "thumb" => $value["preview"],
                "preview" => $baseUri . $value["preview"],
                "base" => $baseUri
            );
            array_push($result, $imageset);
        }

        return $result;

    }

    protected function fetch($uri)
    {
        $html = "";
        $statusCode = 0;
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $uri);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // return body as string
        $response = curl_exec($curl);
        if (!curl_errno($curl)) {
            $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);
        } else {
            throw new Exception("Connection failed: " . curl_error($curl));
        }

        if ($statusCode == 404) {
            $html = "";
        } else if ($statusCode == 200) {
            $html = $response;
        } else {
            // unknown response
            throw new Exception("unknown response (responseCode=" . $response->responseCode . ")");
        }

        return $html;
    }

    // fetches and extracts URIs
    // returns associative array
    public function fetchUris($html)
    {
        $imagesets = array();
        $uris = $this->extractObjectUrisFromHtml($html);
        foreach ($uris as $uri) {
            $html = $this->fetch($uri);
            $parts = $this->extracImageUriPartsFromHtml($html);
            $newImagesets = $this->generateUrisFromParts($uri, $parts);
            $imagesets = array_merge($imagesets, $newImagesets);
        }
        return $imagesets;
    }

}
