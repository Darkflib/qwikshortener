qwikshortener
=============

very fast url shortening service...

*** NOTE: The design used the virtual memory features of a fairly old version of redis. I have depreciated this and will be releasing a riak based shortner soon to replace it. ***

WRT speed, on a 256meg Rackspace server, I was consistantly getting 200+ redirects/second with fastcgi php+nginx+redis

With Redis cluster+horizontal scaling, this should be easily able to hit a few thousand redirects a second.

API is detailed in the odt file, will need to be bootstrapped with a user in redis (read the src)

This was written for a client on a non-exclusive basis and has worked well for them within the restrictions laid down by them.
