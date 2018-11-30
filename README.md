# PHP-LocationSniffer
Attempts to determine location data on a passed in string.  
Currently supports the following formats:

- `%city`
- `%stateName`
- `%countryName`
- `%countryAbbreviation`
- `%city%sep %stateName`
- `%city%sep %stateAbbreviation`
- `%city%sep %countryName`
- `%city%sep %countryAbbreviation`
- `%stateName%sep %city`
- `%countryName%sep %city`
- `%countryAbbreviation%sep %city`
- `%city%sep %stateName%sep $countryName`
- `%city%sep %stateName%sep $countryAbbreviation`
- `%city%sep %stateAbbreviation%sep $countryName`
- `%city%sep %stateAbbreviation%sep $countryAbbreviation`


### References
 - [https://gist.github.com/Miserlou/c5cd8364bf9b2420bb29](https://gist.github.com/Miserlou/c5cd8364bf9b2420bb29)
 - [https://simplemaps.com/data/ca-cities](https://simplemaps.com/data/ca-cities)
