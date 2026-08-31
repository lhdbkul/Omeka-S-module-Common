<?php declare(strict_types=1);

namespace Common\Form\Element;

/**
 * The core ArrayTextarea with the optional editor of pairs.
 */
class ArrayTextarea extends \Omeka\Form\Element\ArrayTextarea
{
    use TraitPairsEditor;

    public function setOptions($options)
    {
        parent::setOptions($options);
        if (array_key_exists('pairs_editor', $this->options)) {
            $this->setPairsEditor($this->options['pairs_editor']);
        }
        return $this;
    }
}
