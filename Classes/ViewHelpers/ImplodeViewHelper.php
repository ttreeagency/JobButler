<?php
namespace Ttree\JobButler\ViewHelpers;

/*
 * This file is part of the Ttree.JobButler package.
 *
 * (c) Contributors of the project and the ttree team - www.ttree.ch
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Cocur\Slugify\Slugify;
use TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Implode an array
 */
class ImplodeViewHelper extends AbstractViewHelper
{
    /**
     * @param array $value
     * @param string $prefix
     * @param string $separator
     * @return string
     */
    public function render(array $value = null, $prefix = '', $separator = ' ')
    {
        $slugify = new Slugify();
        if ($value === null) {
            $value = $this->renderChildren();
        }
        $value = array_map(function($item) use ($prefix, $slugify) {
            return mb_strtolower($prefix . $slugify->slugify($item));
        }, $value);

        return implode($separator, $value);
    }
}
