<?php

class bdPhotos_ControllerHelper_Set extends XenForo_ControllerHelper_Abstract
{
    const DATA_CONDITIONS = 'c';
    const DATA_FETCH_OPTIONS = 'f';

    public static $base64Unsafe = array(
        '+',
        '/',
        '='
    );
    public static $base64Safe = array(
        '-',
        '_',
        '='
    );

    public function getViewParamsForPhotoList(array $conditions, array $fetchOptions)
    {
        $setData = array(
            self::DATA_CONDITIONS => $conditions,
            self::DATA_FETCH_OPTIONS => $fetchOptions,
        );

        $setString = $this->_buildSetStringFromData($setData);

        return array(
            'setData' => $setData,
            'setString' => $setString,
        );
    }

    public function getViewParamsForPhotoView(array $album, array $photo)
    {
        $setTitle = $this->_controller->getInput()->filterSingle('setTitle', XenForo_Input::STRING);
        $setString = $this->_controller->getInput()->filterSingle('setString', XenForo_Input::STRING);
        $setData = $this->_buildSetDataFromString($setString);

        $viewParams = array();

        if (empty($setData) OR empty($setData[self::DATA_CONDITIONS])) {
            // no set specified, use photos from the same album

            // intentionally not set the title
            // $setTitle = $album['album_name'];

            $setData = array(
                self::DATA_CONDITIONS => array('album_id' => $album['album_id']),
                self::DATA_FETCH_OPTIONS => array('order' => 'position'),
            );
            $setString = bdPhotos_Helper_Crypt::encrypt($setData);
        }

        $photos = $this->_getPhotoModel()->getPhotos($setData[self::DATA_CONDITIONS], $setData[self::DATA_FETCH_OPTIONS] ? $setData[self::DATA_FETCH_OPTIONS] : array());

        if (!empty($photos)) {
            $prev = false;
            $next = false;
            $found = false;

            if (count($photos) > 1) {
                foreach ($photos as $_photo) {
                    if ($_photo['photo_id'] == $photo['photo_id']) {
                        $found = true;
                        continue;
                    }

                    if (!$found) {
                        $prev = $_photo;
                    } else {
                        $next = $_photo;
                        break;
                    }
                }

                if ($found) {
                    if ($prev === false) {
                        // the photo is the first one in the set
                        // use the last one as $prev
                        $keys = array_keys($photos);
                        $lastKey = array_pop($keys);
                        $prev = $photos[$lastKey];
                    } elseif ($next === false) {
                        // the photo is the last one in the set
                        // use the first one as $next
                        $keys = array_keys($photos);
                        $firstKey = array_shift($keys);
                        $next = $photos[$firstKey];
                    }

                    if (!empty($prev) AND !empty($next) AND $prev['photo_id'] == $next['photo_id']) {
                        // this happens if there are 2 photos in the set
                        $prev = false;
                    }
                }
            }

            $viewParams = array(
                'setTitle' => $setTitle,
                'setData' => $setData,
                'setString' => $setString,

                'setPhotos' => $photos,
                'setPrev' => $prev,
                'setNext' => $next,
            );
        }

        return $viewParams;
    }

    protected function _buildSetStringFromData(array $data)
    {
        $filteredData = array();

        foreach ($data as $key => $value) {
            switch ($key) {
                case self::DATA_CONDITIONS:
                    $filteredData[$key] = array();

                    foreach ($value as $conditionKey => $conditionValue) {
                        switch ($conditionKey) {
                            case 'photo_is_published':
                                $filteredData[$key]['p'] = $conditionValue;
                                break;
                            case 'user_id':
                                $filteredData[$key]['u'] = $conditionValue;
                                break;
                            default:
                                $filteredData[$key] = $conditionValue;
                        }
                    }
                    break;
                case self::DATA_FETCH_OPTIONS:
                    $filteredData[$key] = array();

                    foreach ($value as $fetchOptionKey => $fetchOptionValue) {
                        switch ($fetchOptionKey) {
                            case 'join':
                                $filteredData[$key]['j'] = $fetchOptionValue;
                                break;
                            case 'order':
                                switch ($fetchOptionValue) {
                                    case 'position':
                                        $fetchOptionValue = 'p';
                                        break;
                                    case 'publish_date':
                                        $fetchOptionValue = 'pd';
                                        break;
                                }

                                $filteredData[$key]['o'] = $fetchOptionValue;
                                break;
                            case 'direction':
                                if (strtolower($fetchOptionValue) === 'desc') {
                                    $filteredData[$key]['d'] = 'd';
                                }
                                break;
                            default:
                                // ignore
                        }
                    }
                    break;
            }
        }

        $filteredData = array_filter($filteredData);

        $string = bdPhotos_Helper_Crypt::encrypt($filteredData);

        $safeString = str_replace(self::$base64Unsafe, self::$base64Safe, $string);

        return $safeString;
    }

    protected function _buildSetDataFromString($safeString)
    {
        $string = str_replace(self::$base64Safe, self::$base64Unsafe, $safeString);

        $filteredData = bdPhotos_Helper_Crypt::decrypt($string);
        $data = array();

        if (empty($filteredData)) {
            return $data;
        }

        foreach ($filteredData as $key => $value) {
            switch ($key) {
                case self::DATA_CONDITIONS:
                    $data[$key] = array();

                    foreach ($value as $conditionKey => $conditionValue) {
                        switch ($conditionKey) {
                            case 'p':
                                $data[$key]['photo_is_published'] = $conditionValue;
                                break;
                            case 'u':
                                $data[$key]['user_id'] = $conditionValue;
                                break;
                            default:
                                $data[$key] = $conditionValue;
                        }
                    }
                    break;
                case self::DATA_FETCH_OPTIONS:
                    $data[$key] = array();

                    foreach ($value as $fetchOptionKey => $fetchOptionValue) {
                        switch ($fetchOptionKey) {
                            case 'j':
                                $data[$key]['join'] = $fetchOptionValue;
                                break;
                            case 'o':
                                switch ($fetchOptionValue) {
                                    case 'p':
                                        $fetchOptionValue = 'position';
                                        break;
                                    case 'pd':
                                        $fetchOptionValue = 'publish_date';
                                        break;
                                }

                                $data[$key]['order'] = $fetchOptionValue;
                                break;
                            case 'd':
                                if (strtolower($fetchOptionValue) === 'd') {
                                    $data[$key]['direction'] = 'desc';
                                }
                                break;
                            default:
                                // ignore
                        }
                    }
                    break;
            }
        }

        return $data;
    }

    /**
     * @return bdPhotos_Model_Photo
     */
    protected function _getPhotoModel()
    {
        return $this->_controller->getModelFromCache('bdPhotos_Model_Photo');
    }

}
