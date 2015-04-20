!function ($, window, document, _undefined) {
    XenForo.bdPhotos_GeoDecoder = function ($element) {
        this.__construct($element);
    };
    XenForo.bdPhotos_GeoDecoder.prototype =
    {
        __construct: function ($element) {
            var $input = $element.find('input[type=text]');

            if ($input.val().length == 0) {
                var lat = $element.data('lat'),
                    lng = $element.data('lng'),
                    _10e6 = Math.pow(10, 6),
                    fLat = lat / _10e6,
                    fLng = lng / _10e6,
                    latlng = new google.maps.LatLng(fLat, fLng),
                    geocoder = new google.maps.Geocoder();

                geocoder.geocode({'latLng': latlng}, function (results, status) {
                    console.log(results);
                    for (var i in results) {
                        var types = results[i]['types'];
                        var ignore = false;

                        for (var j in types) {
                            switch (types[j]) {
                                case 'street_address':
                                case 'neighborhood':
                                case 'sublocality':
                                    ignore = true;
                                    break;
                            }
                        }

                        if (!ignore) {
                            $input.val(results[i]['formatted_address']);
                            break;
                        }
                    }
                });
            }
        }
    };

    // *********************************************************************

    XenForo.bdPhotos_LocationInput = function($input) {
        $input.geocomplete({
            details: $input.data('details'),
            detailsAttribute: 'data-geo'
        });
    };

    // *********************************************************************

    XenForo.register('.bdPhotos_GeoDecoder', 'XenForo.bdPhotos_GeoDecoder');
    XenForo.register('.bdPhotos_LocationInput', 'XenForo.bdPhotos_LocationInput');

}
(jQuery, this, document);