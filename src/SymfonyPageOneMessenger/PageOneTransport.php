<?php

namespace Furison\SymfonyPageOneMessenger;

use Symfony\Component\Notifier\Exception\UnsupportedMessageTypeException;
use Symfony\Component\Notifier\Message\SmsMessage;
use Symfony\Component\Notifier\Message\MessageInterface;
use Symfony\Component\Notifier\Transport\AbstractTransport;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;


class PageOneTransport extends AbstractTransport
{
    protected const HOST = 'www.oventus.com';
    private string $username;
    private string $password;
    private string $from;

    /**
     * @param string|string[]|null $topics
     */
    public function __construct(string $username, string $password, string $from, HttpClientInterface $client = null, EventDispatcherInterface $dispatcher = null)
    {
        $this->username = $username;
        $this->password = $password;
        $this->from = $from;

        parent::__construct($client, $dispatcher);
    }

    public function __toString(): string
    {
        return sprintf('pageone://%s:%s@default?%s', $this->username, $this->password, http_build_query(['from' => $this->from], '', '&'));
    }

    public function supports(MessageInterface $message): bool
    {
        return $message instanceof SmsMessage;
    }

    protected function doSend(MessageInterface $message): SentMessage
    {
        if (!$message instanceof SmsMessage) {
            throw new UnsupportedMessageTypeException(__CLASS__, SmsMessage::class, $message);
        }

        $endpoint = sprintf('https://%s/%s/message?password=%s', $this->getEndpoint(), $this->username, $this->password);
        $response = $this->client->request('POST', $endpoint, [
            'headers' => ['accept' => 'application/json'], //force to JSON instead of XML
            'body' => [
                'from' => $this->from,
                'to' => $message->getPhone(),
                'message' => $message->getSubject(),
                //'deliveryTime' => $deliveryTime,
            ],
        ]);

        try {
            $statusCode = $response->getStatusCode();
        } catch (TransportExceptionInterface $e) {
            throw new TransportException('Could not reach the remote server.', $response, 0, $e);
        }

        try {
            $content = $response->toArray(false);
        } catch (DecodingExceptionInterface $e) {
            throw new TransportException('Could not decode body to an array.', $response, 0, $e);
        }

        if (isset($content['error']) || 201 !== $statusCode) {
            throw new TransportException(sprintf('Unable to send the SMS: "%s".', $content['message'] ?? 'unknown error'), $response);
        }

        return new SentMessage($message, (string) $this);
    }

    protected function getEndpoint(): string
    {
        return self::HOST.'/rest/v1';
    }
}