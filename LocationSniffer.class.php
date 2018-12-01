<?php

    /**
     * LocationSniffer
     * 
     * @abstract
     * @link    https://github.com/getstencil/PHP-LocationSniffer
     * @see     https://simplemaps.com/data/world-cities
     * @see     https://gist.github.com/Miserlou/c5cd8364bf9b2420bb29
     * @see     https://simplemaps.com/data/ca-cities
     * @see     https://docs.google.com/spreadsheets/d/11gbBoutkd9KPCqsD9YtlW_XR_Yds-ofbSwkh65bnnkM/edit?usp=sharing
     * @author  Oliver Nassar <oliver@getstencil.com>
     */
    abstract class LocationSniffer
    {
        /**
         * _aliases
         * 
         * @access  protected
         * @static
         * @var     array (default: array())
         */
        protected static $_aliases = array();

        /**
         * _cacheClosure
         * 
         * @access  protected
         * @static
         * @var     null|Closure (default: null)
         */
        protected static $_cacheClosure = null;

        /**
         * _cities
         * 
         * @access  protected
         * @static
         * @var     array (default: array())
         */
        protected static $_cities = array();

        /**
         * _columnNameMap
         * 
         * @access  protected
         * @static
         * @var     array
         */
        protected static $_columnNameMap = array(
            'City' => 'cityName',
            'City (ascii)' => 'cityNameLatin',
            'Lat' => 'lat',
            'Long' => 'lng',
            'Country' => 'countryName',
            'ISO2' => 'countryAbbr2',
            'ISO3' => 'countryAbbr3',
            'State' => 'stateName',
            'State Abbreviation' => 'stateAbbr',
            'Capital' => 'unused',
            'Primary Capital' => 'countryCapital',
            'Admin Capital' => 'stateCapital',
            'Minor Capital' => 'otherCapital',
            'Population' => 'population',
        );

        /**
         * _lowPopulationCityExceptions
         * 
         * @access  protected
         * @static
         * @var     array
         */
        protected static $_lowPopulationCityExceptions = array(
            'Menlo Park'
        );

        /**
         * _countries
         * 
         * @access  protected
         * @static
         * @var     array (default: array())
         */
        protected static $_countries = array();

        /**
         * _locationStrings
         * 
         * @access  protected
         * @static
         * @var     array
         */
        protected static $_locationStrings = array(
            'cities' => array(),
            'countries' => array(),
            'states' => array()
        );

        /**
         * _minPopulation
         * 
         * @access  protected
         * @static
         * @var     int (default: 40000)
         */
        protected static $_minPopulation = 40000;

        /**
         * _outputFormats
         * 
         * @access  protected
         * @static
         * @var     array
         */
        protected static $_outputFormats = array(
            'default' => array(
                'city' => '%cityName, %countryName',
                'country' => '%countryName',
                'state' => '%stateName, %countryName'
            ),
            'countries' => array(
                'us' => array(
                    'city' => '%cityName, %stateName',
                    'country' => '%countryName',
                    'state' => '%stateName'
                )
            )
        );

        /**
         * _patterns
         * 
         * @access  protected
         * @static
         * @var     array
         */
        protected static $_patterns = array(
            'cities' => array(
                '%cityName',

                '%cityName%sep %stateName',
                '%cityName%sep %stateAbbr',
                '%cityName%sep %countryName',
                '%cityName%sep %countryAbbr2',

                '%stateName%sep %cityName',
                '%countryName%sep %cityName',
                '%countryAbbr2%sep %cityName',

                '%cityName%sep %stateName%sep %countryName',
                '%cityName%sep %stateName%sep %countryAbbr2',
                '%cityName%sep %stateAbbr%sep %countryName',
                '%cityName%sep %stateAbbr%sep %countryAbbr2'
            ),
            'countries' => array(
                '%countryAbbr2',
                '%countryAbbr3',
                '%countryName'
            ),
            'states' => array(
                '%countryAbbr2%sep %stateAbbr ',
                '%countryAbbr2%sep %stateName ',
                '%countryAbbr3%sep %stateAbbr ',
                '%countryAbbr3%sep %stateName ',
                '%countryName%sep %stateAbbr ',
                '%countryName%sep %stateName ',
                '%stateAbbr',
                '%stateAbbr%sep %countryAbbr2',
                '%stateAbbr%sep %countryAbbr3',
                '%stateAbbr%sep %countryName',
                '%stateName',
                '%stateName%sep %countryAbbr2',
                '%stateName%sep %countryAbbr3',
                '%stateName%sep %countryName'
            )
        );

        /**
         * _separators
         * 
         * @access  protected
         * @static
         * @var     array
         */
        protected static $_separators = array(
            '',
            '-',
            ',',
            '/',
            ':'
        );

        /**
         * _states
         * 
         * @access  protected
         * @static
         * @var     array (default: array())
         */
        protected static $_states = array();

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
         * _convertAliases
         * 
         * @access  protected
         * @static
         * @param   string $str
         * @return  string
         */
        protected static function _convertAliases(string $str): string
        {
            self::_loadAliases();
            foreach (self::$_aliases as $alias => $value) {
                $pattern = '/\b' . ($alias) . '\b/i';
                $str = preg_replace($pattern, $value, $str);
            }
            return $str;
        }

        /**
         * _formatMatch
         * 
         * @access  protected
         * @static
         * @param   array $match
         * @return  array
         */
        protected static function _formatMatch(array $match): array
        {
            $properties = array();
            foreach ($match as $key => $value) {
                if (preg_match('/^%/', $key) === 1) {
                    $formatted = preg_replace('/^%/', '', $key);
                    $properties[$formatted] = $value;
                    unset($match[$key]);
                }
            }
            $match['properties'] = $properties;
            return $match;
        }

        /**
         * _getColumnMapKeys
         * 
         * @access  protected
         * @static
         * @param   array $keys
         * @return  array
         */
        protected static function _getColumnMapKeys(array $keys): array
        {
            foreach ($keys as $index => $key) {
                $keys[$index] = self::$_columnNameMap[$key];
            }
            return $keys;
        }

        /**
         * _getCSVArray
         * 
         * @access  protected
         * @static
         * @param   string $path
         * @return  array
         */
        protected static function _getCSVArray(string $path): array
        {
            $file = file($path);
            $data = [];
            foreach ($file as $line) {
                $data[] = str_getcsv($line);
            }
            return $data;
        }

        /**
         * _getOutputFormat
         * 
         * @access  protected
         * @static
         * @param   array $variables
         * @return  string
         */
        protected static function _getOutputFormat(array $variables): string
        {
            $outputFormats = self::$_outputFormats;
            $countryAbbr2 = strtolower($variables['%countryAbbr2']);
            $outputFormat = $outputFormats['default'];
            if (isset($outputFormats['countries'][$countryAbbr2]) === true) {
                $outputFormat = $outputFormats['countries'][$countryAbbr2];
            }
            if (isset($variables['%cityName']) === true) {
                return $outputFormat['city'];
            }
            if (isset($variables['%stateName']) === true) {
                return $outputFormat['state'];
            }
            return $outputFormat['country'];
        }

        /**
         * _getResponse
         * 
         * @access  protected
         * @static
         * @param   string $str
         * @param   array $match
         * @return  array
         */
        protected static function _getResponse(string $str, array $match): array
        {
            $match = self::_formatMatch($match);
            $response = array(
                'str' => $str,
                'matches' => array($match)
            );
            return $response;
        }

        /**
         * _includePatterns
         * 
         * @access  protected
         * @static
         * @param   array $args
         * @param   string $type
         * @return  void
         */
        protected static function _includePatterns(array $args, string $type): void
        {
            $patterns = self::$_patterns[$type];
            $variables = array();
            foreach ($args as $key => $value) {
                $variables['%' . ($key)] = $value;
            }
            $separators = self::$_separators;
            foreach ($patterns as $pattern) {
                foreach ($separators as $separator) {
                    if (strpos($pattern, '%stateAbbr') !== false) {
                        if (isset($variables['%stateAbbr']) === false) {
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
                    if (isset(self::$_locationStrings[$type][$value]) === true) {
                        continue;
                    }
                    $variables['pattern'] = $pattern;
                    $variables['output'] = str_replace(
                        array_keys($variables),
                        array_values($variables),
                        self::_getOutputFormat($variables)
                    );
                    self::$_locationStrings[$type][$value] = $variables;
                    $sanized = self::_sanitize($value);
                    self::$_locationStrings[$type][$sanized] = $variables;
                }
            }
        }

        /**
         * _loadAliases
         * 
         * @access  protected
         * @static
         * @return  bool
         */
        protected static function _loadAliases(): bool
        {
            if (count(self::$_aliases) > 0) {
                return false;
            }
            $path = (__DIR__) . '/aliases.json';
            $content = file_get_contents($path);
            $aliases = json_decode($content, true);
            self::$_aliases = $aliases;
            return true;
        }

        /**
         * _loadCities
         * 
         * @see     https://stackoverflow.com/a/19454643/115025
         * @access  protected
         * @static
         * @return  bool
         */
        protected static function _loadCities(): bool
        {
            if (count(self::$_cities) > 0) {
                return false;
            }
            $path = (__DIR__) . '/cities.csv';
            $data = self::_getCSVArray($path);
            $keys = array_shift($data);
            $keys = self::_getColumnMapKeys($keys);
            foreach ($data as $index => $record) {
                $entry = array_combine($keys, $record);
                unset($entry['unused']);

                // Cleanup
                $entry['stateAbbr'] = empty($entry['stateAbbr']) ? null : $entry['stateAbbr'];
                $entry['countryCapital'] = filter_var($entry['countryCapital'], FILTER_VALIDATE_BOOLEAN);
                $entry['stateCapital'] = filter_var($entry['stateCapital'], FILTER_VALIDATE_BOOLEAN);
                $entry['otherCapital'] = filter_var($entry['otherCapital'], FILTER_VALIDATE_BOOLEAN);
                $entry['population'] = empty($entry['population']) ? null : (int) $entry['population'];

                // Primary or admin capital (eg. Ottawa or Toronto)
                if ($entry['countryCapital'] === true) {
                    array_push(self::$_cities, $entry);
                    continue;
                }
                if ($entry['stateCapital'] === true) {
                    array_push(self::$_cities, $entry);
                    continue;
                }

                // No population detected
                if ($entry['population'] === null) {
                    continue;
                }

                // Min Population requirement met
                if ($entry['population'] >= self::$_minPopulation) {
                    array_push(self::$_cities, $entry);
                }

                // Min popluation exception
                if (in_array($entry['cityName'], self::$_lowPopulationCityExceptions) === true) {
                    array_push(self::$_cities, $entry);
                }
            }

            // Sort by population
            usort(self::$_cities, function ($item1, $item2) {
                return $item2['population'] <=> $item1['population'];
            });

            // Done
            return true;
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
            if (count(self::$_countries) > 0) {
                return false;
            }
            $cities = self::$_cities;
            foreach ($cities as $city) {
                $entry = array(
                    'countryName' => $city['countryName'],
                    'countryAbbr2' => $city['countryAbbr2'],
                    'countryAbbr3' => $city['countryAbbr3']
                );
                if (in_array($entry, self::$_countries) === true) {
                    continue;
                }
                array_push(self::$_countries, $entry);
            }
            return true;
        }

        /**
         * _loadCityLocationStrings
         * 
         * @access  protected
         * @static
         * @return  bool
         */
        protected static function _loadCityLocationStrings(): bool
        {
            if (count(self::$_locationStrings['cities']) > 0) {
                return false;
            }
            $cities = self::$_cities;
            foreach ($cities as $city) {
                self::_includePatterns($city, 'cities');
            }
            return true;
        }

        /**
         * _loadCountryLocationStrings
         * 
         * @access  protected
         * @static
         * @return  bool
         */
        protected static function _loadCountryLocationStrings(): bool
        {
            if (count(self::$_locationStrings['countries']) > 0) {
                return false;
            }
            $countries = self::$_countries;
            foreach ($countries as $country) {
                self::_includePatterns($country, 'countries');
            }
            return true;
        }

        /**
         * _loadStateLocationStrings
         * 
         * @access  protected
         * @static
         * @return  bool
         */
        protected static function _loadStateLocationStrings(): bool
        {
            if (count(self::$_locationStrings['states']) > 0) {
                return false;
            }
            $states = self::$_states;
            foreach ($states as $state) {
                self::_includePatterns($state, 'states');
            }
            return true;
        }

        /**
         * _loadStates
         * 
         * @access  protected
         * @static
         * @return  bool
         */
        protected static function _loadStates(): bool
        {
            if (count(self::$_states) > 0) {
                return false;
            }
            $cities = self::$_cities;
            foreach ($cities as $city) {
                $entry = array(
                    'countryName' => $city['countryName'],
                    'countryAbbr2' => $city['countryAbbr2'],
                    'countryAbbr3' => $city['countryAbbr3'],
                    'stateName' => $city['stateName'],
                    'stateAbbr' => $city['stateAbbr']
                );
                if (in_array($entry, self::$_states) === true) {
                    continue;
                }
                array_push(self::$_states, $entry);
            }
            return true;
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
            // Split by ampersand
            if (strstr($str, ' & ') !== false) {
                $pieces = explode(' & ', $str);
                $str = trim($pieces[0]);
            }

            // Lowercase to normalize comparisons
            $str = strtolower($str);

            // Cleanup possible spaces between separators
            $separators = self::$_separators;
            foreach ($separators as $separator) {
                $str = str_replace(
                    ' ' . ($separator) . ' ',
                    ($separator) . ' ',
                    $str
                );
            }

            // Replace any aliases found
            $str = self::_convertAliases($str);

            // Lowercase again because of alias swaps
            $str = strtolower($str);

            // Done
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
         * _sniff
         * 
         * @access  protected
         * @static
         * @param   string $str
         * @return  array
         */
        protected static function _sniff(string $str): array
        {
            // Clean input
            $cleaned = self::_clean($str);
            $normalized = self::_normalize($cleaned);

            // Load CSV data
            self::_loadCities();

            // Country check
            self::_loadCountries();
            self::_loadCountryLocationStrings();
            if (isset(self::$_locationStrings['countries'][$normalized]) === true) {
                $match = self::$_locationStrings['countries'][$normalized];
                $response = self::_getResponse($str, $match);
                return $response;
            }

            // State check
            self::_loadStates();
            self::_loadStateLocationStrings();
            if (isset(self::$_locationStrings['states'][$normalized]) === true) {
                $match = self::$_locationStrings['states'][$normalized];
                $response = self::_getResponse($str, $match);
                return $response;
            }

            // City check
            self::_loadCityLocationStrings();
            if (isset(self::$_locationStrings['cities'][$normalized]) === true) {
                $match = self::$_locationStrings['cities'][$normalized];
                $response = self::_getResponse($str, $match);
                return $response;
            }

            // Bail
            $response = array(
                'str' => $str,
                'matches' => array()
            );
            return $response;
        }

        /**
         * setCacheClosure
         * 
         * @access  public
         * @static
         * @param   Closure $closure
         * @return  void
         */
        public static function setCacheClosure(Closure $closure): void
        {
            self::$_cacheClosure = $closure;
        }

        /**
         * sniff
         * 
         * @access  public
         * @static
         * @param   string $str
         * @return  array
         */
        public static function sniff(string $str): array
        {
            // No cache closure defined
            if (self::$_cacheClosure === null) {
                $response = self::_sniff($str);
                return $response;
            }

            // Cache closure defined
            $callback = self::$_cacheClosure;
            $args = array($str);
            $response = call_user_func_array($callback, $args);
            if ($response === null) {
                $response = self::_sniff($str);
                $args = array($str, $response);
                call_user_func_array($callback, $args);
            }
            return $response;
        }

        /**
         * test
         * 
         * @access  public
         * @static
         * @param   bool $showSuccessful (default: true)
         * @return  void
         */
        public static function test(bool $showSuccessful = true): void
        {
            // Load test location strings
            $path = (__DIR__) . '/tests.json';
            $content = file_get_contents($path);
            $decoded = json_decode($content, true);
            $strs = $decoded;

            // Keep track of counts
            $total = count($strs);
            $failed = 0;
            $successful = 0;

            // Keep track of attempt responses
            $attempts = array();
            foreach ($strs as $str) {
                $attempt = self::sniff($str);
                if (empty($attempt['matches']) === true) {
                    ++$failed;
                } else {
                    ++$successful;
                }
                array_push($attempts, $attempt);
            }

            // Response filtering
            if ($showSuccessful === true) {
                $attempts = array_filter($attempts, function($attempt) {
                    if (empty($attempt['matches']) === true) {
                        return false;
                    }
                    return true;
                });
                $msg = ($successful) . ' successful attempt(s) of ' . count($strs);
            } else {
                $attempts = array_filter($attempts, function($attempt) {
                    if (empty($attempt['matches']) === true) {
                        return true;
                    }
                    return false;
                });
                $msg = ($failed) . ' failed attempt(s) of ' . count($strs);
            }

            // Output
            echo '<h1>' . ($msg). '</h1>';
            echo '<pre>';
            print_r($attempts);
            echo '</pre>';
        }
    }
