<?php
declare(strict_types=1);

namespace Pwm\DateTimePeriod;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class DateTimePeriodTest extends TestCase
{
    private const PREDICATES = [
        'precedes',
        'meets',
        'overlaps',
        'finishedBy',
        'contains',
        'starts',
        'equals',
        'startedBy',
        'during',
        'finishes',
        'overlappedBy',
        'metBy',
        'precededBy',
    ];

    /**
     * @test
     */
    public function it_creates_from_start_and_end_instants(): void
    {
        $start = new DateTimeImmutable('2017-01-01T14:15:00+01:00');
        $end = new DateTimeImmutable('2017-05-04T16:45:00+01:00');

        $period = new DateTimePeriod($start, $end);

        self::assertInstanceOf(DateTimePeriod::class, $period);
        self::assertEquals($start, $period->getStart());
        self::assertEquals($end, $period->getEnd());
        self::assertEquals($start->diff($end), $period->getInterval());
    }

    /**
     * @test
     * @expectedException \Pwm\DateTimePeriod\Exceptions\TimeZoneMismatch
     */
    public function ensure_that_start_and_end_timezones_are_equal(): void
    {
        new DateTimePeriod(new DateTimeImmutable('2017-10-10T10:10:10+02:00'), new DateTimeImmutable('2017-10-10T10:10:10-05:00'));
    }

    /**
     * @test
     * @expectedException \Pwm\DateTimePeriod\Exceptions\NegativeDateTimePeriod
     */
    public function ensure_that_start_is_before_end(): void
    {
        new DateTimePeriod(new DateTimeImmutable('+1 day'), new DateTimeImmutable('-1 day'));
    }

    /**
     * @test
     */
    public function check_precedes_predicate(): void
    {
        $a = new DateTimePeriod(new DateTimeImmutable('2017-01-01T10:00:00+00:00'), new DateTimeImmutable('2017-01-01T12:00:00+00:00'));
        $b = new DateTimePeriod(new DateTimeImmutable('2017-01-01T14:00:00+00:00'), new DateTimeImmutable('2017-01-01T16:00:00+00:00'));

        self::assertTrue($a->precedes($b));
        static::assertFalse(self::checkOtherPredicates(array_diff(self::PREDICATES, ['precedes']), $a, $b));
        self::assertTrue($b->precededBy($a)); // converse relation
    }

    /**
     * @test
     */
    public function check_meets_predicate(): void
    {
        $a = new DateTimePeriod(new DateTimeImmutable('2017-01-01T10:00:00+00:00'), new DateTimeImmutable('2017-01-01T12:00:00+00:00'));
        $b = new DateTimePeriod(new DateTimeImmutable('2017-01-01T12:00:00+00:00'), new DateTimeImmutable('2017-01-01T14:00:00+00:00'));

        self::assertTrue($a->meets($b));
        static::assertFalse(self::checkOtherPredicates(array_diff(self::PREDICATES, ['meets']), $a, $b));
        self::assertTrue($b->metBy($a)); // converse relation
    }

    /**
     * @test
     */
    public function check_overlaps_predicate(): void
    {
        $a = new DateTimePeriod(new DateTimeImmutable('2017-01-01T11:00:00+00:00'), new DateTimeImmutable('2017-01-01T13:00:00+00:00'));
        $b = new DateTimePeriod(new DateTimeImmutable('2017-01-01T12:00:00+00:00'), new DateTimeImmutable('2017-01-01T14:00:00+00:00'));

        self::assertTrue($a->overlaps($b));
        static::assertFalse(self::checkOtherPredicates(array_diff(self::PREDICATES, ['overlaps']), $a, $b));
        self::assertTrue($b->overlappedBy($a)); // converse relation
    }

    /**
     * @test
     */
    public function check_finishedBy_predicate(): void
    {
        $a = new DateTimePeriod(new DateTimeImmutable('2017-01-01T10:00:00+00:00'), new DateTimeImmutable('2017-01-01T14:00:00+00:00'));
        $b = new DateTimePeriod(new DateTimeImmutable('2017-01-01T12:00:00+00:00'), new DateTimeImmutable('2017-01-01T14:00:00+00:00'));

        self::assertTrue($a->finishedBy($b));
        static::assertFalse(self::checkOtherPredicates(array_diff(self::PREDICATES, ['finishedBy']), $a, $b));
        self::assertTrue($b->finishes($a)); // converse relation
    }

    /**
     * @test
     */
    public function check_contains_predicate(): void
    {
        $a = new DateTimePeriod(new DateTimeImmutable('2017-01-01T10:00:00+00:00'), new DateTimeImmutable('2017-01-01T14:00:00+00:00'));
        $b = new DateTimePeriod(new DateTimeImmutable('2017-01-01T11:00:00+00:00'), new DateTimeImmutable('2017-01-01T13:00:00+00:00'));

        self::assertTrue($a->contains($b));
        static::assertFalse(self::checkOtherPredicates(array_diff(self::PREDICATES, ['contains']), $a, $b));
        self::assertTrue($b->during($a)); // converse relation
    }

    /**
     * @test
     */
    public function check_starts_predicate(): void
    {
        $a = new DateTimePeriod(new DateTimeImmutable('2017-01-01T10:00:00+00:00'), new DateTimeImmutable('2017-01-01T12:00:00+00:00'));
        $b = new DateTimePeriod(new DateTimeImmutable('2017-01-01T10:00:00+00:00'), new DateTimeImmutable('2017-01-01T14:00:00+00:00'));

        self::assertTrue($a->starts($b));
        static::assertFalse(self::checkOtherPredicates(array_diff(self::PREDICATES, ['starts']), $a, $b));
        self::assertTrue($b->startedBy($a)); // converse relation
    }

    /**
     * @test
     */
    public function check_equals_predicate(): void
    {
        $a = new DateTimePeriod(new DateTimeImmutable('2017-01-01T10:00:00+00:00'), new DateTimeImmutable('2017-01-01T12:00:00+00:00'));
        $b = new DateTimePeriod(new DateTimeImmutable('2017-01-01T10:00:00+00:00'), new DateTimeImmutable('2017-01-01T12:00:00+00:00'));

        self::assertTrue($a->equals($b));
        static::assertFalse(self::checkOtherPredicates(array_diff(self::PREDICATES, ['equals']), $a, $b));
        self::assertTrue($b->equals($a)); // converse relation (equals is the converse of itself)
    }

    /**
     * @test
     */
    public function check_startedBy_predicate(): void
    {
        $a = new DateTimePeriod(new DateTimeImmutable('2017-01-01T10:00:00+00:00'), new DateTimeImmutable('2017-01-01T14:00:00+00:00'));
        $b = new DateTimePeriod(new DateTimeImmutable('2017-01-01T10:00:00+00:00'), new DateTimeImmutable('2017-01-01T12:00:00+00:00'));

        self::assertTrue($a->startedBy($b));
        static::assertFalse(self::checkOtherPredicates(array_diff(self::PREDICATES, ['startedBy']), $a, $b));
        self::assertTrue($b->starts($a)); // converse relation
    }

    /**
     * @test
     */
    public function check_during_predicate(): void
    {
        $a = new DateTimePeriod(new DateTimeImmutable('2017-01-01T11:00:00+00:00'), new DateTimeImmutable('2017-01-01T13:00:00+00:00'));
        $b = new DateTimePeriod(new DateTimeImmutable('2017-01-01T10:00:00+00:00'), new DateTimeImmutable('2017-01-01T14:00:00+00:00'));

        self::assertTrue($a->during($b));
        static::assertFalse(self::checkOtherPredicates(array_diff(self::PREDICATES, ['during']), $a, $b));
        self::assertTrue($b->contains($a)); // converse relation
    }

    /**
     * @test
     */
    public function check_finishes_predicate(): void
    {
        $a = new DateTimePeriod(new DateTimeImmutable('2017-01-01T12:00:00+00:00'), new DateTimeImmutable('2017-01-01T14:00:00+00:00'));
        $b = new DateTimePeriod(new DateTimeImmutable('2017-01-01T10:00:00+00:00'), new DateTimeImmutable('2017-01-01T14:00:00+00:00'));

        self::assertTrue($a->finishes($b));
        static::assertFalse(self::checkOtherPredicates(array_diff(self::PREDICATES, ['finishes']), $a, $b));
        self::assertTrue($b->finishedBy($a)); // converse relation
    }

    /**
     * @test
     */
    public function check_overlappedBy_predicate(): void
    {
        $a = new DateTimePeriod(new DateTimeImmutable('2017-01-01T12:00:00+00:00'), new DateTimeImmutable('2017-01-01T14:00:00+00:00'));
        $b = new DateTimePeriod(new DateTimeImmutable('2017-01-01T11:00:00+00:00'), new DateTimeImmutable('2017-01-01T13:00:00+00:00'));

        self::assertTrue($a->overlappedBy($b));
        static::assertFalse(self::checkOtherPredicates(array_diff(self::PREDICATES, ['overlappedBy']), $a, $b));
        self::assertTrue($b->overlaps($a)); // converse relation
    }

    /**
     * @test
     */
    public function check_metBy_predicate(): void
    {
        $a = new DateTimePeriod(new DateTimeImmutable('2017-01-01T12:00:00+00:00'), new DateTimeImmutable('2017-01-01T14:00:00+00:00'));
        $b = new DateTimePeriod(new DateTimeImmutable('2017-01-01T10:00:00+00:00'), new DateTimeImmutable('2017-01-01T12:00:00+00:00'));

        self::assertTrue($a->metBy($b));
        static::assertFalse(self::checkOtherPredicates(array_diff(self::PREDICATES, ['metBy']), $a, $b));
        self::assertTrue($b->meets($a)); // converse relation
    }

    /**
     * @test
     */
    public function check_precededBy_predicate(): void
    {
        $a = new DateTimePeriod(new DateTimeImmutable('2017-01-01T14:00:00+00:00'), new DateTimeImmutable('2017-01-01T16:00:00+00:00'));
        $b = new DateTimePeriod(new DateTimeImmutable('2017-01-01T10:00:00+00:00'), new DateTimeImmutable('2017-01-01T12:00:00+00:00'));

        self::assertTrue($a->precededBy($b));
        static::assertFalse(self::checkOtherPredicates(array_diff(self::PREDICATES, ['precededBy']), $a, $b));
        self::assertTrue($b->precedes($a)); // converse relation
    }

    private static function checkOtherPredicates(array $predicates, DateTimePeriod $a, DateTimePeriod $b): bool
    {
        return array_reduce($predicates, function (bool $result, string $predicate) use ($a, $b) {
            return $result || $a->{$predicate}($b);
        }, false);
    }
}
