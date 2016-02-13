<?php
/**
 * Created by PhpStorm.
 * User: flow
 * Date: 5/29/15
 * Time: 6:00 PM
 */

namespace Banana\Controller\Admin;

use Backend\Controller\Admin\AbstractBackendController;
use Banana\Core\Banana;
use Cake\Core\Configure;
use Cake\Core\Plugin;
use Cake\Event\Event;
use Cake\Filesystem\Folder;

class AppController extends AbstractBackendController
{
    public $paginate = [
        'limit' => 100,
    ];

    public function initialize()
    {
        parent::initialize();
        //$this->loadComponent('RequestHandler');
        $this->loadComponent('Backend.Backend');
    }

    public function beforeFilter(Event $event)
    {
        parent::beforeFilter($event);

        $locale = $this->request->query('locale');
        $this->locale = ($locale) ? $locale : Configure::read('Shop.defaultLocale');
    }

    public function beforeRender(Event $event)
    {
        parent::beforeRender($event);
        $this->set('locale', $this->locale);
    }

    protected function _getGalleryList()
    {
        $list = [];
        $mm = MediaManager::get('gallery');
        $list = $mm->getSelectListRecursive();
        return $list;
    }

    public static function backendMenu()
    {
        return [
            'plugin.banana' => [
                'plugin' => 'Banana',
                'title' => 'Content',
                'url' => ['plugin' => 'Banana', 'controller' => 'Pages', 'action' => 'index'],
                'icon' => 'desktop',

                '_children' => [
                    'pages' => [
                        'title' => 'Pages',
                        'url' => ['plugin' => 'Banana', 'controller' => 'Pages', 'action' => 'index'],
                        'icon' => 'sitemap'
                    ],
                    'posts' => [
                        'title' => 'Posts',
                        'url' => ['plugin' => 'Banana', 'controller' => 'Posts', 'action' => 'index'],
                        'icon' => 'edit'
                    ],
                    'galleries' => [
                        'title' => 'Galleries',
                        'url' => ['plugin' => 'Banana', 'controller' => 'Galleries', 'action' => 'index'],
                        'icon' => 'image file outline'
                    ],
                    'page_layouts' => [
                        'title' => 'Layouts',
                        'url' => ['plugin' => 'Banana', 'controller' => 'PageLayouts', 'action' => 'index'],
                        'icon' => 'file'
                    ],
                    'module_builder' => [
                        'title' => 'Module Builder',
                        'url' => ['plugin' => 'Banana', 'controller' => 'ModuleBuilder', 'action' => 'index'],
                        'icon' => 'wizard'
                    ],
                    'modules' => [
                        'title' => 'Modules',
                        'url' => ['plugin' => 'Banana', 'controller' => 'Modules', 'action' => 'index'],
                        'icon' => 'block layout'
                    ],
                    'content_modules' => [
                        'title' => 'Content Modules',
                        'url' => ['plugin' => 'Banana', 'controller' => 'ContentModules', 'action' => 'index'],
                        'icon' => 'content'
                    ],
                    'themes_manager' => [
                        'title' => 'Theme',
                        'url' => ['plugin' => 'Banana', 'controller' => 'ThemesManager', 'action' => 'index'],
                        'icon' => 'paint brush'
                    ],
                ]
            ]
        ];
    }

    /**
     * @deprecated
     */
    protected function getModulesAvailable()
    {
        return Banana::getModuleCellsAvailable();
    }

    /**
     * @deprecated
     */
    protected function getModuleTemplatesAvailable()
    {
        return Banana::getModuleCellTemplatesAvailable();
    }

    /**
     * @deprecated
     */
    protected function getLayoutsAvailable()
    {
        return Banana::getLayoutsAvailable();
    }

    /**
     * @deprecated
     */
    protected function getThemesAvailable()
    {
        return Banana::getLayoutsAvailable();
    }
}
