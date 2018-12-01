# PHP-LocationSniffer
Attempts to determine location data on a passed in string.


### Important note(s):
- This library is insanely inefficient; it's a first go at something that is
effective, rather than optimized. Use at your own risk (read: possible memory or
cpu issues).


### Example:
``` php
require_once '/path/to/LocationSniffer.class.php';
$str = 'Toronto';
$response = LocationSniffer::sniff($str);
print_r($response);

$str = 'Florida - Miami';
$response = LocationSniffer::sniff($str);
print_r($response);

$str = 'Lebanon / Beirut';
$response = LocationSniffer::sniff($str);
print_r($response);
```

Response:  
``` php
Array
(
    [str] => Toronto
    [matches] => Array
        (
            [0] => Array
                (
                    [pattern] => %cityName
                    [output] => Toronto, Canada
                    [properties] => Array
                        (
                            [cityName] => Toronto
                            [cityNameLatin] => Toronto
                            [lat] => 43.7
                            [lng] => -79.42
                            [countryName] => Canada
                            [countryAbbr2] => CA
                            [countryAbbr3] => CAN
                            [stateName] => Ontario
                            [stateAbbr] => 
                            [countryCapital] => 
                            [stateCapital] => 1
                            [otherCapital] => 
                            [population] => 5213000
                            [sep] => 
                        )

                )

        )

)
```
``` php
Array
(
    [str] => Florida - Miami
    [matches] => Array
        (
            [0] => Array
                (
                    [pattern] => %stateName%sep %cityName
                    [output] => Miami, Florida
                    [properties] => Array
                        (
                            [cityName] => Miami
                            [cityNameLatin] => Miami
                            [lat] => 25.784
                            [lng] => -80.2102
                            [countryName] => United States
                            [countryAbbr2] => US
                            [countryAbbr3] => USA
                            [stateName] => Florida
                            [stateAbbr] => FL
                            [countryCapital] => 
                            [stateCapital] => 
                            [otherCapital] => 
                            [population] => 6247425
                            [sep] => -
                        )

                )

        )

)
```
``` php
Array
(
    [str] => Lebanon / Beirut
    [matches] => Array
        (
            [0] => Array
                (
                    [pattern] => %countryName%sep %cityName
                    [output] => Beirut, Lebanon
                    [properties] => Array
                        (
                            [cityName] => Beirut
                            [cityNameLatin] => Beirut
                            [lat] => 33.872
                            [lng] => 35.5097
                            [countryName] => Lebanon
                            [countryAbbr2] => LB
                            [countryAbbr3] => LBN
                            [stateName] => Beyrouth
                            [stateAbbr] => 
                            [countryCapital] => 1
                            [stateCapital] => 
                            [otherCapital] => 
                            [population] => 1846000
                            [sep] => /
                        )

                )

        )

)
```


### Formats supported:
- `%cityName`
- `%cityName%sep %stateName`
- `%cityName%sep %stateAbbr`
- `%cityName%sep %countryName`
- `%cityName%sep %countryAbbr2`
- `%stateName%sep %cityName`
- `%countryName%sep %cityName`
- `%countryAbbr2%sep %cityName`
- `%cityName%sep %stateName%sep %countryName`
- `%cityName%sep %stateName%sep %countryAbbr2`
- `%cityName%sep %stateAbbr%sep %countryName`
- `%cityName%sep %stateAbbr%sep %countryAbbr`
- `%countryAbbr2`
- `%countryAbbr3`
- `%countryNam`
- `%countryAbbr2%sep %stateAbbr `
- `%countryAbbr2%sep %stateName `
- `%countryAbbr3%sep %stateAbbr `
- `%countryAbbr3%sep %stateName `
- `%countryName%sep %stateAbbr `
- `%countryName%sep %stateName `
- `%stateAbbr`
- `%stateAbbr%sep %countryAbbr2`
- `%stateAbbr%sep %countryAbbr3`
- `%stateAbbr%sep %countryName`
- `%stateName`
- `%stateName%sep %countryAbbr2`
- `%stateName%sep %countryAbbr3`
- `%stateName%sep %countryName`


### References
 - [https://gist.github.com/Miserlou/c5cd8364bf9b2420bb29](https://gist.github.com/Miserlou/c5cd8364bf9b2420bb29)
 - [https://simplemaps.com/data/ca-cities](https://simplemaps.com/data/ca-cities)
