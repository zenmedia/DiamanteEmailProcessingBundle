<?php
/*
 * Copyright (c) 2014 Eltrino LLC (http://eltrino.com)
 *
 * Licensed under the Open Software License (OSL 3.0).
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *    http://opensource.org/licenses/osl-3.0.php
 *
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@eltrino.com so we can send you a copy immediately.
 */
namespace Eltrino\EmailProcessingBundle\Tests\Model\Service;

use Eltrino\EmailProcessingBundle\Model\Message;
use Eltrino\EmailProcessingBundle\Model\Service\MessageProcessingManager;
use Eltrino\PHPUnit\MockAnnotations\MockAnnotations;

class MessageProcessingManagerTest extends \PHPUnit_Framework_TestCase
{
    const DUMMY_MESSAGE_UNIQUE_ID = 'dummy_message_unique_id';
    const DUMMY_MESSAGE_ID        = 'dummy_message_id';
    const DUMMY_MESSAGE_SUBJECT   = 'dummy_message_subject';
    const DUMMY_MESSAGE_CONTENT   = 'dummy_message_content';
    const DUMMY_MESSAGE_FROM      = 'dummy_message_from';
    const DUMMY_MESSAGE_TO        = 'dummy_message_to';
    const DUMMY_MESSAGE_REFERENCE = 'dummy_message_reference';

    /**
     * @var MessageProcessingManager
     */
    private $manager;

    /**
     * @var \Eltrino\EmailProcessingBundle\Model\Message\MessageProvider
     * @Mock \Eltrino\EmailProcessingBundle\Model\Message\MessageProvider
     */
    private $provider;

    /**
     * @var \Eltrino\EmailProcessingBundle\Model\Processing\Context
     * @Mock \Eltrino\EmailProcessingBundle\Model\Processing\Context
     */
    private $context;

    /**
     * @var \Eltrino\EmailProcessingBundle\Model\Processing\StrategyHolder
     * @Mock \Eltrino\EmailProcessingBundle\Model\Processing\StrategyHolder
     */
    private $strategyHolder;

    /**
     * @var \Eltrino\EmailProcessingBundle\Model\Processing\Strategy
     * @Mock \Eltrino\EmailProcessingBundle\Model\Processing\Strategy
     */
    private $strategy;


    protected function setUp()
    {
        MockAnnotations::init($this);
        $this->manager = new MessageProcessingManager($this->context, $this->strategyHolder);
    }

    /**
     * @test
     */
    public function thatHandles()
    {
        $messages = array(new Message(
            self::DUMMY_MESSAGE_UNIQUE_ID,
            self::DUMMY_MESSAGE_ID,
            self::DUMMY_MESSAGE_SUBJECT,
            self::DUMMY_MESSAGE_CONTENT,
            self::DUMMY_MESSAGE_FROM,
            self::DUMMY_MESSAGE_TO,
            self::DUMMY_MESSAGE_REFERENCE)
        );
        $strategies = array($this->strategy);

        $this->provider->expects($this->once())->method('fetchMessagesToProcess')->will($this->returnValue($messages));
        $this->strategyHolder->expects($this->once())->method('getStrategies')->will($this->returnValue($strategies));
        $this->context->expects($this->exactly(count($strategies)))->method('setStrategy')
            ->with($this->isInstanceOf('\Eltrino\EmailProcessingBundle\Model\Processing\Strategy'));
        $this->context->expects($this->exactly(count($messages) * count($strategies)))->method('execute')
            ->with($this->isInstanceOf('Eltrino\EmailProcessingBundle\Model\Message'));
        $this->provider->expects($this->once())->method('markMessagesAsProcessed')
            ->with($this->logicalAnd(
                    $this->isType(\PHPUnit_Framework_Constraint_IsType::TYPE_ARRAY),
                    $this->countOf(count($messages)),
                    $this->callback(function($other) {
                        $result = true;
                        foreach ($other as $message) {
                            $constraint = \PHPUnit_Framework_Assert::isInstanceOf(
                                'Eltrino\EmailProcessingBundle\Model\Message'
                            );
                            try {
                                \PHPUnit_Framework_Assert::assertThat($message, $constraint);
                            } catch (PHPUnit_Framework_ExpectationFailedException $e) {
                                $result = false;
                            }
                        }
                        return $result;
                    })
                )
            );

        $this->manager->handle($this->provider);
    }
}
