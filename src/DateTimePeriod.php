<?php
declare(strict_types=1);

namespace Pwm\DateTimePeriod;

use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Pwm\DateTimePeriod\Exceptions\NegativeDateTimePeriod;
use Pwm\DateTimePeriod\Exceptions\UTCOffsetMismatch;

class DateTimePeriod
{
    /** @var DateTimeImmutable */
    protected $start;

    /** @var DateTimeImmutable */
    protected $end;

    public function __construct(DateTimeImmutable $start, DateTimeImmutable $end)
    {
        self::ensureUTCOffsetsMatch($start, $end);
        self::ensureStartIsBeforeEnd($start, $end);

        $this->start = $start;
        $this->end = $end;
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

    public static function getUtcOffset(DateTimeImmutable $datetime): string
    {
        $utcOffset = $datetime
            ->getTimezone()
            ->getOffset(new DateTime($datetime->format('Y-m-d H:i:s'), new DateTimeZone('UTC')));
        $hour = floor(abs($utcOffset) / 3600);
        $minute = abs($utcOffset) % 3600 / 60;
        return sprintf('%s%02s:%02s', $utcOffset >= 0 ? '+' : '-', $hour, $minute);
    }

    private static function ensureUTCOffsetsMatch(DateTimeImmutable $start, DateTimeImmutable $end): void
    {
        if ($start->getOffset() !== $end->getOffset()) {
            throw new UTCOffsetMismatch(
                sprintf(
                    'Start instant UTC offset %s and end instant UTC offset %s differ.',
                    $start->getOffset(),
                    $end->getOffset()
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
