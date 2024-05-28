# Tabel

My Simple Framework - this is for simple projects where i do not need the all the parts offered by Laravel for instance, a static php website with a few features like reading and storing items in a database. 

I had planned on it being a no-dependecy framework but i got lazy while working on the DB layer and my custom `QueryBuilder` had some gaps. I chose to use Laravel's Eloquent since i was already acustomed to it and it's simplicity.

> Please note that this should only be used for simple php projects and i created this as part of teaching myself the Laravel framework.

## Installation
```
composer require tabel/framework
```
## Development

Want to contribute? just fork, then pull a request to merge.
I'm yet to work on tests and Mail functions

## Items Check-list
* implement `Router:has('route')` && `router('route')`
* implement `url('route')`
* Connect DB to Eloquent

## License

MIT
