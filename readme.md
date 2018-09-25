# DateTimePeriod

[![Build Status](https://travis-ci.org/pwm/datetime-period.svg?branch=master)](https://travis-ci.org/pwm/datetime-period)
[![codecov](https://codecov.io/gh/pwm/datetime-period/branch/master/graph/badge.svg)](https://codecov.io/gh/pwm/datetime-period)
[![Maintainability](https://api.codeclimate.com/v1/badges/25356a7f11c642ee8ac5/maintainability)](https://codeclimate.com/github/pwm/datetime-period/maintainability)
[![Test Coverage](https://api.codeclimate.com/v1/badges/25356a7f11c642ee8ac5/test_coverage)](https://codeclimate.com/github/pwm/datetime-period/test_coverage)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)

An implementation of the datetime period type for working with temporal intervals. The library includes the full set of relations on intervals defined by [Allen's Interval Algebra](https://www.ics.uci.edu/~alspaugh/cls/shr/allen.html). For further information see the "Usage" and "How it works" paragraphs.

## Table of Contents

* [Requirements](#requirements)
* [Installation](#installation)
* [Usage](#usage)
* [How it works](#how-it-works)
* [Tests](#tests)
* [Changelog](#changelog)
* [Todo](#todo)
* [Licence](#licence)

## Requirements

PHP 7.1+

## Installation

    $ composer require pwm/datetime-period

## Usage

##### Creation:

```php
$start = new DateTimeImmutable('2010-10-10T10:10:10+00:00');
$end = new DateTimeImmutable('2011-11-11T11:11:11+00:00');
$period = new DateTimePeriod($start, $end);

// Start and end instants (see the definition of instant under "How it works")
$start = $period->getStart(); // DateTimeImmutable('2010-10-10T10:10:10+00:00')
$end = $period->getEnd(); // DateTimeImmutable('2011-11-11T11:11:11+00:00')
```

##### Restrictions:

```php
// Throws TimeZoneMismatch exception
new DateTimePeriod(
    new DateTimeImmutable('2017-10-10T10:10:10+02:00'),
    new DateTimeImmutable('2017-10-10T10:10:10-05:00')
);

// Throws NegativeDateTimePeriod exception
new DateTimePeriod(
    new DateTimeImmutable('+1 day'),
    new DateTimeImmutable('-1 day')
);
```

##### The full set of relations between 2 periods:

```php
$a = new DateTimePeriod(new DateTimeImmutable('...'), new DateTimeImmutable('...'));
$b = new DateTimePeriod(new DateTimeImmutable('...'), new DateTimeImmutable('...'));

// |--a--|
//          |--b--|
$a->precedes($b);

// |--a--|
//       |--b--|
$a->meets($b);

// |--a--|
//    |--b--|
$a->overlaps($b);

// |----a----|
//     |--b--|
$a->finishedBy($b);

// |----a----|
//   |--b--|
$a->contains($b);

// |--a--|
// |----b----|
$a->starts($b);

// |--a--|
// |--b--|
$a->equals($b);

//  |----a----|
//  |--b--|
$a->startedBy($b);

//   |--a--|
// |----b----|
$a->during($b);

//     |--a--|
// |----b----|
$a->finishes($b);

//    |--a--|
// |--b--|
$a->overlappedBy($b);

//       |--a--|
// |--b--|
$a->metBy($b);

//          |--a--|
// |--b--|
$a->precededBy($b);
```

##### Extensibility:

The `DateTimePeriod` object itself is immutable, meaning that once created you can't change the state of the object, ie. the values of its properties. However the properties have been defined as `protected` so that you can subclass the type in your project if the need arises.

##### Working with different granularities:

The 2 periods below meet on a timeline with hour granularity but does not meet on a more fine-grained timeline with minute granularity.

```php
$aStart = '2017-01-01T12:12:09.829462+00:00';
$aEnd   = '2017-01-01T14:23:34.534678+00:00';
$bStart = '2017-01-01T14:41:57.657388+00:00';
$bEnd   = '2017-01-01T16:19:03.412832+00:00';

$hourGranule = 'Y-m-d\TH';
$a = new DateTimePeriod(
    DateTimeImmutable::createFromFormat($hourGranule, (new DateTimeImmutable($aStart))->format($hourGranule)),
    DateTimeImmutable::createFromFormat($hourGranule, (new DateTimeImmutable($aEnd))->format($hourGranule))
);
$b = new DateTimePeriod(
    DateTimeImmutable::createFromFormat($hourGranule, (new DateTimeImmutable($bStart))->format($hourGranule)),
    DateTimeImmutable::createFromFormat($hourGranule, (new DateTimeImmutable($bEnd))->format($hourGranule))
);
assert($a->meets($b) === true); // a meets b by the hour granule

$minuteGranule = 'Y-m-d\TH:i';
$a = new DateTimePeriod(
    DateTimeImmutable::createFromFormat($minuteGranule, (new DateTimeImmutable($aStart))->format($minuteGranule)),
    DateTimeImmutable::createFromFormat($minuteGranule, (new DateTimeImmutable($aEnd))->format($minuteGranule))
);

$b = new DateTimePeriod(
    DateTimeImmutable::createFromFormat($minuteGranule, (new DateTimeImmutable($bStart))->format($minuteGranule)),
    DateTimeImmutable::createFromFormat($minuteGranule, (new DateTimeImmutable($bEnd))->format($minuteGranule))
);
assert($a->meets($b) === false); // a does not meet b by the minute granule
```

##### Miscellaneous:

This is a list of small helper methods that can save you time when you need their specific functionality.

1. Get the number of days in a period:

```php
$start = new DateTimeImmutable('2016-01-01T11:11:11+00:00');
$end = new DateTimeImmutable('2018-01-01T11:11:11+00:00');
$period = new DateTimePeriod($start, $end);
assert(366 + 365 === $period->getNumberOfDays()); // 2016 was a leap year
```

## How it works

In order to be able to talk about periods first let's agree on the following definitions:

#### Definitions

##### 1. Instant
An anchor, ie. discrete point, on the timeline. The most basic temporal type. A "true" time instant is theoretical like a point on a continuous geometrical line. A representation of an instant, however, always has a duration, called a granule. We can thus represent the same instant using various discreet timelines of different granularities. Eg. "2017-10-10" and "2017-10-10 10:10:10" could represent the same instant.

##### 2. Interval
An unanchored, directed portion of the timeline. Unanchored means it has no absolute relation to the timeline. Examples are "2 weeks" or "1 day, 2 hours and 3 minutes". Directed means it is perfectly valid to say "-3 days".

##### 3. Period
An anchored interval on the timeline. There are several possible representations, the most common being a pair of ordered instants of identical granularity. Depending on the representation the interval of a period can be open or closed on both its start and end. A common way is to use a closed-open interval, ie. [start, end), which helps simplifying calculations. Eg. the period ["2017-10-10", "2017-11-11") includes the instant "2017-10-10" but excludes the instant "2017-11-11".

We arrived to the definition of a period. Now on to...

#### Relations

Defining relations on periods is somewhat complex as there is no [total order](https://en.wikipedia.org/wiki/Total_order). In 1983 James F. Allen wrote a paper in which he defined 13 jointly exhaustive and pairwise disjoint binary relations on intervals, meaning that any 2 intervals are related exactly one way. You can see each of the 13 relations above, in the "Usage" section. These relations and the operations on them form what is referred to as [Allen's interval algebra](https://www.ics.uci.edu/~alspaugh/cls/shr/allen.html).

#### Complexities

Dealing with calendrical time is difficult with many peculiarities. This, in turn, means that dealing with derivative entities, like `DateTimePeriod` is complex as well.

Here is an example involving timezones:

If you try to create a one year `DateTimePeriod` starting from `2018-03-26T08:00:00` and ending at `2019-03-26T08:00:00` for the timezone `Europe/London` it would fail with a `UTCOffsetMismatch` exception. This is because for the start instant the timezone `Europe/London` equals to `BST` (which equals to `UTC+01:00`) while for the end instant `Europe/London` equals to `GMT` (which equals to `UTC+00:00`). This is because daylight saving time happens on different days in different years.

If you create the above period using UTC offsets, ie. from `2018-03-26T08:00:00+01:00` to `2019-03-26T08:00:00+01:00` that would not throw a `UTCOffsetMismatch`, however the end instant will not be a valid `Europe/London` datetime so you would have to calculate the correct time for `Europe/London` from your `UTC+01:00` offset.

The supplied `DateTimePeriod::getUtcOffset()` function can help ease this problem by mapping your current timezone to its UTC offset.

## Tests

	$ vendor/bin/phpunit
	$ composer phpcs
	$ composer phpstan
	$ composer infection

## Changelog

[Click here](changelog.md)

## Todo

 * Work out a more user friendly solution for the UTC offset / timezone problem.

## Licence

[MIT](LICENSE)
