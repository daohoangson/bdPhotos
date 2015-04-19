<?php

class bdPhotos_Helper_GoogleMapsApi
{
    public static function reverseDecoding($lat, $lng)
    {
        $_10e6 = pow(10, 6);

        // TODO: confirm whether we need API key for our usage? And logo placement
        $client = XenForo_Helper_Http::getClient(sprintf('http://maps.googleapis.com/maps/api/geocode/json?latlng=%f,%f&sensor=false', $lat / $_10e6, $lng / $_10e6));
        $response = $client->request('GET')->getBody();
        $responseArray = json_decode($response, true);

        XenForo_Helper_File::log('bdPhotos_googleMapsApi', call_user_func_array('sprintf', array(
            'reverseDecoding %d, %d -> %s (%d results)',
            $lat,
            $lng,
            (!empty($responseArray['status']) ? $responseArray['status'] : 'N/A'),
            (!empty($responseArray['results']) ? count($responseArray['results']) : 0),
        )));

        if (!empty($responseArray['status']) AND $responseArray['status'] == 'OK') {
            foreach ($responseArray['results'] as $result) {
                if (!empty($result['geometry']['bounds'])) {
                    return array(
                        'location_name' => $result['formatted_address'],
                        'ne_lat' => floor($result['geometry']['bounds']['northeast']['lat'] * $_10e6),
                        'ne_lng' => floor($result['geometry']['bounds']['northeast']['lng'] * $_10e6),
                        'sw_lat' => floor($result['geometry']['bounds']['southwest']['lat'] * $_10e6),
                        'sw_lng' => floor($result['geometry']['bounds']['southwest']['lng'] * $_10e6),
                        'location_info' => array_merge($result, array(
                            '_source' => 'maps.googleapis.com',
                            '_timestamp' => XenForo_Application::$time,
                            '_requestedLat' => $lat,
                            '_requestedLng' => $lng,
                        )),
                    );
                }
            }
        }

        return false;
    }

    public static function getStaticMapForBounds($apiKey, $width, $height, $neLat, $neLng, $swLat, $swLng)
    {
        $_10e6 = pow(10, 6);

        return call_user_func_array('sprintf', array(
            'http://maps.googleapis.com/maps/api/staticmap?sensor=false&key=%s&size=%dx%d&format=jpg&markers=%s&path=%s',
            $apiKey,
            $width,
            $height,
            urlencode(call_user_func_array('sprintf', array(
                'color:red|%f,%f',
                ($neLat + $swLat) / 2 / $_10e6,
                ($neLng + $swLng) / 2 / $_10e6,
            ))),
            urlencode(call_user_func_array('sprintf', array(
                'color:0xff00000000|weight:0|%f,%f|%f,%f',
                $neLat / $_10e6,
                $neLng / $_10e6,
                $swLat / $_10e6,
                $swLng / $_10e6,
            ))),
        ));
    }

}
