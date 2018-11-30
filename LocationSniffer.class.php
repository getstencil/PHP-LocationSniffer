<?php

    /**
     * LocationSniffer
     * 
     * @abstract
     * @link    https://github.com/getstencil/PHP-LocationSniffer
     * @see     https://gist.github.com/Miserlou/c5cd8364bf9b2420bb29
     * @see     https://simplemaps.com/data/ca-cities
     * @author  Oliver Nassar <oliver@getstencil.com>
     */
    abstract class LocationSniffer
    {
        /**
         * _countryAbbreviations
         * 
         * @access  protected
         * @static
         * @var     array
         */
        protected static $_countryAbbreviations = array(
            'us',
            'ca',

            'ar',
            'at',
            'au',
            'be',
            'br',
            // 'ca',
            'ch',
            'cn',
            'co',
            'de',
            'dk',
            'es',
            'fr',
            'gb',
            'id',
            'ie',
            'il',
            'in',
            'jp',
            'kr',
            'mx',
            'nl',
            'nz',
            'pl',
            'pt',
            'ru',
            'sa',
            'se',
            'tr',
            // 'us',
            'za'
        );

        /**
         * _locationStrings
         * 
         * @access  protected
         * @static
         * @var     array (default: array())
         */
        protected static $_locationStrings = array();

        /**
         * _countryData
         * 
         * @access  protected
         * @static
         * @var     array (default: array())
         */
        protected static $_countryData = array();

        /**
         * _separators
         * 
         * @access  protected
         * @static
         * @var     array
         */
        protected static $_separators = array(
            '',
            ',',
            '/',
            ':'
        );

        /**
         * _patterns
         * 
         * @access  protected
         * @static
         * @var     array
         */
        protected static $_patterns = array(
            '%city',
            '%stateName',
            '%countryName',
            '%countryAbbreviation',

            '%city%sep %stateName',
            '%city%sep %countryName',
            '%city%sep %countryAbbreviation',

            '%stateName%sep %city',
            '%countryName%sep %city',
            '%countryAbbreviation%sep %city'
        );

        /**
         * _includePatterns
         * 
         * @access  protected
         * @static
         * @param   array $args
         * @return  void
         */
        protected static function _includePatterns(array $args): void
        {
            $separators = self::$_separators;
            $patterns = self::$_patterns;
            $variables = array();
            foreach ($args as $key => $value) {
                $variables['%' . ($key)] = $value;
            }
            foreach ($patterns as $pattern) {
                foreach ($separators as $separator) {
                    if (strpos($pattern, '%city') !== false) {
                        if (isset($variables['%city']) === false) {
                            continue;
                        }
                    }
                    if (strpos($pattern, '%stateName') !== false) {
                        if (isset($variables['%stateName']) === false) {
                            continue;
                        }
                    }
                    if (strpos($pattern, '%stateAbbreviation') !== false) {
                        if (isset($variables['%stateAbbreviation']) === false) {
                            continue;
                        }
                    }
                    $variables['%sep'] = $separator;
                    $value = str_replace(
                        array_keys($variables),
                        array_values($variables),
                        $pattern
                    );
                    $value = strtolower($value);
                    if (isset(self::$_locationStrings[$value]) === true) {
                        continue;
                    }
                    $variables['pattern'] = $pattern;
                    self::$_locationStrings[$value] = $variables;
                    self::$_locationStrings[self::_sanitize($value)] = $variables;
// prx(
//     self::_sanitize('São Paulo')
// );
                }
            }
        }

        /**
         * _loadLocationStrings
         * 
         * @access  protected
         * @static
         * @return  bool
         */
        protected static function _loadLocationStrings(): bool
        {
            if (count(self::$_locationStrings) > 0) {
                return false;
            }
            $countryData = self::$_countryData;
            foreach ($countryData as $index => $country) {
                self::_includePatterns(array(
                    'countryName' => $country['name'],
                    'countryAbbreviation' => $country['abbreviation']
                ));
                if (isset($country['alternateAbbreviations']) === true) {
                    foreach ($country['alternateAbbreviations'] as $abbreviation) {
                        self::_includePatterns(array(
                            'countryName' => $country['name'],
                            'countryAbbreviation' => $abbreviation
                        ));
                    }
                }
                $cities = $country['cities'];
                foreach ($cities as $city) {
                    self::_includePatterns(array(
                        'city' => $city,
                        'countryName' => $country['name'],
                        'countryAbbreviation' => $country['abbreviation']
                    ));
                    if (isset($country['alternateAbbreviations']) === true) {
                        foreach ($country['alternateAbbreviations'] as $abbreviation) {
                            self::_includePatterns(array(
                                'city' => $city,
                                'countryName' => $country['name'],
                                'countryAbbreviation' => $abbreviation
                            ));
                        }
                    }
                }
                // if ($index === 2) {
                //     break;
                // }
            }
            return true;
        }

        /**
         * _loadCountry
         * 
         * @access  protected
         * @static
         * @param   string $countryAbbreviation
         * @return  void
         */
        protected static function _loadCountry(string $countryAbbreviation): void
        {
            $path = (__DIR__) . '/countries/' . ($countryAbbreviation) . '.json';
            $content = file_get_contents($path);
            $decoded = json_decode($content, true);
            array_push(self::$_countryData, $decoded);
            if (isset($decoded['states']) === false) {
                $decoded['states'] = array();
            }
        }

        /**
         * _loadCountries
         * 
         * @access  protected
         * @static
         * @return  bool
         */
        protected static function _loadCountries(): bool
        {
            if (count(self::$_countryData) > 0) {
                return false;
            }
            foreach (self::$_countryAbbreviations as $countryAbbreviation) {
                self::_loadCountry($countryAbbreviation);
            }
            return true;
        }

        /**
         * _clean
         * 
         * Attempts to clean up the passed in string so that any superfluous
         * data is not included in the check.
         * 
         * @access  protected
         * @static
         * @param   string $str
         * @return  string
         */
        protected static function _clean(string $str): string
        {
            $str = trim($str);
            $str = preg_replace('!\s+!', ' ', $str);
            return $str;
        }

        /**
         * _sanitize
         * 
         * @see     https://stackoverflow.com/a/23782573/115025
         * @access  protected
         * @static
         * @param   string $str
         * @return  string
         */
        protected static function _sanitize(string $str): string
        {
            $str = str_replace(
                array('à', 'á', 'â', 'ä', 'æ', 'ã', 'å', 'ā', 'À', 'Á', 'Â', 'Ä', 'Æ', 'Ã', 'Å', 'Ā'),
                array('a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A'),
                $str
            );
            $str = str_replace(
                array('ç', 'ć', 'č', 'Ç', 'Ć', 'Č'),
                array('c', 'c', 'c', 'C', 'C', 'C'),
                $str
            );
            $str = str_replace(
                array('è', 'é', 'ê', 'ë', 'ē', 'ė', 'ę', 'È', 'É', 'Ê', 'Ë', 'Ē', 'Ė', 'Ę'),
                array('e', 'e', 'e', 'e', 'e', 'e', 'e', 'E', 'E', 'E', 'E', 'E', 'E', 'E'),
                $str
            );
            $str = str_replace(
                array('î', 'ï', 'í', 'ī', 'į', 'ì', 'Î', 'Ï', 'Í', 'Ī', 'Į', 'Ì'),
                array('i', 'i', 'i', 'i', 'i', 'i', 'I', 'I', 'I', 'I', 'I', 'I'),
                $str
            );
            $str = str_replace(
                array('ł', 'Ł'),
                array('l', 'L'),
                $str
            );
            $str = str_replace(
                array('ñ', 'ń', 'Ñ', 'Ń'),
                array('n', 'n', 'N', 'N'),
                $str
            );
            $str = str_replace(
                array('ô', 'ö', 'ò', 'ó', 'œ', 'ø', 'ō', 'õ', 'Ô', 'Ö', 'Ò', 'Ó', 'Œ', 'Ø', 'Ō', 'Õ'),
                array('o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O'),
                $str
            );
            $str = str_replace(
                array('ß', 'ś', 'š', 'Ś', 'Š'),
                array('ss', 's', 's', 'S', 'S'),
                $str
            );
            $str = str_replace(
                array('û', 'ü', 'ù', 'ú', 'ū', 'Û', 'Ü', 'Ù', 'Ú', 'Ū'),
                array('u', 'u', 'u', 'u', 'u', 'U', 'U', 'U', 'U', 'U'),
                $str
            );
            $str = str_replace(
                array('ÿ', 'Ÿ'),
                array('y', 'Y'),
                $str
            );
            $str = str_replace(
                array('ž', 'ź', 'ż', 'Ž', 'Ź', 'Ż'),
                array('z', 'z', 'z', 'Z', 'Z', 'Z'),
                $str
            );
            return strtolower($str);
        }

        /**
         * _normalize
         * 
         * Normalizes the passed in string so that it's more likely to be in the
         * same format as the location string values.
         * 
         * @access  protected
         * @static
         * @param   string $str
         * @return  string
         */
        protected static function _normalize(string $str): string
        {
            if (strstr($str, ' & ') !== false) {
                $pieces = explode(' & ', $str);
                $str = trim($pieces[0]);
            }
            $str = strtolower($str);
            $separators = self::$_separators;
            foreach ($separators as $separator) {
                $pattern = '/[s]+[' . ($separator) . ']{1}[s]+/';
                $str = str_replace(
                    ' ' . ($separator) . ' ',
                    ($separator) . ' ',
                    $str
                );
            }
            return $str;
        }

        /**
         * _sniff
         * 
         * @access  protected
         * @static
         * @param   string $str
         * @return  null|array
         */
        protected static function _sniff(string $str): ?array
        {
            $cleaned = self::_clean($str);
            $normalized = self::_normalize($cleaned);
// prx($normalized);
            self::_loadCountries();
            self::_loadLocationStrings();
            if (isset(self::$_locationStrings[$normalized]) === true) {
                $location = self::$_locationStrings[$normalized];
                return $location;
            }
            return null;
        }

        /**
         * sniff
         * 
         * @access  public
         * @static
         * @param   string $str
         * @return  null|array
         */
        public static function sniff(string $str): ?array
        {
            $response = self::_sniff($str);
            return $response;
        }

        /**
         * test
         * 
         * @access  public
         * @static
         * @return  void
         */
        public static function test(): void
        {
            $path = (__DIR__) . '/tests.json';
            $content = file_get_contents($path);
            $decoded = json_decode($content, true);
            $strs = $decoded;
            $total = count($strs);
            $failed = 0;
            $response = array();
            foreach ($strs as $str) {
                $attempt = self::sniff($str);
                if ($attempt === null) {
                    ++$failed;
                }
                array_push(
                    $response,
                    array(
                        'str' => $str,
                        'response' => $attempt
                    )
                );
            }
            $response = array_filter($response, function($var) {
                if ($var['response'] === null) {
                    return true;
                }
                return false;
            });
            prx($response);
        }
    }
