# Tabel

A Simple Framework - this is meant for simple projects where i do not need the all the parts offered by Laravel for instance, a static php website with a few features like reading and storing items in a database. 

I had planned on it being a no-dependecy framework but i got lazy while working on the DB layer and my custom `QueryBuilder` had some gaps. I chose to use Laravel's Eloquent since i was already acustomed to it and it's simplicity.

> Please note that this should only be used for simple php projects and i created this as part of teaching myself the internals of the Laravel framework.

> [!NOTE]
> Please, unless you are just buliding a very minimal and simple web-app that doesn't need all that laravel offers, i'd recommend for you to stick with laravel of symphony as this is very experimental and has not been tested, plus i just wanted to reduce the number of dependencies that comes with laravel


## Installation
```
composer require tabel/framework
```

## Items Check-list
* create a default table for users and implement Auth UI and flow
* implement Mailing feature, simple mailing
* fix any errors as i go

## License

Be free, you can do anything with the code, but please consult what  `illuminate/database` says as am using it for my db interactions, but you can remove it and use the custom ``QueryBuilder` but please note it only works with SQLite, MySQL and a little bit of Postgress
