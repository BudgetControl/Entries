<?php
namespace Budgetcontrol\Test\Unit;

use ReflectionClass;
use PHPUnit\Framework\TestCase;
use Budgetcontrol\Entry\Controller\Controller;
use Illuminate\Support\Facades\Date as Carbon;

class ControllerTest extends TestCase
{
    protected \PHPUnit\Framework\MockObject\MockObject $controller;

    protected function setUp(): void
    {
        $this->controller = $this->getMockBuilder(Controller::class)
            ->disableOriginalConstructor()
            ->onlyMethods([])
            ->getMock();
    }

    public function testIsPlannedReturnsTrueForFutureDate()
    {
        $reflection = new ReflectionClass($this->controller);
        $method = $reflection->getMethod('isPlanned');
        $method->setAccessible(true);

        $futureDate = Carbon::now()->addMinutes(10)->toDateTimeString();
        $result = $method->invoke($this->controller, $futureDate);

        $this->assertTrue($result);
    }

    public function testIsPlannedReturnsFalseForPastDate()
    {
        $reflection = new ReflectionClass($this->controller);
        $method = $reflection->getMethod('isPlanned');
        $method->setAccessible(true);

        $pastDate = Carbon::now()->subMinutes(10)->toDateTimeString();
        $result = $method->invoke($this->controller, $pastDate);

        $this->assertFalse($result);
    }

    public function testIsPlannedReturnsFalseForCurrentDate()
    {
        $reflection = new ReflectionClass($this->controller);
        $method = $reflection->getMethod('isPlanned');
        $method->setAccessible(true);

        $nowDate = Carbon::now()->toDateTimeString();
        $result = $method->invoke($this->controller, $nowDate);

        $this->assertFalse($result);
    }

    public function testIfPlunnedReturnFalseForSameDateWithDifferentSeconds()
    {
        $reflection = new ReflectionClass($this->controller);
        $method = $reflection->getMethod('isPlanned');
        $method->setAccessible(true);

        // Create a date that is the same as now but with different seconds
        $now = Carbon::now();
        $sameDateWithDifferentSeconds = $now->copy()->setSecond(10)->toDateTimeString();
        $result = $method->invoke($this->controller, $sameDateWithDifferentSeconds);

        $this->assertFalse($result);
    }
}