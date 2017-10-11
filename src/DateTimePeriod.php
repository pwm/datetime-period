<?php
declare(strict_types=1);

namespace Pwm\DateTimePeriod;

use DateInterval;
use DateTimeImmutable;
use Pwm\DateTimePeriod\Exceptions\NegativeDateTimePeriod;
use Pwm\DateTimePeriod\Exceptions\TimeZoneMismatch;

class DateTimePeriod
{
    /** @var DateTimeImmutable */
    private $start;

    /** @var DateTimeImmutable */
    private $end;

    /** @var DateInterval */
    private $interval;

    public function __construct(DateTimeImmutable $start, DateTimeImmutable $end)
    {
        self::ensureTimeZonesMatch($start, $end);
        self::ensureStartIsBeforeEnd($start, $end);

        $this->start = $start;
        $this->end = $end;
        $this->interval = $this->start->diff($this->end);
    }

    /**
     * [a1, a2) precedes [b1, b2): a2 < b1
     *
     * |--a--|
     *          |--b--|
     */
    public function precedes(DateTimePeriod $period): bool
    {
        return $this->getEnd() < $period->getStart();
    }

    /**
     * [a1, a2) meets [b1, b2): a2 = b1
     *
     * |--a--|
     *       |--b--|
     */
    public function meets(DateTimePeriod $period): bool
    {
        return $this->getEnd() == $period->getStart();
    }

    /**
     * [a1, a2) overlaps [b1, b2): a1 < b1 and a2 < b2 and b1 < a2
     *
     * |--a--|
     *    |--b--|
     */
    public function overlaps(DateTimePeriod $period): bool
    {
        return
            $this->getStart() < $period->getStart() &&
            $this->getEnd() < $period->getEnd() &&
            $period->getStart() < $this->getEnd();
    }

    /**
     * [a1, a2) finishedBy [b1, b2): a1 < b1 and a2 = b2
     *
     * |----a----|
     *     |--b--|
     */
    public function finishedBy(DateTimePeriod $period): bool
    {
        return
            $this->getStart() < $period->getStart() &&
            $this->getEnd() == $period->getEnd();
    }

    /**
     * [a1, a2) contains [b1, b2): a1 < b1 and b2 < a2
     *
     * |----a----|
     *   |--b--|
     */
    public function contains(DateTimePeriod $period): bool
    {
        return
            $this->getStart() < $period->getStart() &&
            $period->getEnd() < $this->getEnd();
    }

    /**
     * [a1, a2) starts [b1, b2): a1 = b1 and a2 < b2
     *
     * |--a--|
     * |----b----|
     */
    public function starts(DateTimePeriod $period): bool
    {
        return
            $this->getStart() == $period->getStart() &&
            $this->getEnd() < $period->getEnd();
    }

    /**
     * [a1, a2) equals [b1, b2): a1 = b1 && a2 = b2
     *
     * |--a--|
     * |--b--|
     */
    public function equals(DateTimePeriod $period): bool
    {
        // Using == for structural comparison
        return
            $this->getStart() == $period->getStart() &&
            $this->getEnd() == $period->getEnd();
    }

    /**
     * [a1, a2) startedBy [b1, b2): a1 = b1 and b2 < a2
     *
     *  |----a----|
     *  |--b--|
     */
    public function startedBy(DateTimePeriod $period): bool
    {
        return
            $this->getStart() == $period->getStart() &&
            $period->getEnd() < $this->getEnd();
    }

    /**
     * [a1, a2) during [b1, b2): b1 < a1 and a2 < b2
     *
     *   |--a--|
     * |----b----|
     */
    public function during(DateTimePeriod $period): bool
    {
        return
            $period->getStart() < $this->getStart() &&
            $this->getEnd() < $period->getEnd();
    }

    /**
     * [a1, a2) finishes [b1, b2): b1 < a1 and a2 = b2
     *
     *     |--a--|
     * |----b----|
     */
    public function finishes(DateTimePeriod $period): bool
    {
        return
            $period->getStart() < $this->getStart() &&
            $this->getEnd() == $period->getEnd();
    }

    /**
     * [a1, a2) overlappedBy [b1, b2): b1 < a1 and b2 < a2 and a1 < b2
     *
     *    |--a--|
     * |--b--|
     */
    public function overlappedBy(DateTimePeriod $period): bool
    {
        return
            $period->getStart() < $this->getStart() &&
            $period->getEnd() < $this->getEnd() &&
            $this->getStart() < $period->getEnd();
    }

    /**
     * [a1, a2) metBy [b1, b2): b2 = a1
     *
     *       |--a--|
     * |--b--|
     */
    public function metBy(DateTimePeriod $period): bool
    {
        return $period->getEnd() == $this->getStart();
    }

    /**
     * [a1, a2) precededBy [b1, b2): b2 < a1
     *
     *          |--a--|
     * |--b--|
     */
    public function precededBy(DateTimePeriod $period): bool
    {
        return $period->getEnd() < $this->getStart();
    }

    public function getStart(): DateTimeImmutable
    {
        return $this->start;
    }

    public function getEnd(): DateTimeImmutable
    {
        return $this->end;
    }

    public function getInterval(): DateInterval
    {
        return $this->interval;
    }

    private static function ensureTimeZonesMatch(DateTimeImmutable $start, DateTimeImmutable $end): void
    {
        if ($start->getTimezone()->getOffset($start) !== $end->getTimezone()->getOffset($end)) {
            throw new TimeZoneMismatch(
                sprintf(
                    'Start date timezone %s and end date timezone %s differ',
                    $start->getTimezone()->getName(),
                    $end->getTimezone()->getName()
                )
            );
        }
    }

    private static function ensureStartIsBeforeEnd(DateTimeImmutable $start, DateTimeImmutable $end): void
    {
        if ($start > $end) {
            throw new NegativeDateTimePeriod(
                sprintf(
                    'Start date "%s" cannot be after end date "%s".',
                    $start->format(DATE_ATOM),
                    $end->format(DATE_ATOM)
                )
            );
        }
    }
}
