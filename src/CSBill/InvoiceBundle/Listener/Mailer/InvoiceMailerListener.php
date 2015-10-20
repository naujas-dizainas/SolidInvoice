<?php

/*
 * This file is part of CSBill project.
 *
 * (c) 2013-2015 Pierre du Plessis <info@customscripts.co.za>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace CSBill\InvoiceBundle\Listener\Mailer;

use CSBill\CoreBundle\Mailer\Events\InvoiceMailEvent;
use CSBill\CoreBundle\Mailer\Mailer;
use CSBill\CoreBundle\Mailer\MailerEvents;
use CSBill\InvoiceBundle\Event\InvoiceEvent;
use CSBill\InvoiceBundle\Event\InvoiceEvents;
use CSBill\PaymentBundle\Repository\PaymentMethodRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Templating\EngineInterface;

class InvoiceMailerListener implements EventSubscriberInterface
{
    const TEMPLATE = 'CSBillInvoiceBundle:Email:payment.html.twig';

    /**
     * @var EngineInterface
     */
    private $templating;

    /**
     * @var Mailer
     */
    private $mailer;

    /**
     * @var ObjectManager
     */
    private $entityManager;

    /**
     * @param EngineInterface $templating
     * @param Mailer          $mailer
     * @param ManagerRegistry $registry
     */
    public function __construct(EngineInterface $templating, Mailer $mailer, ManagerRegistry $registry)
    {
        $this->templating = $templating;
        $this->mailer = $mailer;
        $this->entityManager = $registry->getManager();
    }

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            InvoiceEvents::INVOICE_POST_ACCEPT => 'onInvoiceAccepted',
            MailerEvents::MAILER_SEND_INVOICE => 'onInvoiceMail',
        );
    }

    /**
     * @param InvoiceEvent $event
     */
    public function onInvoiceAccepted(InvoiceEvent $event)
    {
        $this->mailer->sendInvoice($event->getInvoice());
    }

    /**
     * @param InvoiceMailEvent $event
     */
    public function onInvoiceMail(InvoiceMailEvent $event)
    {
        /** @var PaymentMethodRepository $paymentRepository */
        $paymentRepository = $this->entityManager->getRepository('CSBillPaymentBundle:PaymentMethod');

        if ($paymentRepository->getTotalMethodsConfigured(false) > 0) {
            $htmlTemplate = $event->getHtmlTemplate();

            $htmlTemplate .= $this->templating->render(self::TEMPLATE, array('invoice' => $event->getInvoice()));

            $event->setHtmlTemplate($htmlTemplate);
        }
    }
}
