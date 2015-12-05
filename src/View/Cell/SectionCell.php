<?php
namespace Banana\View\Cell;

use Cake\View\Cell;
use Cake\Network\Request;
use Cake\Network\Response;
use Cake\Event\EventManager;
use Banana\Model\Table\ContentModulesTable;

/**
 * Class SectionCell
 *
 * @package App\View\Cell
 *
 * @property ContentModulesTable $ContentModules
 */
class SectionCell extends Cell
{
    protected $_validCellOptions = ['name', 'page_id'];

    /**
     * @var string Section name. Can be passed as cell option.
     */
    public $name;

    public $page_id;

    protected $_layoutModules = [];

    protected $_pageModules = [];

    /**
     * Constructor.
     *
     * @param \Cake\Network\Request $request The request to use in the cell.
     * @param \Cake\Network\Response $response The response to use in the cell.
     * @param \Cake\Event\EventManager $eventManager The eventManager to bind events to.
     * @param array $cellOptions Cell options to apply.
     */
    public function __construct(
        Request $request = null,
        Response $response = null,
        EventManager $eventManager = null,
        array $cellOptions = []
    ) {
        parent::__construct($request, $response, $eventManager, $cellOptions);

    }

    public function display()
    {
        $this->loadModel('Banana.ContentModules');

        $this->_loadPageModules();
        //if (count($this->_pageModules) < 1) {
            $this->_loadLayoutModules();
        //}

        $this->set('page_id', $this->page_id);
        $this->set('section', $this->name);
        $this->set('layout_modules', $this->_layoutModules);
        $this->set('page_modules', $this->_pageModules);
    }

    protected function _loadPageModules()
    {
        if (!isset($this->page_id)) {
            debug("ContentModules skipped for section " . $this->name . ": No pageId set");
            $this->_pageModules = [];
            return;
        }

        $pageId = $this->page_id;
        $this->_pageModules = $this->ContentModules->find()
            ->where(['section' => $this->name, 'refscope' => 'Banana.Pages', 'refid' => $pageId])
            ->contain(['Modules'])
            ->all();
    }

    protected function _loadLayoutModules()
    {
        $this->_layoutModules = $this->ContentModules->find()
            ->where(['section' => $this->name, 'refscope' => 'Banana.Pages', 'refid IS NULL'])
            ->contain(['Modules'])
            ->all();
    }


}

