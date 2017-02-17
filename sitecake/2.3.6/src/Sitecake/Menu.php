<?php

namespace Sitecake;

use Sitecake\Util\HtmlUtils;

class Menu
{
    const SC_MENU_BASE_CLASS = 'sc-nav';
    /**
     * At the moment all menus are treated as main. When menu manager is implemented this will deffer from menu to menu
     * @var string
     */
    protected $name = 'main';

    protected $items;

    private $node;

    public function __construct(\DOMElement $node)
    {
        $this->node = $node;

        $this->init();
    }

    protected function init()
    {
        $class = $this->node->getAttribute('class');

        if (preg_match('/(^|\s)(' . preg_quote(self::SC_MENU_BASE_CLASS) . '(\-([^\s]+))*)(\s|$)/', $class, $matches)) {
            // TODO : Uncomment this when menu manager is implemented in editor
            //if($matches[3] != self::SC_MENU_BASE_CLASS && isset($matches[5]))
            //{
            //    $this->_name = $matches[5];
            //}

            $this->findItems();
        }
    }

    protected function findItems()
    {
        $doc = new \DOMDocument();

        // Suppress HTML5 errors
        libxml_use_internal_errors(true);

        $doc->loadHTML((string)$this);

        libxml_use_internal_errors(false);

        foreach ($doc->getElementsByTagName('a') as $no => $menuItem) {
            /** @var \DOMElement $menuItem */
            $this->items[] = [
                'text' => $menuItem->textContent,
                'url' => $menuItem->getAttribute('href')
            ];
        }
    }

    public function render($template, $isActive = null, $activeClass = '')
    {
        $this->node->nodeValue = '';
        $menuItems = '';

        foreach ($this->items as $no => $item) {
            $itemHTML = str_replace('${url}', $item['url'], $template);
            $itemHTML = str_replace('${title}', $item['text'], $itemHTML);
            $itemHTML = str_replace('${order}', $no, $itemHTML);

            if (strpos($itemHTML, '${active}') !== false) {
                if (is_callable($isActive) && $isActive($item['url'])) {
                    $itemHTML = str_replace('${active}', $activeClass, $itemHTML);
                } else {
                    $itemHTML = str_replace('${active}', '', $itemHTML);
                }
            }

            $menuItems .= $itemHTML;
            HtmlUtils::appendHTML($this->node, $itemHTML);
        }

        return $menuItems;//(string)$this;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return trim($this->node->ownerDocument->saveHTML($this->node));
    }

    public function name()
    {
        return $this->name;
    }

    public function items($items = null)
    {
        if (empty($items)) {
            return $this->items;
        }

        return $this->items = $items;
    }
}
