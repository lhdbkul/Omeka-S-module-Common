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

    /**
     * Skip the values that are not scalar, in particular the obsolete settings
     * stored by an old version of a module, else the form cannot be displayed.
     */
    public function arrayToString($array)
    {
        if (is_array($array)) {
            $array = array_filter($array, 'is_scalar');
        }
        return parent::arrayToString($array);
    }
}
