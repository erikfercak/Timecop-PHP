<?php

date_default_timezone_set('Europe/Prague');
require_once dirname(__FILE__) . '/../lib/Timecop.php';

class TimecopTest extends PHPUnit_Framework_TestCase
{
    // 16.6.2007 13:10:45 GMT+2
    protected $time = 1181992245;

    public function tearDown()
    {
        Timecop::restore();
    }

    public function testTimeFreeze()
    {
        Timecop::freeze();
        $time = Timecop::time();

        usleep(1000001);

        $this->assertEquals($time, Timecop::time());
    }

    public function testTimeTravel()
    {
        $time = Timecop::time();
        Timecop::travel(Timecop::time() - 3600);

        $this->assertGreaterThan(Timecop::time(), time());

        // under freeze
        Timecop::freeze();
        Timecop::travel($this->time);
        usleep(1000001);

        $this->assertEquals(Timecop::time(), $this->time);
    }

    public function testTimeUnfreezing()
    {
        Timecop::freeze();
        Timecop::travel(Timecop::time() - 3600);
        $time = Timecop::time();

        $this->assertGreaterThan(Timecop::time(), time());

        Timecop::unfreeze();
        usleep(1000001);
        $this->assertGreaterThan($time, Timecop::time());
        $this->assertGreaterThan(Timecop::time(), time());
    }

    public function testRestoreTime()
    {
        Timecop::freeze();
        Timecop::travel(Timecop::time() - 3600);
        $time = Timecop::time();

        Timecop::restore();

        $this->assertGreaterThan($time, Timecop::time());
    }

    public function testWarpingAndUnwarpingTime()
    {
        $fail = TRUE;
        try {
            Timecop::unwarpTime();
        } catch (RuntimeException $e) {
            $fail = FALSE;
        }

        if ($fail) {
            $this->fail('Calling unwarp without warping should fail');
        }

        Timecop::freeze();
        Timecop::travel($this->time);
        Timecop::warpTime();

        $fail = TRUE;
        try {
            Timecop::warpTime();
        } catch (RuntimeException $e) {
            $fail = FALSE;
        }

        if ($fail) {
            $this->fail('Warping time more than once without unwarping should fail');
        }

        $this->assertEquals(time(), $this->time);
        $this->assertEquals(time(), Timecop::time());

        $this->assertEquals(date('Y-m-d H:i:s'), date('Y-m-d H:i:s', $this->time));
        $this->assertEquals(date('Y-m-d H:i:s'), Timecop::date('Y-m-d H:i:s'));
        $this->assertEquals(date('Y-m-d H:i:s', $this->time), Timecop::date('Y-m-d H:i:s', $this->time));

        $this->assertEquals(getdate(), Timecop::getdate());
        $this->assertEquals(getdate($this->time), Timecop::getdate($this->time));

        // TODO: TZ/DST/LOCALE check?
        $this->assertEquals(gmdate('Y-m-d H:i:s'), Timecop::gmdate('Y-m-d H:i:s'));
        $this->assertEquals(gmdate('Y-m-d H:i:s', $this->time), Timecop::gmdate('Y-m-d H:i:s', $this->time));
        $this->assertEquals(gmmktime(), Timecop::gmmktime());
        $this->assertEquals(gmstrftime('%a %h %Y %X'), Timecop::gmstrftime('%a %h %Y %X'));
        $this->assertEquals(gmstrftime('%a %h %Y %X', $this->time), Timecop::gmstrftime('%a %h %Y %X', $this->time));

        $this->assertEquals(idate('s'), idate('s', $this->time));
        $this->assertEquals(idate('s'), Timecop::idate('s'));
        $this->assertEquals(idate('s', $this->time), Timecop::idate('s', $this->time));

        $this->assertEquals(localtime(), Timecop::localtime());
        $this->assertEquals(localtime($this->time), Timecop::localtime($this->time));

        $this->assertEquals(mktime(), Timecop::mktime());

        $this->assertEquals(strftime('%a %h %Y %X'), Timecop::strftime('%a %h %Y %X'));
        $this->assertEquals(strftime('%a %h %Y %X', $this->time), Timecop::strftime('%a %h %Y %X', $this->time));

        $this->assertEquals(strptime('2007-06-16 13:10:45', '%a %h %Y %X'), Timecop::strptime('2007-06/16 13:10:45', '%a %h %Y %X'));

        $this->assertEquals(strtotime('+1 day'), Timecop::strtotime('+1 day'));
        $this->assertEquals(strtotime('+1 day', $this->time), Timecop::strtotime('+1 day', $this->time));

        Timecop::unwarpTime();
        usleep(1000001);

        $this->assertNotEquals(time(), $this->time);
        $this->assertNotEquals(date('Y-m-d H:i:s'), Timecop::date('Y-m-d H:i:s'));
        $this->assertNotEquals(getdate(), Timecop::getdate());
        $this->assertNotEquals(gmdate('Y-m-d H:i:s'), Timecop::gmdate('Y-m-d H:i:s'));
        $this->assertNotEquals(gmmktime(), Timecop::gmmktime());
        $this->assertNotEquals(gmstrftime('%a %h %Y %X'), Timecop::gmstrftime('%a %h %Y %X'));
        $this->assertNotEquals(localtime(), Timecop::localtime());
        $this->assertNotEquals(mktime(), Timecop::mktime());
        $this->assertNotEquals(strftime('%a %h %Y %X'), Timecop::strftime('%a %h %Y %X'));
        $this->assertNotEquals(strtotime('+1 day'), Timecop::strtotime('+1 day'));
    }
}
