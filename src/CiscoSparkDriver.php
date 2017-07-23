<?php

namespace BotMan\Drivers\CiscoSpark;

use BotMan\BotMan\Users\User;
use Illuminate\Support\Collection;
use BotMan\BotMan\Drivers\HttpDriver;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Question;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use BotMan\BotMan\Messages\Attachments\Location;
use Symfony\Component\HttpFoundation\ParameterBag;
use BotMan\BotMan\Messages\Incoming\IncomingMessage;
use BotMan\BotMan\Messages\Outgoing\OutgoingMessage;

class CiscoSparkDriver extends HttpDriver
{
    const DRIVER_NAME = 'CiscoSpark';

    const API_ENDPOINT = 'https://api.ciscospark.com/v1/';

    /** @var string|null */
    private $botId;

    /**
     * @param Request $request
     */
    public function buildPayload(Request $request)
    {
        $this->payload = new ParameterBag((array) json_decode($request->getContent(), true));
        $this->event = Collection::make($this->payload->get('data'));
        $this->config = Collection::make($this->config->get('cisco-spark'));
    }

    /**
     * @return array
     */
    protected function getHeaders()
    {
        return [
            'Accept:application/json',
            'Content-Type:application/json',
            'Authorization:Bearer '.$this->config->get('token'),
        ];
    }

    /**
     * Determine if the request is for this driver.
     *
     * @return bool
     */
    public function matchesRequest()
    {
        return ! is_null($this->payload->get('actorId')) && $this->payload->get('resource') === 'messages' && $this->payload->get('event') === 'created';
    }

    /**
     * @param  \BotMan\BotMan\Messages\Incoming\IncomingMessage $message
     * @return Answer
     */
    public function getConversationAnswer(IncomingMessage $message)
    {
        return Answer::create($message->getText())->setMessage($message);
    }

    /**
     * Retrieve the chat message.
     *
     * @return array
     */
    public function getMessages()
    {
        $this->getBotId();

        $messageContent = $this->getMessageContent($this->event->get('id'));

        $message = new IncomingMessage($messageContent->text, $messageContent->roomId, $messageContent->personId, $messageContent);

        if ($this->getBotId() === $messageContent->personId) {
            $message->setIsFromBot(true);
        }

        return [$message];
    }

    /**
     * @param $messageId
     * @return mixed
     */
    protected function getMessageContent($messageId)
    {
        $response = $this->http->get(self::API_ENDPOINT.'messages/'.$messageId, [], $this->getHeaders());

        return json_decode($response->getContent());
    }

    /**
     * @param string|Question|IncomingMessage $message
     * @param \BotMan\BotMan\Messages\Incoming\IncomingMessage $matchingMessage
     * @param array $additionalParameters
     * @return Response|null
     */
    public function buildServicePayload($message, $matchingMessage, $additionalParameters = [])
    {
        $parameters = array_merge_recursive([
            'roomId' => $matchingMessage->getSender(),
        ], $additionalParameters);
        /*
         * If we send a Question with buttons, ignore
         * the text and append the question.
         */
        if ($message instanceof Question) {
            $parameters['text'] = $message->getText();
            $parameters['markdown'] = $message->getText();
        } elseif ($message instanceof OutgoingMessage) {
            $parameters['text'] = $message->getText();
            $parameters['markdown'] = $message->getText();

            $attachment = $message->getAttachment();
            if (! is_null($attachment) && ! $attachment instanceof Location) {
                $parameters['files'] = $attachment->getUrl();
            }
        } else {
            $parameters['text'] = $message;
            $parameters['markdown'] = $message;
        }

        return $parameters;
    }

    /**
     * @param mixed $payload
     * @return Response
     */
    public function sendPayload($payload)
    {
        return $this->http->post(self::API_ENDPOINT.'messages', [], $payload, $this->getHeaders(), true);
    }

    /**
     * @return bool
     */
    public function isConfigured()
    {
        return ! is_null($this->config->get('token'));
    }

    /**
     * Retrieve User information.
     * @param \BotMan\BotMan\Messages\Incoming\IncomingMessage $matchingMessage
     * @return User
     */
    public function getUser(IncomingMessage $matchingMessage)
    {
        $personId = $matchingMessage->getPayload()->personId;
        $response = $this->http->get(self::API_ENDPOINT.'people/'.$personId, [], $this->getHeaders());
        $userInfo = Collection::make(json_decode($response->getContent(), true));

        return new User($userInfo->get('id'), $userInfo->get('firstName'), $userInfo->get('lastName'), $userInfo->get('nickName'));
    }

    /**
     * Low-level method to perform driver specific API requests.
     *
     * @param string $endpoint
     * @param array $parameters
     * @param \BotMan\BotMan\Messages\Incoming\IncomingMessage $matchingMessage
     * @return void
     */
    public function sendRequest($endpoint, array $parameters, IncomingMessage $matchingMessage)
    {
        //
    }

    /**
     * Returns the chatbot ID.
     * @return string
     */
    private function getBotId()
    {
        if (is_null($this->botId)) {
            $response = $this->http->get(self::API_ENDPOINT.'people/me', [], $this->getHeaders());
            $bot = json_decode($response->getContent());
            $this->botId = $bot->id;
        }

        return $this->botId;
    }
}
