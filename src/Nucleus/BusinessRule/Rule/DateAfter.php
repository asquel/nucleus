<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Nucleus\BusinessRule\Rule;

use DateTime;
use Nucleus\IService\Clock\IClock;

/**
 * Description of DateAfter
 *
 * @author Martin
 */
class DateAfter
{
    /**
     * @var \Nucleus\IService\Clock\IClock
     */
    private $clock;

    /**
     * @param \Nucleus\IService\Clock\IClock $clock
     * 
     * @Inject
     */
    public function initialize(IClock $clock)
    {
        $this->clock = $clock;
    }

    public function __invoke($date, DateTime $toCompare = null)
    {
        if (is_null($toCompare)) {
            $toCompareTimestamp = $this->clock->now();
        } else {
            $toCompareTimestamp = $toCompare->getTimestamp();
        }
        return $toCompareTimestamp > $this->clock->strtotime($date);
    }
}
