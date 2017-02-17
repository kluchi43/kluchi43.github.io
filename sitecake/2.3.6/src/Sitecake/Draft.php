<?php

namespace Sitecake;

use phpQuery;
use Sitecake\Util\HtmlUtils;
use Sitecake\Util\Utils;

class Draft extends Page
{
    /**
     * Stores page object after PHP evaluation
     * @var \phpQueryObject
     */
    protected $evaluated;

    /**
     * Draft constructor.
     *
     * @param string $html
     */
    public function __construct($html)
    {
        parent::__construct($html);
        $this->evaluated = $this->createPhpQueryDocSafe($this->evaluate($html));
    }

    /**
     * Adds data-pageid attribute to sitecake meta tag
     * Needed for SC editor to work properly.
     *
     * @param int $id ID to set
     *
     * @throws \Exception
     */
    public function setPageId($id)
    {
        if ('' == $this->getMetadataAttr('pageid')) {
            $this->addMetadataAttr('pageid', $id);
        }
    }

    /**
     * Reads the metadata attribute value.
     *
     * @param  string $attr attribute name
     *
     * @return string returns the attribute value or an empty string if attribute is not present
     * @throws \Exception
     */
    public function getMetadataAttr($attr)
    {
        return phpQuery::pq('meta[content="sitecake"]', $this->evaluated)->attr('data-' . $attr);
    }

    /**
     * Adds an attribute to the sitecake metadata tag. If the metadata tag does not
     * exists it will be created.
     *
     * @param string $attr attribute name
     * @param string $value attribute value
     *
     * @throws \Exception
     */
    public function addMetadataAttr($attr, $value)
    {
        $this->addMetadata();
        phpQuery::pq('meta[content="sitecake"]', $this->evaluated)->attr('data-' . $attr, $value);
    }

    /**
     * Adds meta:application-name to draft header, if not present
     *
     * @throws \Exception
     */
    public function addMetadata()
    {
        if (phpQuery::pq('meta[content="sitecake"]', $this->evaluated)->count() === 0) {
            phpQuery::pq('head', $this->evaluated)->prepend('<meta name="application-name" content="sitecake"/>');
        }
    }

    /**
     * Removes meta:application-name from draft header
     *
     * @throws \Exception
     */
    public function removeMetadata()
    {
        phpQuery::pq('meta[content="sitecake"]', $this->evaluated)->remove();
    }

    /**
     * Removes the specified attribute of the sitecake meta tag.
     *
     * @param  string $attr attribute name
     *
     * @throws \Exception
     */
    public function removeMetadataAttr($attr)
    {
        phpQuery::pq('meta[content="sitecake"]', $this->evaluated)->removeAttr('data-' . $attr);
    }

    /**
     * Adds the 'noindex, nofollow' meta tag to draft header, if not present.
     */
    public function addRobotsNoIndexNoFollow()
    {
        if (phpQuery::pq('meta[content="noindex, nofollow"]', $this->evaluated)->count() === 0) {
            phpQuery::pq('head', $this->evaluated)->prepend('<meta name="robots" content="noindex, nofollow">');
        }
    }

    /**
     * Renders evaluated page
     *
     * @param string $entryPointPath Path/Name to sitecake entry point
     *
     * @return string
     * @throws \Exception
     */
    public function render($entryPointPath)
    {
        $this->adjustNavLinks($entryPointPath);

        return (string)$this->evaluated;
    }

    /**
     * Turns all site links that are not in editable containers to editable links
     *
     * @param string $entryPointPath Path/Name to sitecake entry point
     *
     * @throws \Exception
     */
    protected function adjustNavLinks($entryPointPath)
    {
        // Get all editable links
        $editableLinks = phpQuery::pq('[class*="' . self::SC_BASE_CLASS . '"] a', $this->evaluated);

        foreach (phpQuery::pq('a', $this->evaluated) as $node) {
            // Filter out editable links
            foreach ($editableLinks as $editableLink) {
                if ($node->isSameNode($editableLink)) {
                    continue 2;
                }
            }
            $href = $node->getAttribute('href');
            if (!Utils::isExternalNavLink($href) && Utils::isResourceUrl($href) && !Utils::isScResourceUrl($href)) {
                if (strpos($href, '?') !== false) {
                    $href = str_replace('?', '&', $href);
                }
                $node->setAttribute('href', $entryPointPath . '?scpage=' . ltrim($href, './'));
            }
        }
    }

    /**
     * Normalizes resource paths
     *
     * @param string $base
     *
     * @throws \Exception
     */
    public function normalizeResourcePaths($base)
    {
        foreach (phpQuery::pq('a, img', $this->evaluated) as $node) {
            $attributes = ['src', 'href', 'srcset'];

            foreach ($attributes as $attribute) {
                $value = $node->hasAttribute($attribute) ? $node->getAttribute($attribute) : false;

                if ($value) {
                    // Add basedir prefix to all relative urls that are not resource urls if it's not already there
                    HtmlUtils::prefixNodeAttribute($node, $attribute, $base, function ($url) use ($base) {
                        return Utils::isResourceUrl($url) && !Utils::isScResourceUrl($url) && strpos($url, $base) !== 0;
                    });

                    $newValue = $node->getAttribute($attribute);

                    // Need to strip all '../' and duplicate / inside url
                    if (Utils::isResourceUrl($newValue) && !Utils::isScResourceUrl($newValue)) {
                        $newValue = str_replace(['../', './'], '', $newValue);
                        $newValue = str_replace(['//'], '/', $newValue);
                    }

                    $node->setAttribute($attribute, $newValue);
                }
            }
        }
    }

    /**
     * Appends passed html code to draft header.
     * Used to add sitecake client side script
     *
     * @param string $code
     *
     * @throws \Exception
     */
    public function appendCodeToHead($code)
    {
        HtmlUtils::appendToHead($this->evaluated, $code);
    }
}
