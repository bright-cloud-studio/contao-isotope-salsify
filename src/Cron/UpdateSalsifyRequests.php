<?php

declare(strict_types=1);

namespace Bcs\ContaoIsotopeSalsify\Cron;

use Contao\CommentsNotifyModel;
use Contao\CoreBundle\DependencyInjection\Attribute\AsCronJob;
use Contao\CoreBundle\Framework\ContaoFramework;
use Psr\Log\LoggerInterface;

#[AsCronJob('minutely')]
class PurgeSubscriptionsCron
{
    public function __construct(private ContaoFramework $framework, private LoggerInterface|null $logger)
    {
    }

    public function __invoke(): void
    {
        $this->framework->initialize();
        $this->logger?->info('CRON TRIGGERED YO!');
    }
}
