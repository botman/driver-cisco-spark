<?php

namespace Tests;

use Mockery as m;
use BotMan\BotMan\Http\Curl;
use PHPUnit_Framework_TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use BotMan\Drivers\CiscoSpark\CiscoSparkDriver;

class CiscoSparkDriverTest extends PHPUnit_Framework_TestCase
{
    private function getDriver($responseData, $htmlInterface = null)
    {
        $request = Request::create('', 'POST', [], [], [], [], json_encode($responseData));
        if ($htmlInterface === null) {
            $htmlInterface = m::mock(Curl::class);
        }

        return new CiscoSparkDriver($request, [
            'cisco-spark' => [
                'token' => 'my-token',
            ],
        ], $htmlInterface);
    }

    private function getValidTestData()
    {
        return [
            'id' => 'Y2lzY29zcGFyazovL3VzL1dFQkhPT0svZjRlNjA1NjAtNjYwMi00ZmIwLWEyNWEtOTQ5ODgxNjA5NDk3',
            'name' => 'Guild Chat to http://requestb.in/1jw0w3x1',
            'resource' => 'messages',
            'event' => 'created',
            'filter' => 'roomId=Y2lzY29zcGFyazovL3VzL1JPT00vY2RlMWRkNDAtMmYwZC0xMWU1LWJhOWMtN2I2NTU2ZDIyMDdi',
            'orgId' => 'Y2lzY29zcGFyazovL3VzL09SR0FOSVpBVElPTi8xZWI2NWZkZi05NjQzLTQxN2YtOTk3NC1hZDcyY2FlMGUxMGY',
            'createdBy' => 'Y2lzY29zcGFyazovL3VzL1BFT1BMRS8xZjdkZTVjYi04NTYxLTQ2NzEtYmMwMy1iYzk3NDMxNDQ0MmQ',
            'appId' => 'Y2lzY29zcGFyazovL3VzL0FQUExJQ0FUSU9OL0MyNzljYjMwYzAyOTE4MGJiNGJkYWViYjA2MWI3OTY1Y2RhMzliNjAyOTdjODUwM2YyNjZhYmY2NmM5OTllYzFm',
            'ownedBy' => 'creator',
            'status' => 'active',
            'actorId' => 'Y2lzY29zcGFyazovL3VzL1BFT1BMRS8xZjdkZTVjYi04NTYxLTQ2NzEtYmMwMy1iYzk3NDMxNDQ0MmQ',
            'data' => [
                'id' => 'Y2lzY29zcGFyazovL3VzL01FU1NBR0UvMzIzZWUyZjAtOWFhZC0xMWU1LTg1YmYtMWRhZjhkNDJlZjlj',
                'roomId' => 'Y2lzY29zcGFyazovL3VzL1JPT00vY2RlMWRkNDAtMmYwZC0xMWU1LWJhOWMtN2I2NTU2ZDIyMDdi',
                'personId' => 'Y2lzY29zcGFyazovL3VzL1BFT1BMRS9lM2EyNjA4OC1hNmRiLTQxZjgtOTliMC1hNTEyMzkyYzAwOTg',
                'personEmail' => 'person@example.com',
                'created' => '2015-12-04T17:33:56.767Z',
            ],
        ];
    }

    /** @test */
    public function it_returns_the_driver_name()
    {
        $driver = $this->getDriver([]);
        $this->assertSame('CiscoSpark', $driver->getName());
    }

    /** @test */
    public function it_matches_the_request()
    {
        $driver = $this->getDriver([
            'to' => '41766013098',
            'messageId' => '0C000000075069C7',
            'text' => 'Hi Julia',
            'type' => 'text',
            'keyword' => 'HEY',
            'message_timestamp' => '2016-11-30 19:27:46',
        ]);
        $this->assertFalse($driver->matchesRequest());

        $driver = $this->getDriver($this->getValidTestData());
        $this->assertTrue($driver->matchesRequest());
    }

    /** @test */
    public function it_returns_the_message_object()
    {
        $botResponseData = [
            'id' => 'bot-id',
        ];
        $botResponse = new Response(json_encode($botResponseData));

        $htmlInterface = m::mock(Curl::class);
        $htmlInterface->shouldReceive('get')
            ->once()
            ->with('https://api.ciscospark.com/v1/people/me', [], [
                'Accept:application/json',
                'Content-Type:application/json',
                'Authorization:Bearer my-token',
            ])
            ->andReturn($botResponse);

        $msgResponseData = [
            'text' => 'Hi Julia',
            'roomId' => 'room-1234567890',
            'personId' => 'person-0987654321',
        ];
        $msgResponse = new Response(json_encode($msgResponseData));

        $htmlInterface->shouldReceive('get')
            ->once()
            ->with('https://api.ciscospark.com/v1/messages/Y2lzY29zcGFyazovL3VzL01FU1NBR0UvMzIzZWUyZjAtOWFhZC0xMWU1LTg1YmYtMWRhZjhkNDJlZjlj', [], [
                'Accept:application/json',
                'Content-Type:application/json',
                'Authorization:Bearer my-token',
            ])
            ->andReturn($msgResponse);

        $driver = $this->getDriver($this->getValidTestData(), $htmlInterface);
        $this->assertTrue(is_array($driver->getMessages()));
    }

    /** @test */
    public function it_returns_the_message_text()
    {
        $botResponseData = [
            'id' => 'bot-id',
        ];
        $botResponse = new Response(json_encode($botResponseData));

        $htmlInterface = m::mock(Curl::class);
        $htmlInterface->shouldReceive('get')
            ->once()
            ->with('https://api.ciscospark.com/v1/people/me', [], [
                'Accept:application/json',
                'Content-Type:application/json',
                'Authorization:Bearer my-token',
            ])
            ->andReturn($botResponse);

        $msgResponseData = [
            'text' => 'Hi Julia',
            'roomId' => 'room-1234567890',
            'personId' => 'person-0987654321',
        ];
        $msgResponse = new Response(json_encode($msgResponseData));

        $htmlInterface->shouldReceive('get')
            ->once()
            ->with('https://api.ciscospark.com/v1/messages/Y2lzY29zcGFyazovL3VzL01FU1NBR0UvMzIzZWUyZjAtOWFhZC0xMWU1LTg1YmYtMWRhZjhkNDJlZjlj', [], [
                'Accept:application/json',
                'Content-Type:application/json',
                'Authorization:Bearer my-token',
            ])
            ->andReturn($msgResponse);

        $driver = $this->getDriver($this->getValidTestData(), $htmlInterface);
        $this->assertSame('Hi Julia', $driver->getMessages()[0]->getText());
    }

    /** @test */
    public function it_detects_bots()
    {
        $botResponseData = [
            'id' => 'bot-id',
        ];
        $botResponse = new Response(json_encode($botResponseData));

        $htmlInterface = m::mock(Curl::class);
        $htmlInterface->shouldReceive('get')
            ->once()
            ->with('https://api.ciscospark.com/v1/people/me', [], [
                'Accept:application/json',
                'Content-Type:application/json',
                'Authorization:Bearer my-token',
            ])
            ->andReturn($botResponse);

        $msgResponseData = [
            'text' => 'Hi Julia',
            'roomId' => 'room-1234567890',
            'personId' => 'bot-id',
        ];
        $msgResponse = new Response(json_encode($msgResponseData));

        $htmlInterface->shouldReceive('get')
            ->once()
            ->with('https://api.ciscospark.com/v1/messages/Y2lzY29zcGFyazovL3VzL01FU1NBR0UvMzIzZWUyZjAtOWFhZC0xMWU1LTg1YmYtMWRhZjhkNDJlZjlj', [], [
                'Accept:application/json',
                'Content-Type:application/json',
                'Authorization:Bearer my-token',
            ])
            ->andReturn($msgResponse);

        $driver = $this->getDriver($this->getValidTestData(), $htmlInterface);
        $this->assertTrue($driver->getMessages()[0]->isFromBot());
    }

    /** @test */
    public function it_returns_the_user_id()
    {
        $botResponseData = [
            'id' => 'bot-id',
        ];
        $botResponse = new Response(json_encode($botResponseData));

        $htmlInterface = m::mock(Curl::class);
        $htmlInterface->shouldReceive('get')
            ->once()
            ->with('https://api.ciscospark.com/v1/people/me', [], [
                'Accept:application/json',
                'Content-Type:application/json',
                'Authorization:Bearer my-token',
            ])
            ->andReturn($botResponse);

        $msgResponseData = [
            'text' => 'Hi Julia',
            'roomId' => 'room-1234567890',
            'personId' => 'person-0987654321',
        ];
        $msgResponse = new Response(json_encode($msgResponseData));

        $htmlInterface->shouldReceive('get')
            ->once()
            ->with('https://api.ciscospark.com/v1/messages/Y2lzY29zcGFyazovL3VzL01FU1NBR0UvMzIzZWUyZjAtOWFhZC0xMWU1LTg1YmYtMWRhZjhkNDJlZjlj', [], [
                'Accept:application/json',
                'Content-Type:application/json',
                'Authorization:Bearer my-token',
            ])
            ->andReturn($msgResponse);

        $driver = $this->getDriver($this->getValidTestData(), $htmlInterface);
        $this->assertSame('room-1234567890', $driver->getMessages()[0]->getSender());
    }

    /** @test */
    public function it_returns_the_channel_id()
    {
        $botResponseData = [
            'id' => 'bot-id',
        ];
        $botResponse = new Response(json_encode($botResponseData));

        $htmlInterface = m::mock(Curl::class);
        $htmlInterface->shouldReceive('get')
            ->once()
            ->with('https://api.ciscospark.com/v1/people/me', [], [
                'Accept:application/json',
                'Content-Type:application/json',
                'Authorization:Bearer my-token',
            ])
            ->andReturn($botResponse);

        $msgResponseData = [
            'text' => 'Hi Julia',
            'roomId' => 'room-1234567890',
            'personId' => 'person-0987654321',
        ];
        $msgResponse = new Response(json_encode($msgResponseData));

        $htmlInterface->shouldReceive('get')
            ->once()
            ->with('https://api.ciscospark.com/v1/messages/Y2lzY29zcGFyazovL3VzL01FU1NBR0UvMzIzZWUyZjAtOWFhZC0xMWU1LTg1YmYtMWRhZjhkNDJlZjlj', [], [
                'Accept:application/json',
                'Content-Type:application/json',
                'Authorization:Bearer my-token',
            ])
            ->andReturn($msgResponse);

        $driver = $this->getDriver($this->getValidTestData(), $htmlInterface);
        $this->assertSame('person-0987654321', $driver->getMessages()[0]->getRecipient());
    }

    /** @test */
    public function it_is_configured()
    {
        $request = m::mock(Request::class.'[getContent]');
        $request->shouldReceive('getContent')->andReturn('');
        $htmlInterface = m::mock(Curl::class);

        $driver = new CiscoSparkDriver($request, [
            'cisco-spark' => [
                'token' => 'token',
            ],
        ], $htmlInterface);

        $this->assertTrue($driver->isConfigured());

        $driver = new CiscoSparkDriver($request, [
            'cisco-spark' => [
                'token' => null,
            ],
        ], $htmlInterface);

        $this->assertFalse($driver->isConfigured());

        $driver = new CiscoSparkDriver($request, [], $htmlInterface);

        $this->assertFalse($driver->isConfigured());
    }
}
