# WordPress-Hello-Weather-Plugin
This is an extremely simple plugin. It was made so I could get a bit of a better understanding on how WordPress plugins work.
It's very similar to the famous [Hello Dolly Plugin](https://wordpress.org/plugins/hello-dolly/) that you see installed by 
default on nearly every WordPress site.

## What does it do?
Hello Weather makes to GET request using CURL. The first GET request is made to [IP Info](https://ipinfo.io/) using the 
IP contained inside of the clients browser, using HTTP Headers. It collects 4 pieces of information:
+ City
+ Region
+ Latitude
+ Longitude

Hello Weather collects these so it can make the next call.

The second GET request is made to the [OpenWeather API](https://openweathermap.org). Using the given Longitude and Latitude,
it returns the given weather conditions. (eg. Cloudy, Sunny, etc)

Finally after all that is retrieved, It places the string on the top right of the admin panel just like [Hello Dolly](https://wordpress.org/plugins/hello-dolly/) does.   
[ **Example:** _It looks like "broken clouds" in City, Region today._ ]

## What's Next?
Absolutely nothing. I don't see any other reason or feature to expand upon Hello Weather. It is nothing more than a simple 
Beginner WordPress Plugin Development project. 

If for whatever reason you wish to take and expand on this. Please feel free to. I look forward to seeing what you can come up with!

Thank you,  
KeanuAaron
