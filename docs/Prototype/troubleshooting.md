# Prototype builder - Troubleshooting

Check following tips if you have any problem with the prototype builder.

## The user interface is broken (CSS is not working)

Symptoms:
  * the user interface, including the authentication screen is broken

Possible causes:
  * CSS is not properly loaded
  * relative URL ```adios/cache.css``` is not found (404)

Solution:
  * check if you have correct ```--rewrite-base``` argument in your builder command
  * check if your ```.htaccess``` is enabled in the Apache server

## The connecion to the database is not working

Symptoms:
  * the application cannot connect to the database

Possible causes:
  * Database server is not running
  * Database connection string is wrong

Solution:
  * Start/restart your database server
  * Check the ```ConfigEnv``` section your prototype.json file
  
