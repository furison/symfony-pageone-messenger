# PageOne Notifier for Symfony Notifier

Provides [PageOne](https://www.pageone.co.uk/) integration for Symfony Notifier.

## Installation
Install using composer
`composer require furison/symfony-pageone-messenger`

## Usage
In config/services.yaml
`services:
    Furison\SymfonyPageOneMessenger\PageOneTransportFactory:
        tags: [notifier.transport_factory]`

Then configure the transport in # config/packages/messenger.yaml
`framework:
    messenger:
        transports:
            pageone: 'pageone://username:password@default?from=fromMsisdn'`