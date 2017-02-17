<?php
namespace Sitecake;

use Sitecake\Util\HtmlUtils;

class Renderer
{
    /**
     * @var array Options with paths
     */
    protected $options;

    /**
     * @var Site Reference to Site object
     */
    protected $site;

    public function __construct($_site, $options)
    {
        $this->site = $_site;
        $this->options = $options;
    }

    public function loginResponse()
    {
        return $this->injectLoginDialog($this->site->getDefaultPublicPage());
    }

    /**
     * @param Draft $draft
     *
     * @return mixed
     * @throws \Exception
     */
    protected function injectLoginDialog($draft)
    {
        $draft->appendCodeToHead($this->clientCodeLogin());

        return $draft->render($this->options['entry_point_file_name']);
    }

    protected function clientCodeLogin()
    {
        $globals = 'var sitecakeGlobals = {' .
                   "editMode: false, " .
                   'serverVersionId: "2.3.6", ' .
                   'phpVersion: "' . phpversion() . '@' . PHP_OS . '", ' .
                   'serviceUrl:"' . $this->options['SERVICE_URL'] . '", ' .
                   'configUrl:"' . $this->options['EDITOR_CONFIG_URL'] . '", ' .
                   'forceLoginDialog: true' .
                   '};';

        return HtmlUtils::wrapToScriptTag($globals) .
               HtmlUtils::scriptTag($this->options['EDITOR_LOGIN_URL'], [
                   'data-cfasync' => 'false'
               ]);
    }

    public function editResponse($page)
    {
        $this->site->startEdit();

        return $this->injectEditorCode(
            $this->site->getDraft($page),
            $this->site->isDraftClean()
        );
    }

    /**
     * @param Draft $draft
     * @param bool $published
     *
     * @return mixed
     * @throws \Exception
     */
    protected function injectEditorCode($draft, $published)
    {
        $draft->appendCodeToHead($this->clientCodeEditor($published));

        return $draft->render($this->options['entry_point_file_name']);
    }

    protected function clientCodeEditor($published)
    {
        $globals = 'var sitecakeGlobals = {' .
                   'editMode: true, ' .
                   'serverVersionId: "2.3.6", ' .
                   'phpVersion: "' . phpversion() . '@' . PHP_OS . '", ' .
                   'serviceUrl: "' . $this->options['SERVICE_URL'] . '", ' .
                   'configUrl: "' . $this->options['EDITOR_CONFIG_URL'] . '", ' .
                   'draftPublished: ' . ($published ? 'true' : 'false') . ', ' .
                   'indexPageName: "' . $this->site->getDefaultIndex() . '"' .
                   '};';

        return HtmlUtils::wrapToScriptTag($globals) .
               HtmlUtils::scriptTag($this->options['EDITOR_EDIT_URL'], [
                   'data-cfasync' => 'false'
               ]);
    }
}
