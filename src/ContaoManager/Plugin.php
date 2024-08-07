<?php

/**
 * @copyright  Bright Cliud Studio
 * @author     Bright Cloud Studio
 * @package    Contao Isotope Salsify
 * @license    LGPL-3.0+
 * @see	       https://github.com/bright-cloud-studio/contao-isotope-salsify
 */

namespace Bcs\SalsifyBundle\ContaoManager;
use Contao\ManagerPlugin\Bundle\Config\BundleConfig;
use Contao\ManagerPlugin\Bundle\BundlePluginInterface;
use Contao\ManagerPlugin\Bundle\Parser\ParserInterface;

class Plugin implements BundlePluginInterface
{
    /** {@inheritdoc} */
    public function getBundles(ParserInterface $parser)
    {
        return [
            BundleConfig::create('Bcs\SalsifyBundle\BcsSalsifyBundle')
                ->setLoadAfter(['Contao\CoreBundle\ContaoCoreBundle']),
        ];
    }
}
