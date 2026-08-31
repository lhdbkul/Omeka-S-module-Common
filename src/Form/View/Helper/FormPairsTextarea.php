<?php declare(strict_types=1);

namespace Common\Form\View\Helper;

use Laminas\Form\ElementInterface;
use Laminas\Form\View\Helper\FormTextarea;

/**
 * Render a textarea and enqueue the assets of the editor of pairs if enabled.
 */
class FormPairsTextarea extends FormTextarea
{
    public function render(ElementInterface $element): string
    {
        $class = ' ' . (string) $element->getAttribute('class') . ' ';
        if (strpos($class, ' common-pairs-textarea ') !== false) {
            $this->getView()->pairsTextareaAssets();
        }
        return parent::render($element);
    }
}
