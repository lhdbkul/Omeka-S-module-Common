<?php declare(strict_types=1);

namespace Common\View\Helper;

use Laminas\View\Helper\AbstractHelper;

/**
 * Enqueue the assets of the editor of pairs of the textareas.
 */
class PairsTextareaAssets extends AbstractHelper
{
    protected $appended = false;

    public function __invoke(): void
    {
        if ($this->appended) {
            return;
        }
        $this->appended = true;

        $view = $this->getView();
        $translate = $view->plugin('translate');

        $data = [
            'labels' => [
                'editAsForm' => $translate('Edit as a form'), // @translate
                'editAsText' => $translate('Edit as text'), // @translate
                'key' => $translate('key'), // @translate
                'value' => $translate('value'), // @translate
                'add' => $translate('Add'), // @translate
                'pick' => $translate('Add…'), // @translate
                'remove' => $translate('Remove'), // @translate
                'drag' => $translate('Drag to reorder'), // @translate
                'move' => $translate('Move: use the up and down arrows to reorder'), // @translate
                'up' => $translate('Move up'), // @translate
                'down' => $translate('Move down'), // @translate
                'unparsable' => $translate('The text cannot be edited as a list: fix it or edit it as text.'), // @translate
                'keyForbidden' => $translate('The key cannot contain "{separator}".'), // @translate
            ],
        ];

        $assetUrl = $view->plugin('assetUrl');
        $view->headLink()
            ->appendStylesheet($assetUrl('css/common-pairs-textarea.css', 'Common'));
        $view->headScript()
            ->appendScript('window.CommonPairsTextarea = ' . json_encode($data, 320) . ';')
            ->appendFile($assetUrl('js/common-pairs-textarea.js', 'Common'), 'text/javascript', ['defer' => 'defer']);
    }
}
