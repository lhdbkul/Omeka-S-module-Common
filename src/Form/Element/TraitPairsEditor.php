<?php declare(strict_types=1);

namespace Common\Form\Element;

/**
 * Enable the editor of pairs "key = value" on a textarea element.
 *
 * The option "pairs_editor" (true or array) is converted into data attributes
 * read by the javascript "common-pairs-textarea.js", that displays the lines of
 * the textarea as a list of rows with a key and a value, sortable, with an
 * optional list of known keys and default values. The textarea remains the
 * posted value.
 *
 * Options of "pairs_editor":
 * - format (string): "lines" (key = value by line, default), "ini" (values
 *   quoted, typed), or "list" (one value by line, no key column).
 * - separator (string): the separator between key and value (default "=").
 * - key_label, value_label (string): the headers of the columns.
 * - value_type (string): "text" (default) or "number".
 * - sortable (bool): the rows can be reordered (default true).
 * - keys (array): the known keys with their default value or label, used to
 *   fill a picker "Add…" and, when "key_fill" is set, the value of the row.
 * - key_source (string): a css selector of a select whose options are the
 *   known keys (value) and default values (text).
 * - key_skip (array): keys of the source to skip.
 * - key_fill (bool): fill the value with the default of the key (default true
 *   when keys or key_source is set).
 * - free_keys (bool): the user can type any key (default true).
 * - key_readonly (bool): the key of a row cannot be edited (default false).
 * - default_display (string): "form" (default) or "text".
 */
trait TraitPairsEditor
{
    protected function setPairsEditor($options): self
    {
        if (!$options) {
            return $this;
        }
        $options = is_array($options) ? $options : [];

        $class = trim((string) $this->getAttribute('class'));
        if (strpos(" $class ", ' common-pairs-textarea ') === false) {
            $this->setAttribute('class', trim($class . ' common-pairs-textarea'));
        }

        $format = $options['format'] ?? $this->pairsEditorDefaultFormat();
        $keys = $options['keys'] ?? null;
        $keySource = $options['key_source'] ?? null;
        $keyFill = $options['key_fill'] ?? ($keys || $keySource);

        $attributes = [
            'data-pairs-format' => $format,
            'data-pairs-separator' => $options['separator'] ?? $this->pairsEditorDefaultSeparator(),
            'data-pairs-key-label' => $options['key_label'] ?? '',
            'data-pairs-value-label' => $options['value_label'] ?? '',
            'data-pairs-value-type' => $options['value_type'] ?? 'text',
            'data-pairs-sortable' => empty($options['sortable']) && array_key_exists('sortable', $options) ? '0' : '1',
            'data-pairs-key-fill' => $keyFill ? '1' : '0',
            'data-pairs-free-keys' => empty($options['free_keys']) && array_key_exists('free_keys', $options) ? '0' : '1',
            'data-pairs-key-readonly' => empty($options['key_readonly']) ? '0' : '1',
            'data-pairs-default-display' => ($options['default_display'] ?? 'form') === 'text' ? 'text' : 'form',
        ];
        if ($keys) {
            $attributes['data-pairs-keys'] = json_encode($keys, 320);
        }
        if ($keySource) {
            $attributes['data-pairs-key-source'] = $keySource;
        }
        if (!empty($options['key_skip'])) {
            $attributes['data-pairs-key-skip'] = json_encode(array_values($options['key_skip']), 320);
        }
        foreach ($attributes as $name => $value) {
            $this->setAttribute($name, $value);
        }
        return $this;
    }

    protected function pairsEditorDefaultFormat(): string
    {
        if ($this instanceof IniTextarea) {
            return 'ini';
        }
        return method_exists($this, 'getAsKeyValue') && !$this->getAsKeyValue()
            ? 'list'
            : 'lines';
    }

    protected function pairsEditorDefaultSeparator(): string
    {
        // The core getter requires a useless argument.
        return method_exists($this, 'getKeyValueSeparator')
            ? (string) $this->getKeyValueSeparator('=')
            : '=';
    }
}
