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
            $view = $this->getView();
            $view->pairsTextareaAssets();
            // The labels of the columns are data attributes, so they are not
            // translated by the standard rendering of the element. Do not
            // modify the element itself: it may be rendered more than once.
            $translate = $view->plugin('translate');
            $element = clone $element;
            foreach (['data-pairs-key-label', 'data-pairs-value-label'] as $attribute) {
                $label = (string) $element->getAttribute($attribute);
                if ($label !== '') {
                    $element->setAttribute($attribute, $translate($label));
                }
            }
        }
        return parent::render($element);
    }
}
