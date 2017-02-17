<?php

namespace Sitecake\Services\Pages;

use Silex\Application;
use Sitecake\Services\Service;

class PagesService extends Service
{
    /**
     * @var Application
     */
    protected $ctx;

    protected $pages;

    public function __construct($ctx)
    {
        $this->ctx = $ctx;
        $this->pages = new Pages($this->ctx['site'], $this->ctx);
    }

    public function pages($request)
    {
        $pageUpdates = $request->request->get('pages');
        if (!is_null($pageUpdates)) {
            $this->pages->update(json_decode($pageUpdates, true));
        }

        return $this->json($request, ['status' => 0, 'pages' => array_values($this->pages->listPages())], 200);
    }
}
