<?php
namespace Sitecake\Services\Pages;

use Sitecake\Exception\BadArgumentException;
use Sitecake\Site;
use Sitecake\Util\Utils;

class Pages
{
    protected $conf;

    /**
     * @var Site
     */
    protected $site;

    /**
     * List of site pages
     * @var array
     */
    protected $pageList;

    public function __construct(Site $site, $conf)
    {
        $this->site = $site;
        $this->conf = $conf;
    }

    /*static function update($newPages) {
        $pages = $this->pages->listPages();
        $pages = pages::get(true);
        pages::sanity_check($pages, $newPages);
        $pages = $pages['pages'];
        $navPages = pages::nav_pages($newPages);
        pages::updatePages($pages, $newPages, $navPages);
        pages::savePages($newPages);
        pages::remove_deleted_pages($pages, $newPages);
        pages::sitemap($navPages);
        return array('status' => 0, 'pages' => pages::reduce_pages($newPages));
    }*/

    /*static function sanity_check($pages, $newPages) {
        $homePages = util::arrayFilterProp($newPages, 'home', true);
        if (!(is_array($homePages) && count($homePages) == 1))
            throw new \Exception(
                'One and only one page should be marked as the home page');

        $homePage = util::arrayFindProp($newPages, 'home', true);
        if (!(isset($homePage['url']) && $homePage['url'] == 'index.html'))
            throw new \Exception(
                'The URL of the home page has to be index.html');

        array_walk($newPages, function($page) {
            if (!util::strEndsWith('.html', $page['url']))
                throw new \Exception('The page URL has to end with .html');
        });
    }*/

    public function update($pageUpdates)
    {
        /**
         * Go through all existing page files (from metadata) and compare it with received $pageUpdates
         *        - Create new pages (id not set tid set to source page)
         *        - Delete pages that could not be found in received $pageUpdates and we have it in page files
         *        - Update unnamed container names
         *        - Duplicate resources in unnamed containers
         *        - Update title and description metadata for all pages
         *        - Update menus in all files that contain menu
         *        - Update site metadata
         */
        $pagesMetadata = $this->listPages();
        $paths = array_keys($pagesMetadata);
        $metadata = [];
        $pages = [];
        foreach ($pageUpdates as $no => $pageDetails) {
            // Get page path
            $path = $this->site->pageFilePath($pageDetails['url']);

            // Gather metadata for later update
            $metadata[$path] = $pageDetails;

            // Get navigation property to copy it to new metadata
            $metadata[$path]['navigation'] = isset($pagesMetadata[$path]['navigation']) ?
                $pagesMetadata[$path]['navigation'] : false;

            if (!isset($pageDetails['id'])) {
                if (!isset($pageDetails['tid'])) {
                    throw new BadArgumentException(['name' => 'pages[' . $no . ']']);
                }

                // This is a new page, create it from source
                $page = $this->site->newPage($path, $pageDetails);

                $metadata[$path]['id'] = Utils::id();
                unset($metadata[$path]['tid']);
            } else {
                $page = $this->site->updatePage($path, $pageDetails);
            }

            $pages[$path] = [
                'path' => $path,
                'page' => $page,
            ];

            if (($index = array_search($path, $paths)) !== false) {
                unset($paths[$index]);
            }
        }

        // Remove deleted pages
        if (!empty($paths)) {
            $this->site->deleteDraftPages($paths);
        }

        // Update page files and menus
        $this->site->updatePageFiles($pages, $metadata);
    }

    public function listPages()
    {
        $this->pageList = $this->site->loadMetadata()['pages'];

        return $this->pageList;
    }
}
