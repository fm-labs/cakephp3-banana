<?php
/**
 * Created by PhpStorm.
 * User: flow
 * Date: 4/30/16
 * Time: 1:04 AM
 */

namespace Banana\Controller\Admin;


use Cake\Core\Exception\Exception;
use Cake\Network\Exception\BadRequestException;
use Cake\Network\Exception\NotFoundException;
use Cake\ORM\TableRegistry;

class SortController extends AppController
{

    public function moveUp()
    {

    }

    public function reorder()
    {
        $modelName = $this->request->query('model');
        $field = $this->request->query('field');
        $order = $this->request->query('order');
        $scope = $this->request->query('scope');

        if (!$modelName || !$this->_getModel($modelName)) {
            throw new BadRequestException("Table not found");
        }

        if ($this->_getModel($modelName)->reorder($scope, compact('field', 'order'))) {
            $this->Flash->success(__('Reordering complete'));
        } else {
            $this->Flash->error(__('Reordering failed'));
        }
        $this->redirect($this->referer());
    }

    public function tableSort()
    {
        $this->viewBuilder()->className('Json');

        $responseData = [];
        try {
            if ($this->request->is(['post', 'put'])) {
                $data = $this->request->data;

                $modelName = (isset($data['model'])) ? (string) $data['model'] : null;
                $id = (isset($data['id'])) ? (int) $data['id'] : null;
                $after = (isset($data['after'])) ? (int) $data['after'] : 0;

                if (!$id) {
                    throw new BadRequestException('ID missing');
                }

                if (!$modelName || !$this->_getModel($modelName)) {
                    throw new NotFoundException("Table not found");
                }

                $model = $this->_getModel($modelName);
                if (!$model->behaviors()->has('Sortable')) {
                    throw new Exception('Table has no Sortable behavior attached');
                }

                if ($after < 1) {
                    $success = $model->moveTop($model->get($id));
                } else {
                    $success = $model->moveAfter($model->get($id), $after);
                }

                $responseData['success'] = (bool) $success;
            }
        } catch (\Exception $ex) {
            $responseData['success'] = false;
            $responseData['error'] = $ex->getMessage();
        }

        //$this->autoRender = false;
        //$this->response->body(json_encode($responseData));

        $this->set('result', $responseData);
        $this->set('_serialize', 'result');
    }

    /**
     * @param $tableName
     * @return \Cake\ORM\Table
     */
    protected function _getModel($tableName)
    {
        return TableRegistry::get($tableName);
    }

}