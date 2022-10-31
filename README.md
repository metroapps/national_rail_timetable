# National Rail Timetable web site

This is a website of showing National Rail timetable information.
It comes with two forms: departure board view, and timetable view.

### Departure board view
It lists the departures in order, the destination, and all subsequent calling
points of all portions.

### Timetable view
It is the web version of traditional timetables, listing the information in the
traditional tabular form similar to paper timetables, but updated using the 
latest journey planner data.

## Installation
1. Copy `config.example.php` to `config.php` and set its values
2. Run `composer install`
3. Point the web root to `public_html` folder

## Loading data
Run `bin/update.bash` with EMAIL and PASSWORD environment variables set to your
National Rail Open Data credentials. It should be put into the scheduler to be
run every day.

## Demo
A public instance is located at [https://gbtt.uk/]()