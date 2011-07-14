<?php

date_default_timezone_set('Europe/Prague');
require_once dirname(__FILE__) . '/../lib/Timecop.php';

class ReadmeTest extends PHPUnit_Framework_TestCase
{
    public function testReadmeExamples()
    {
        // override internal PHP functions
        Timecop::warpTime();

        // time travel
        $presentTime = time();
        Timecop::travel(time() - 3600); // one hour back

        $this->assertTrue($presentTime > time());

        Timecop::travel(time() + 7200); // one hour forward
        $this->assertTrue($presentTime < time());

        // freeze time
        Timecop::freeze();
        $frozenTime = time();
        usleep(1000001);

        $this->assertEquals(time(), $frozenTime);

        // restore time - unfreezes time and returns to present
        Timecop::restore();

        $this->assertNotEquals(time(), $frozenTime);
        $this->assertTrue($frozenTime > time());

        // restore original PHP functionality
        Timecop::unwarpTime();
    }
}
